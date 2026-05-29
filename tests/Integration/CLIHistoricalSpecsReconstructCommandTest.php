<?php

declare(strict_types=1);

namespace Foundry\Tests\Integration;

use Foundry\CLI\Application;
use Foundry\Tests\Fixtures\TempProject;
use PHPUnit\Framework\TestCase;

final class CLIHistoricalSpecsReconstructCommandTest extends TestCase
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

    public function test_command_generates_reconstruction_notes_and_log_entries_that_validate(): void
    {
        $this->writeImportedSpec('LegacyModule', '001-recovered-runtime');

        $context = $this->runCommand([
            'foundry',
            'historical-specs:context',
            '--module=LegacyModule',
            '--apply',
            '--json',
        ]);
        $this->assertSame(0, $context['status']);

        $preview = $this->runCommand([
            'foundry',
            'historical-specs:reconstruct',
            '--module=LegacyModule',
            '--json',
        ]);

        $this->assertSame(0, $preview['status']);
        $this->assertTrue($preview['payload']['dry_run']);
        $this->assertSame(1, $preview['payload']['summary']['imported_specs']);
        $this->assertFileDoesNotExist($this->project->root . '/Modules/LegacyModule/outcomes/001-recovered-runtime.md');

        $apply = $this->runCommand([
            'foundry',
            'historical-specs:reconstruct',
            '--module=LegacyModule',
            '--apply',
            '--json',
        ]);

        $this->assertSame(0, $apply['status']);
        $this->assertSame(1, $apply['payload']['summary']['notes_created']);
        $this->assertSame(1, $apply['payload']['summary']['log_entries_appended']);
        $this->assertFileExists($this->project->root . '/Modules/LegacyModule/outcomes/001-recovered-runtime.md');

        $validate = $this->runCommand(['foundry', 'spec:validate', '--json']);
        $this->assertSame(0, $validate['status']);
        $this->assertTrue($validate['payload']['ok']);
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
        $this->writeFile('Modules/' . $module . '/specs/' . $name . '.md', "# Execution Spec: {$name}\n\n## Historical Import Note\n\nThis spec was imported from archived pre-repository implementation records. Details marked inferred should be treated as lower-confidence historical reconstruction.\n\nRESULT\nImplemented src/Legacy/Runtime.php.\nphp vendor/bin/phpunit passed.\n");
        $this->writeFile('src/Legacy/Runtime.php', "<?php\n");
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
