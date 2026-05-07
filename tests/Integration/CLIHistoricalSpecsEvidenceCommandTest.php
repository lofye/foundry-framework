<?php

declare(strict_types=1);

namespace Foundry\Tests\Integration;

use Foundry\CLI\Application;
use Foundry\Tests\Fixtures\TempProject;
use PHPUnit\Framework\TestCase;

final class CLIHistoricalSpecsEvidenceCommandTest extends TestCase
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

    public function test_command_builds_preview_and_write_modes(): void
    {
        $this->writeFile('_import/historical-specs/Foundry-Spec-1.md', "Spec 1\nTitle: One\n");

        $preview = $this->runCommand([
            'foundry',
            'historical-specs:evidence',
            '--source=_import/historical-specs',
            '--json',
        ]);

        $this->assertSame(0, $preview['status']);
        $this->assertTrue($preview['payload']['dry_run']);
        $this->assertFalse($preview['payload']['outputs']['written']);
        $this->assertArrayHasKey('canonical_transition', $preview['payload']);
        $this->assertArrayHasKey('counts', $preview['payload']);
        $this->assertArrayHasKey('era', $preview['payload']['candidates'][0]);
        $this->assertArrayHasKey('import_action', $preview['payload']['candidates'][0]);
        $this->assertFileDoesNotExist($this->project->root . '/_import/historical-specs/evidence-map.json');

        $write = $this->runCommand([
            'foundry',
            'historical-specs:evidence',
            '--source=_import/historical-specs',
            '--write',
            '--json',
        ]);

        $this->assertSame(0, $write['status']);
        $this->assertTrue($write['payload']['outputs']['written']);
        $this->assertFileExists($this->project->root . '/_import/historical-specs/evidence-map.json');
        $this->assertFileExists($this->project->root . '/_import/historical-specs/evidence-map.md');
    }

    public function test_command_supports_anchors_and_with_git_flags(): void
    {
        $this->writeFile('_import/historical-specs/Foundry-Spec-35D7C.md', "Spec 35D7C\nTitle: Auto planning\n");
        $this->writeFile('_import/historical-specs/import-anchors.json', json_encode([
            'anchors' => [[
                'legacy_label' => 'Spec35D7C',
                'canonical_module' => 'ContextPersistence',
                'canonical_spec_id' => '011',
                'canonical_slug' => 'auto-planning-from-canonical-feature-context',
                'confidence' => 'high',
                'notes' => 'Known anchor.',
            ]],
        ], JSON_THROW_ON_ERROR));

        $result = $this->runCommand([
            'foundry',
            'historical-specs:evidence',
            '--source=_import/historical-specs',
            '--anchors=_import/historical-specs/import-anchors.json',
            '--with-git',
            '--json',
        ]);

        $this->assertSame(0, $result['status']);
        $this->assertSame('ContextPersistence', $result['payload']['candidates'][0]['suggested_module']);
        $this->assertSame('canonical_existing', $result['payload']['candidates'][0]['era']);
        $this->assertArrayHasKey('git', $result['payload']['candidates'][0]);
    }

    public function test_command_rejects_blank_source_and_missing_source_directory(): void
    {
        $blank = $this->runCommand([
            'foundry',
            'historical-specs:evidence',
            '--source=',
            '--json',
        ]);
        $this->assertSame(1, $blank['status']);
        $this->assertSame('CLI_HISTORICAL_SPECS_EVIDENCE_SOURCE_REQUIRED', $blank['payload']['error']['code']);

        $missing = $this->runCommand([
            'foundry',
            'historical-specs:evidence',
            '--source=_import/missing',
            '--json',
        ]);
        $this->assertSame(1, $missing['status']);
        $this->assertSame('HISTORICAL_SPECS_EVIDENCE_SOURCE_DIRECTORY_MISSING', $missing['payload']['error']['code']);
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
