<?php

declare(strict_types=1);

namespace Foundry\Tests\Integration;

use Foundry\CLI\Application;
use Foundry\Tests\Fixtures\TempProject;
use PHPUnit\Framework\TestCase;

final class CLIHistoricalSpecsExtractCommandTest extends TestCase
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

    public function test_command_supports_dry_run_and_apply_modes(): void
    {
        $this->writeRaw(
            '_import/raw-historical-specs/notes/specs.md',
            "Spec 42A: Auth\nTitle: Auth contracts\nPurpose: Deterministic extraction.\n",
        );

        $dryRun = $this->runCommand([
            'foundry',
            'historical-specs:extract',
            '--source=_import/raw-historical-specs',
            '--target=_import/historical-specs',
            '--dry-run',
            '--json',
        ]);

        $this->assertSame(0, $dryRun['status']);
        $this->assertTrue($dryRun['payload']['dry_run']);
        $this->assertSame(1, $dryRun['payload']['summary']['files_scanned']);
        $this->assertSame(1, $dryRun['payload']['summary']['candidates']);
        $this->assertSame(0, $dryRun['payload']['summary']['written']);
        $this->assertDirectoryDoesNotExist($this->project->root . '/_import/historical-specs/candidate-001');

        $apply = $this->runCommand([
            'foundry',
            'historical-specs:extract',
            '--source=_import/raw-historical-specs',
            '--target=_import/historical-specs',
            '--json',
        ]);

        $this->assertSame(0, $apply['status']);
        $this->assertFalse($apply['payload']['dry_run']);
        $this->assertSame(1, $apply['payload']['summary']['written']);
        $this->assertFileExists($this->project->root . '/_import/historical-specs/candidate-001/spec.md');
        $this->assertFileExists($this->project->root . '/_import/historical-specs/candidate-001/source.md');
        $this->assertFileExists($this->project->root . '/_import/historical-specs/candidate-001/metadata.json');
    }

    public function test_command_returns_structured_error_for_missing_source_directory(): void
    {
        $result = $this->runCommand([
            'foundry',
            'historical-specs:extract',
            '--source=_import/missing',
            '--target=_import/historical-specs',
            '--json',
        ]);

        $this->assertSame(1, $result['status']);
        $this->assertSame('HISTORICAL_SPECS_SOURCE_DIRECTORY_MISSING', $result['payload']['error']['code']);
    }

    public function test_command_rejects_blank_source_argument(): void
    {
        $result = $this->runCommand([
            'foundry',
            'historical-specs:extract',
            '--source=',
            '--target=_import/historical-specs',
            '--json',
        ]);

        $this->assertSame(1, $result['status']);
        $this->assertSame('CLI_HISTORICAL_SPECS_SOURCE_REQUIRED', $result['payload']['error']['code']);
    }

    public function test_command_rejects_blank_target_argument(): void
    {
        $result = $this->runCommand([
            'foundry',
            'historical-specs:extract',
            '--source=_import/raw-historical-specs',
            '--target=',
            '--json',
        ]);

        $this->assertSame(1, $result['status']);
        $this->assertSame('CLI_HISTORICAL_SPECS_TARGET_REQUIRED', $result['payload']['error']['code']);
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

    private function writeRaw(string $relativePath, string $contents): void
    {
        $path = $this->project->root . '/' . $relativePath;
        $directory = dirname($path);
        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        file_put_contents($path, $contents);
    }
}
