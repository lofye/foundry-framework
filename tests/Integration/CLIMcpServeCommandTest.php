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
        $this->assertSame('invalid', $planBlocked['payload']['data']['execution_state']);
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
