<?php

declare(strict_types=1);

namespace Foundry\Tests\Integration;

use Foundry\CLI\Application;
use Foundry\Tests\Fixtures\TempProject;
use PHPUnit\Framework\TestCase;

final class CLIHistoricalSpecsImportCommandTest extends TestCase
{
    private TempProject $project;
    private string $cwd;

    protected function setUp(): void
    {
        $this->project = new TempProject();
        $this->cwd = getcwd() ?: '.';
        chdir($this->project->root);
        $this->writeFile('Modules/FeatureSystem/feature-system.spec.md', "# Feature Spec: feature-system\n");
    }

    protected function tearDown(): void
    {
        chdir($this->cwd);
        $this->project->cleanup();
    }

    public function test_command_supports_report_and_apply_modes(): void
    {
        $this->writeArchiveCandidate('spec-001', [
            'module' => 'FeatureSystem',
            'spec_id' => '001',
            'slug' => 'historical-import',
            'implemented' => true,
            'source_confidence' => 'high',
        ], "Purpose: Historical import.\n");

        $report = $this->runCommand([
            'foundry',
            'historical-specs:import',
            '--source=historical-specs',
            '--dry-run',
            '--json',
        ]);

        $this->assertSame(0, $report['status']);
        $this->assertTrue($report['payload']['dry_run']);
        $this->assertSame(1, $report['payload']['summary']['importable']);
        $this->assertFileDoesNotExist($this->project->root . '/Modules/FeatureSystem/specs/001-historical-import.md');

        $apply = $this->runCommand([
            'foundry',
            'historical-specs:import',
            '--source=historical-specs',
            '--apply',
            '--json',
        ]);

        $this->assertSame(0, $apply['status']);
        $this->assertFalse($apply['payload']['dry_run']);
        $this->assertSame(1, $apply['payload']['summary']['written']);
        $this->assertFileExists($this->project->root . '/Modules/FeatureSystem/specs/001-historical-import.md');
    }

    public function test_command_returns_structured_errors_for_invalid_inputs(): void
    {
        $blank = $this->runCommand([
            'foundry',
            'historical-specs:import',
            '--source=',
            '--json',
        ]);
        $this->assertSame(1, $blank['status']);
        $this->assertSame('CLI_HISTORICAL_SPECS_IMPORT_SOURCE_REQUIRED', $blank['payload']['error']['code']);

        $missing = $this->runCommand([
            'foundry',
            'historical-specs:import',
            '--source=missing',
            '--json',
        ]);
        $this->assertSame(1, $missing['status']);
        $this->assertSame('HISTORICAL_SPEC_IMPORT_SOURCE_DIRECTORY_MISSING', $missing['payload']['error']['code']);
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

    /**
     * @param array<string,mixed> $metadata
     */
    private function writeArchiveCandidate(string $directory, array $metadata, string $specText): void
    {
        $this->writeFile('historical-specs/' . $directory . '/spec.md', $specText);
        $this->writeFile(
            'historical-specs/' . $directory . '/metadata.json',
            json_encode($metadata, \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES | \JSON_THROW_ON_ERROR) . "\n",
        );
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
