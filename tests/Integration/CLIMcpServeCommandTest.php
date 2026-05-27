<?php

declare(strict_types=1);

namespace Foundry\Tests\Integration;

use Foundry\CLI\Application;
use Foundry\Tests\Fixtures\TempProject;
use PHPUnit\Framework\TestCase;

final class CLIMcpServeCommandTest extends TestCase
{
    private TempProject $project;
    private string $cwd;

    protected function setUp(): void
    {
        $this->project = new TempProject();
        $this->cwd = getcwd() ?: '.';
        chdir($this->project->root);
    }

    protected function tearDown(): void
    {
        chdir($this->cwd);
        $this->project->cleanup();
    }

    public function test_mcp_serve_exposes_deterministic_manifest_and_tools(): void
    {
        $app = new Application();

        $examples = $this->runCommand($app, ['foundry', 'mcp:serve', '--tool=list_examples', '--json']);
        $this->assertSame(0, $examples['status']);
        $this->assertSame('list_examples', $examples['payload']['tool']);
        $this->assertArrayHasKey('examples', $examples['payload']['data']);

        $packs = $this->runCommand($app, ['foundry', 'mcp:serve', '--tool=list_packs', '--json']);
        $this->assertSame(0, $packs['status']);
        $this->assertSame('list_packs', $packs['payload']['tool']);
        $this->assertSame([], $packs['payload']['data']['packs']);

        $events = $this->runCommand($app, ['foundry', 'mcp:serve', '--tool=event.list', '--json']);
        $this->assertSame(0, $events['status']);
        $this->assertSame('event.list', $events['payload']['tool']);
        $this->assertSame([], $events['payload']['data']['events']);

        $eventInspect = $this->runCommand($app, ['foundry', 'mcp:serve', '--tool=event.inspect', '--input={"event":"missing.event"}', '--json']);
        $this->assertSame(0, $eventInspect['status']);
        $this->assertSame('event.inspect', $eventInspect['payload']['tool']);
        $this->assertSame('missing.event', $eventInspect['payload']['data']['event']);
        $this->assertSame([], $eventInspect['payload']['data']['listeners']);
    }

    public function test_mcp_tool_parity_matches_examples_cli_payload(): void
    {
        $app = new Application();

        $cli = $this->runCommand($app, ['foundry', 'examples:list', '--json']);
        $mcp = $this->runCommand($app, ['foundry', 'mcp:serve', '--tool=list_examples', '--json']);

        $this->assertSame(0, $cli['status']);
        $this->assertSame(0, $mcp['status']);
        $this->assertSame($cli['payload'], $mcp['payload']['data']);
    }

    public function test_mcp_generate_plan_and_apply_tools_surface_entitlement_and_apply_contracts(): void
    {
        $app = new Application();

        $planBlocked = $this->runCommand($app, [
            'foundry',
            'mcp:serve',
            '--tool=generate_plan',
            '--input={"intent":"Create blog post notes","mode":"new","packs":["foundry/blog"]}',
            '--json',
        ]);
        $this->assertSame(0, $planBlocked['status']);
        $this->assertSame('generate_plan', $planBlocked['payload']['tool']);
        $this->assertSame('blocked', $planBlocked['payload']['data']['status']);
        $this->assertSame('blocked_pack_unavailable', $planBlocked['payload']['data']['execution_state']);
        $this->assertSame('blocked', $planBlocked['payload']['data']['validation']['status']);
        $this->assertSame(['foundry/blog'], $planBlocked['payload']['data']['entitlements']['required']);

        $generate = $this->runCommand($app, [
            'foundry',
            'generate',
            'Create',
            'comments',
            '--mode=new',
            '--json',
        ]);
        $this->assertSame(0, $generate['status']);
        $planId = (string) ($generate['payload']['plan_record']['plan_id'] ?? '');
        $this->assertNotSame('', $planId);

        $apply = $this->runCommand($app, [
            'foundry',
            'mcp:serve',
            '--tool=generate_apply',
            '--input={"plan_id":"' . $planId . '","strict":true}',
            '--json',
        ]);
        $this->assertSame(0, $apply['status']);
        $this->assertSame('generate_apply', $apply['payload']['tool']);
        $this->assertSame('applied', $apply['payload']['data']['status']);
        $this->assertSame($planId, $apply['payload']['data']['plan_id']);
    }

