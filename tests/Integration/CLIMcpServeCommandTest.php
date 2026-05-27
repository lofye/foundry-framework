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

    public function test_mcp_generate_plan_surfaces_entitlement_contract(): void
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
    }

    public function test_mcp_apply_plan_dry_run_runs_preflight_without_mutation(): void
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
        $affected = (string) ($generate['payload']['plan']['affected_files'][0] ?? '');
        $this->assertNotSame('', $affected);
        $featurePath = $this->project->root . '/' . $affected;
        $this->assertFileDoesNotExist($featurePath, 'Preflight should not apply planned file mutations.');

        $apply = $this->runCommand($app, [
            'foundry',
            'mcp:serve',
            '--tool=apply_plan',
            '--input={"plan_id":"' . $planId . '","strict":true,"dry_run":true}',
            '--json',
        ]);
        $this->assertSame(0, $apply['status']);
        $this->assertSame('apply_plan', $apply['payload']['tool']);
        $this->assertSame('preflight_passed', $apply['payload']['data']['status']);
        $this->assertSame($planId, $apply['payload']['data']['plan_id']);
        $this->assertTrue($apply['payload']['data']['dry_run']);
        $this->assertSame('passed', $apply['payload']['data']['preflight']['status']);
        $this->assertNull($apply['payload']['data']['result']);
        $this->assertNull($apply['payload']['data']['error']);
        $this->assertFileDoesNotExist($featurePath);
    }

    public function test_mcp_explain_plan_matches_cli_plan_explain_payload(): void
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

        $cli = $this->runCommand($app, [
            'foundry',
            'explain',
            'plan',
            $planId,
            '--json',
        ]);
        $this->assertSame(0, $cli['status']);

        $mcp = $this->runCommand($app, [
            'foundry',
            'mcp:serve',
            '--tool=explain_plan',
            '--input={"plan_id":"' . $planId . '"}',
            '--json',
        ]);

        $this->assertSame(0, $mcp['status']);
        $this->assertSame('explain_plan', $mcp['payload']['tool']);
        $this->assertSame($cli['payload'], $mcp['payload']['data']);
        $this->assertSame('explainable', $mcp['payload']['data']['status']);
        $this->assertSame('ready', $mcp['payload']['data']['readiness']['status']);
        $this->assertSame('apply_plan', $mcp['payload']['data']['readiness']['next_actions'][0]['type']);
    }

    public function test_mcp_apply_plan_applies_after_preflight_and_generate_apply_alias_matches_contract(): void
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
        $affected = (string) ($generate['payload']['plan']['affected_files'][0] ?? '');
        $this->assertNotSame('', $affected);
        $featurePath = $this->project->root . '/' . $affected;
        $this->assertFileDoesNotExist($featurePath);

        $canonical = $this->runCommand($app, [
            'foundry',
            'mcp:serve',
            '--tool=apply_plan',
            '--input={"plan_id":"' . $planId . '","strict":true}',
            '--json',
        ]);
        $this->assertSame(0, $canonical['status']);
        $this->assertSame('apply_plan', $canonical['payload']['tool']);
        $this->assertSame('applied', $canonical['payload']['data']['status']);
        $this->assertFalse($canonical['payload']['data']['dry_run']);
        $this->assertSame('passed', $canonical['payload']['data']['preflight']['status']);
        $this->assertNull($canonical['payload']['data']['error']);
        $this->assertFileExists($featurePath);

        $alias = $this->runCommand($app, [
            'foundry',
            'mcp:serve',
            '--tool=generate_apply',
            '--input={"plan_id":"missing-plan"}',
            '--json',
        ]);
        $this->assertSame(0, $alias['status']);
        $this->assertSame('generate_apply', $alias['payload']['tool']);
        $this->assertSame('invalid', $alias['payload']['data']['status']);
        $this->assertSame('PLAN_RECORD_NOT_FOUND', $alias['payload']['data']['error']['code']);
    }

    public function test_mcp_apply_plan_requires_plan_id_input(): void
    {
        $result = $this->runCommand(new Application(), [
            'foundry',
            'mcp:serve',
            '--tool=apply_plan',
            '--input={"strict":true}',
            '--json',
        ]);

        $this->assertSame(1, $result['status']);
        $this->assertSame('MCP_INPUT_INVALID', $result['payload']['error']['code']);
    }

    public function test_mcp_apply_plan_returns_invalid_for_unknown_plan_id(): void
    {
        $result = $this->runCommand(new Application(), [
            'foundry',
            'mcp:serve',
            '--tool=apply_plan',
            '--input={"plan_id":"missing-plan"}',
            '--json',
        ]);

        $this->assertSame(0, $result['status']);
        $this->assertSame('invalid', $result['payload']['data']['status']);
        $this->assertSame('invalid', $result['payload']['data']['execution_state']);
        $this->assertSame('PLAN_RECORD_NOT_FOUND', $result['payload']['data']['error']['code']);
    }

    public function test_mcp_apply_plan_blocks_stale_plan_before_mutation(): void
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
        $record['metadata']['source_hash'] = 'mcp-apply-stale-source-hash';
        file_put_contents($recordPath, json_encode($record, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR) . PHP_EOL);

        $affected = (string) ($generate['payload']['plan']['affected_files'][0] ?? '');
        $this->assertNotSame('', $affected);
        $featurePath = $this->project->root . '/' . $affected;
        $this->assertFileDoesNotExist($featurePath);

        $apply = $this->runCommand($app, [
            'foundry',
            'mcp:serve',
            '--tool=apply_plan',
            '--input={"plan_id":"' . $planId . '","strict":true}',
            '--json',
        ]);

        $this->assertSame(0, $apply['status']);
        $this->assertSame('blocked', $apply['payload']['data']['status']);
        $this->assertSame('stale', $apply['payload']['data']['execution_state']);
        $this->assertSame('PLAN_STALE', $apply['payload']['data']['error']['code']);
        $this->assertFileDoesNotExist($featurePath);
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
