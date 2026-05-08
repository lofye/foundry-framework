<?php

declare(strict_types=1);

namespace Foundry\Tests\Integration;

use Foundry\CLI\Application;
use Foundry\Tests\Fixtures\TempProject;
use PHPUnit\Framework\TestCase;

final class CLIHistoricalSpecsContextCommandTest extends TestCase
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

    public function test_command_reports_and_applies_historical_module_context(): void
    {
        $this->writeImportedSpec('LegacyModule', '001-recovered-runtime');

        $preview = $this->runCommand([
            'foundry',
            'historical-specs:context',
            '--module=LegacyModule',
            '--json',
        ]);

        $this->assertSame(0, $preview['status']);
        $this->assertTrue($preview['payload']['dry_run']);
        $this->assertSame(1, $preview['payload']['summary']['modules']);
        $this->assertFileDoesNotExist($this->project->root . '/Modules/LegacyModule/legacy-module.md');

        $apply = $this->runCommand([
            'foundry',
            'historical-specs:context',
            '--module=LegacyModule',
            '--apply',
            '--json',
        ]);

        $this->assertSame(0, $apply['status']);
        $this->assertFalse($apply['payload']['dry_run']);
        $this->assertSame(3, $apply['payload']['summary']['written']);
        $this->assertFileExists($this->project->root . '/Modules/LegacyModule/legacy-module.md');

        $verify = $this->runCommand([
            'foundry',
            'verify',
            'context',
            '--feature=legacy-module',
            '--json',
        ]);

        $this->assertSame(0, $verify['status']);
        $this->assertSame('pass', $verify['payload']['status']);
        $this->assertSame('ok', $verify['payload']['doctor_status']);
        $this->assertSame('ok', $verify['payload']['alignment_status']);
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

    private function writeImportedSpec(string $module, string $name): void
    {
        $this->writeFile('Modules/' . $module . '/specs/' . $name . '.md', "# Execution Spec: {$name}\n\n## Historical Import Note\n\nThis spec was imported from archived pre-repository implementation records. Details marked inferred should be treated as lower-confidence historical reconstruction.\n\n## Purpose\n\nRecover context.\n");
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
