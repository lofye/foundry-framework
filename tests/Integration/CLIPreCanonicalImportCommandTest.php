<?php

declare(strict_types=1);

namespace Foundry\Tests\Integration;

use Foundry\CLI\Application;
use Foundry\Tests\Fixtures\TempProject;
use PHPUnit\Framework\TestCase;

final class CLIPreCanonicalImportCommandTest extends TestCase
{
    private TempProject $project;
    private string $cwd;

    protected function setUp(): void
    {
        $this->project = new TempProject();
        $this->cwd = getcwd() ?: '.';
        chdir($this->project->root);
        $this->writeFile('Modules/implementation.log', "# Implementation Log\n");
    }

    protected function tearDown(): void
    {
        chdir($this->cwd);
        $this->project->cleanup();
    }

    public function test_command_supports_dry_run_and_apply_modes(): void
    {
        $this->writeFile('_import/precanonical/marked-archive.md', implode("\n", [
            'P@@@@@@@@@@@@@@@@',
            'NAME: Opening context',
            'Archive context.',
            'S@@@@@@@@@@@@@@@@',
            'NAME: 0B — Second Foundation',
            'Spec body.',
            'R@@@@@@@@@@@@@@@@',
            'NAME: 0B - Second Foundation',
            'Result body.',
            '',
        ]));

        $report = $this->runCommand([
            'foundry',
            'precanonical:import',
            '--source=_import/precanonical/marked-archive.md',
            '--json',
        ]);

        $this->assertSame(0, $report['status']);
        $this->assertTrue($report['payload']['dry_run']);
        $this->assertSame(1, $report['payload']['summary']['spec_blocks']);
        $this->assertSame(1, $report['payload']['summary']['paired_result_blocks']);
        $this->assertFileDoesNotExist($this->project->root . '/Modules/PreCanonical/specs/000.002-second-foundation.md');

        $apply = $this->runCommand([
            'foundry',
            'precanonical:import',
            '--source=_import/precanonical/marked-archive.md',
            '--apply',
            '--json',
        ]);

        $this->assertSame(0, $apply['status']);
        $this->assertFalse($apply['payload']['dry_run']);
        $this->assertSame(1, $apply['payload']['summary']['spec_blocks']);
        $this->assertFileExists($this->project->root . '/Modules/PreCanonical/specs/000.002-second-foundation.md');
        $this->assertFileExists($this->project->root . '/Modules/PreCanonical/plans/000.002-second-foundation.md');
        $this->assertFileExists($this->project->root . '/Modules/PreCanonical/pre-canonical.md');
    }

    public function test_command_returns_structured_errors_for_invalid_inputs(): void
    {
        $blank = $this->runCommand([
            'foundry',
            'precanonical:import',
            '--source=',
            '--json',
        ]);
        $this->assertSame(1, $blank['status']);
        $this->assertSame('CLI_PRECANONICAL_IMPORT_SOURCE_REQUIRED', $blank['payload']['error']['code']);

        $missing = $this->runCommand([
            'foundry',
            'precanonical:import',
            '--source=missing.md',
            '--json',
        ]);
        $this->assertSame(1, $missing['status']);
        $this->assertSame('PRECANONICAL_ARCHIVE_SOURCE_MISSING', $missing['payload']['error']['code']);
    }

    /**
     * @param array<int,string> $argv
     * @return array{status:int,payload:array<string,mixed>}
     */
    private function runCommand(array $argv): array
    {
        ob_start();
        $status = (new Application())->run($argv);
        $output = ob_get_clean() ?: '';

        /** @var array<string,mixed> $payload */
        $payload = json_decode($output, true, 512, JSON_THROW_ON_ERROR);

        return ['status' => $status, 'payload' => $payload];
    }

    private function writeFile(string $relativePath, string $contents): void
    {
        $path = $this->project->root . '/' . $relativePath;
        $directory = dirname($path);
        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        file_put_contents($path, $contents);
    }
}