    public function test_mcp_generate_plan_requires_intent_input(): void
    {
        $result = $this->runCommand(new Application(), [
            'foundry',
            'mcp:serve',
            '--tool=generate_plan',
            '--input={"mode":"new"}',
            '--json',
        ]);

        $this->assertSame(1, $result['status']);
        $this->assertSame('MCP_INPUT_INVALID', $result['payload']['error']['code']);
    }

    public function test_mcp_validate_plan_supports_plan_id_and_inline_plan_inputs(): void
    {
        $app = new Application();
        $generate = $this->runCommand($app, [
            'foundry',
            'generate',
            'Create',
            'comments',
            '--mode=new',
            '--dry-run',
            '--json',
        ]);
        $this->assertSame(0, $generate['status']);
        $planId = (string) ($generate['payload']['plan_record']['plan_id'] ?? '');
        $this->assertNotSame('', $planId);

        $byId = $this->runCommand($app, [
            'foundry',
            'mcp:serve',
            '--tool=validate_plan',
            '--input={"plan_id":"' . $planId . '"}',
            '--json',
        ]);
        $this->assertSame(0, $byId['status']);
        $this->assertSame('validate_plan', $byId['payload']['tool']);
        $this->assertSame('valid', $byId['payload']['data']['status']);
        $this->assertSame($planId, $byId['payload']['data']['plan_id']);
        $this->assertSame('executable', $byId['payload']['data']['execution_state']);
        $this->assertSame('valid', $byId['payload']['data']['validation']['status']);

        $inline = $this->runCommand($app, [
            'foundry',
            'mcp:serve',
            '--tool=validate_plan',
            '--input=' . json_encode(['plan' => $generate['payload']['plan']], JSON_THROW_ON_ERROR),
            '--json',
        ]);
        $this->assertSame(0, $inline['status']);
        $this->assertSame('validate_plan', $inline['payload']['tool']);
        $this->assertSame('valid', $inline['payload']['data']['status']);
        $this->assertNull($inline['payload']['data']['plan_id']);
        $this->assertSame('executable', $inline['payload']['data']['execution_state']);
    }

    public function test_mcp_validate_plan_reports_stale_for_strict_drift_detection(): void
    {
        $app = new Application();
        $generate = $this->runCommand($app, [
            'foundry',
            'generate',
            'Create',
            'comments',
            '--mode=new',
            '--dry-run',
            '--json',
        ]);
        $this->assertSame(0, $generate['status']);

        $planId = (string) $generate['payload']['plan_record']['plan_id'];
        $recordPath = $this->project->root . '/' . $generate['payload']['plan_record']['storage_path'];
        $record = json_decode((string) file_get_contents($recordPath), true, 512, JSON_THROW_ON_ERROR);
        $record['metadata']['source_hash'] = 'mcp-validate-stale-source-hash';
        file_put_contents($recordPath, json_encode($record, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR) . PHP_EOL);

        $validate = $this->runCommand($app, [
            'foundry',
            'mcp:serve',
            '--tool=validate_plan',
            '--input={"plan_id":"' . $planId . '"}',
            '--json',
        ]);

        $this->assertSame(0, $validate['status']);
        $this->assertSame('stale', $validate['payload']['data']['status']);
        $this->assertSame('stale', $validate['payload']['data']['execution_state']);
        $this->assertSame('PLAN_REPLAY_STRICT_DRIFT', $validate['payload']['data']['validation']['errors'][0]['code']);
    }

    public function test_mcp_validate_plan_returns_invalid_for_unknown_plan_id(): void
    {
        $validate = $this->runCommand(new Application(), [
            'foundry',
            'mcp:serve',
            '--tool=validate_plan',
            '--input={"plan_id":"missing-plan"}',
            '--json',
        ]);

        $this->assertSame(0, $validate['status']);
        $this->assertSame('invalid', $validate['payload']['data']['status']);
        $this->assertSame('invalid', $validate['payload']['data']['execution_state']);
        $this->assertSame('PLAN_RECORD_NOT_FOUND', $validate['payload']['data']['validation']['errors'][0]['code']);
    }

    /**
     * @param array<int,string> $argv
     * @return array{status:int,payload:array<string,mixed>}
     */
    private function runCommand(Application $app, array $argv): array
    {
        ob_start();
        $status = $app->run($argv);
        $output = trim((string) ob_get_clean());

        /** @var array<string,mixed> $payload */
        $payload = json_decode($output, true, 512, JSON_THROW_ON_ERROR);

        return ['status' => $status, 'payload' => $payload];
    }
}
