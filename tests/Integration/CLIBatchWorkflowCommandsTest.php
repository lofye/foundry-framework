<?php

declare(strict_types=1);

namespace Foundry\Tests\Integration;

use Foundry\CLI\Application;
use PHPUnit\Framework\TestCase;

final class CLIBatchWorkflowCommandsTest extends TestCase
{
    private string $cwd;

    protected function setUp(): void
    {
        $this->cwd = getcwd() ?: '.';
        chdir(dirname(__DIR__, 2));
    }

    protected function tearDown(): void
    {
        chdir($this->cwd);
    }

    public function test_verify_architecture_batch_runs_as_human_readable_command(): void
    {
        $result = $this->runCommandRaw(new Application(), ['foundry', 'verify', 'architecture']);

        $this->assertSame(0, $result['status']);
        $this->assertStringContainsString('Verify architecture', $result['output']);
        $this->assertStringContainsString('Status: ok', $result['output']);
        $this->assertStringContainsString('Summary: 6/6 steps passed', $result['output']);
    }

    public function test_verify_feature_work_batch_runs_as_human_readable_command(): void
    {
        $result = $this->runCommandRaw(new Application(), ['foundry', 'verify', 'feature-work', 'quality-enforcement']);

        $this->assertSame(0, $result['status']);
        $this->assertStringContainsString('Verify feature-work: quality-enforcement', $result['output']);
        $this->assertStringContainsString('Status: ok', $result['output']);
        $this->assertStringContainsString('Summary: 5/5 steps passed', $result['output']);
    }

    public function test_verify_feature_work_requires_feature_slug(): void
    {
        $result = $this->runCommand(new Application(), ['foundry', 'verify', 'feature-work', '--json']);

        $this->assertSame(1, $result['status']);
        $this->assertSame('CLI_VERIFY_FEATURE_WORK_FEATURE_REQUIRED', $result['payload']['error']['code']);
    }

    public function test_doctor_runs_as_human_readable_quality_overview(): void
    {
        $result = $this->runCommandRaw(new Application(), ['foundry', 'doctor']);

        $this->assertSame(0, $result['status']);
        $this->assertStringContainsString('Foundry doctor completed', $result['output']);
        $this->assertStringContainsString('Checks:', $result['output']);
        $this->assertStringContainsString('Suggested actions:', $result['output']);
    }

    public function test_doctor_ready_runs_batch_workflow(): void
    {
        $result = $this->runCommandRaw(new Application(), ['foundry', 'doctor', '--ready']);

        $this->assertSame(0, $result['status']);
        $this->assertStringContainsString('Doctor ready', $result['output']);
        $this->assertStringContainsString('Status: ok', $result['output']);
        $this->assertStringContainsString('Summary: 7/7 steps passed', $result['output']);
    }

    public function test_doctor_cli_graph_and_strict_modes_report_expected_status(): void
    {
        $cliGraph = $this->runCommand(new Application(), [
            'foundry',
            'doctor',
            '--cli',
            '--graph',
            '--feature=publish-post',
            '--json',
        ]);

        $this->assertSame(0, $cliGraph['status']);
        $this->assertTrue($cliGraph['payload']['cli']);
        $this->assertTrue($cliGraph['payload']['graph_mode']);
        $this->assertSame('publish-post', $cliGraph['payload']['feature_filter']);
        $this->assertArrayHasKey('cli_surface', $cliGraph['payload']);

        $strict = $this->runCommand(new Application(), ['foundry', 'doctor', '--strict', '--json']);

        $this->assertSame(1, $strict['status']);
        $this->assertFalse($strict['payload']['ok']);

        $missing = $this->runCommand(new Application(), ['foundry', 'doctor', '--feature=missing-feature', '--json']);

        $this->assertSame(1, $missing['status']);
        $this->assertSame('FEATURE_NOT_FOUND', $missing['payload']['error']['code']);
    }

    public function test_context_bootstrap_and_recover_batches_run_for_existing_context(): void
    {
        $bootstrap = $this->runCommandRaw(new Application(), ['foundry', 'context', 'bootstrap', 'quality-enforcement']);

        $this->assertSame(0, $bootstrap['status']);
        $this->assertStringContainsString('Context bootstrap: quality-enforcement', $bootstrap['output']);
        $this->assertStringContainsString('Summary: 3/3 steps passed', $bootstrap['output']);

        $recover = $this->runCommandRaw(new Application(), ['foundry', 'context', 'recover', 'quality-enforcement']);

        $this->assertSame(0, $recover['status']);
        $this->assertStringContainsString('Context recover: quality-enforcement', $recover['output']);
        $this->assertStringContainsString('Summary: 4/4 steps passed', $recover['output']);
    }

    public function test_context_batch_commands_require_feature_slug(): void
    {
        $bootstrap = $this->runCommand(new Application(), ['foundry', 'context', 'bootstrap', '--json']);

        $this->assertSame(1, $bootstrap['status']);
        $this->assertSame('CLI_CONTEXT_BOOTSTRAP_FEATURE_REQUIRED', $bootstrap['payload']['error']['code']);

        $recover = $this->runCommand(new Application(), ['foundry', 'context', 'recover', '--json']);

        $this->assertSame(1, $recover['status']);
        $this->assertSame('CLI_CONTEXT_RECOVER_FEATURE_REQUIRED', $recover['payload']['error']['code']);
    }

    /**
     * @param array<int,string> $argv
     * @return array{status:int,payload:array<string,mixed>}
     */
    private function runCommand(Application $app, array $argv): array
    {
        ob_start();
        $status = $app->run($argv);
        $output = ob_get_clean() ?: '';

        /** @var array<string,mixed> $payload */
        $payload = json_decode($output, true, 512, JSON_THROW_ON_ERROR);

        return ['status' => $status, 'payload' => $payload];
    }

    /**
     * @param array<int,string> $argv
     * @return array{status:int,output:string}
     */
    private function runCommandRaw(Application $app, array $argv): array
    {
        ob_start();
        $status = $app->run($argv);
        $output = (string) (ob_get_clean() ?: '');

        return ['status' => $status, 'output' => $output];
    }
}
