<?php

declare(strict_types=1);

namespace Foundry\Tests\Unit;

use Foundry\FeatureSystem\PreCanonicalArchiveImporter;
use Foundry\Support\FoundryError;
use Foundry\Support\Paths;
use Foundry\Tests\Fixtures\TempProject;
use PHPUnit\Framework\TestCase;

final class PreCanonicalArchiveImporterTest extends TestCase
{
    private TempProject $project;

    protected function setUp(): void
    {
        $this->project = new TempProject();
        $this->writeFile('Modules/implementation.log', "# Implementation Log\n");
    }

    protected function tearDown(): void
    {
        $this->project->cleanup();
    }

    public function test_dry_run_parses_blocks_pairs_dash_variant_results_and_writes_nothing(): void
    {
        $this->writeFile('archive.md', implode("\n", [
            'P@@@@@@@@@@@@@@@@',
            'NAME: Era setup',
            '',
            'Roadmap context.',
            'S@@@@@@@@@@@@@@@@',
            'NAME: 0A — Foundational Compiler Layer',
            '',
            'Spec body.',
            'R@@@@@@@@@@@@@@@@',
            'NAME: 0A - Foundational Compiler Layer',
            '',
            'Result body.',
            '',
        ]));

        $payload = $this->importer()->import('archive.md', 'PreCanonical', false, false);

        $this->assertTrue($payload['dry_run']);
        $this->assertSame(1, $payload['summary']['spec_blocks']);
        $this->assertSame(1, $payload['summary']['result_blocks']);
        $this->assertSame(1, $payload['summary']['preamble_blocks']);
        $this->assertSame(1, $payload['summary']['paired_result_blocks']);
        $this->assertSame(1, $payload['summary']['associated_preamble_blocks']);
        $this->assertSame('000.001', $payload['specs'][0]['canonical_id']);
        $this->assertSame('Modules/PreCanonical/specs/000.001-foundational-compiler-layer.md', $payload['specs'][0]['spec_path']);
        $this->assertFileDoesNotExist($this->project->root . '/Modules/PreCanonical/specs/000.001-foundational-compiler-layer.md');
    }

    public function test_apply_writes_specs_plans_context_and_log_once(): void
    {
        $this->writeFile('archive.md', implode("\n", [
            'S@@@@@@@@@@@@@@@@',
            'NAME: 19FB — Spec Freeze 1.0.0',
            '',
            'Original spec body.',
            'R@@@@@@@@@@@@@@@@',
            'NAME: 19FB — Spec Freeze 1.0.0',
            '',
            'Verification passed.',
            '',
        ]));

        $first = $this->importer()->import('archive.md', 'PreCanonical', true, false);
        $second = $this->importer()->import('archive.md', 'PreCanonical', true, false);

        $specPath = $this->project->root . '/Modules/PreCanonical/specs/019.006.002-spec-freeze-1-0-0.md';
        $planPath = $this->project->root . '/Modules/PreCanonical/plans/019.006.002-spec-freeze-1-0-0.md';
        $log = (string) file_get_contents($this->project->root . '/Modules/implementation.log');

        $this->assertSame(6, $first['summary']['written']);
        $this->assertSame(0, $second['summary']['written']);
        $this->assertFileExists($specPath);
        $this->assertFileExists($planPath);
        $this->assertFileExists($this->project->root . '/Modules/PreCanonical/pre-canonical.spec.md');
        $this->assertStringStartsWith("# Execution Spec: 019.006.002-spec-freeze-1-0-0\n", (string) file_get_contents($specPath));
        $this->assertStringContainsString("## Historical Implementation Evidence\n\n### Result Block 1", (string) file_get_contents($planPath));
        $this->assertSame(1, substr_count($log, '- spec: Modules/PreCanonical/specs/019.006.002-spec-freeze-1-0-0.md'));
    }

    public function test_id_mapping_handles_mixed_alphanumeric_and_hyphen_suffixes(): void
    {
        $this->writeFile('archive.md', implode("\n", [
            'S@@@@@@@@@@@@@@@@',
            'NAME: 30C-2 — Monetization Realignment',
            'Body.',
            'S@@@@@@@@@@@@@@@@',
            'NAME: 35D7C — Contexting',
            'Body.',
            '',
        ]));

        $payload = $this->importer()->import('archive.md', 'PreCanonical', false, false);

        $this->assertSame('030.003.002', $payload['specs'][0]['canonical_id']);
        $this->assertSame('035.004.007.003', $payload['specs'][1]['canonical_id']);
    }

    public function test_multiple_result_blocks_are_preserved_in_source_order(): void
    {
        $this->writeFile('archive.md', implode("\n", [
            'S@@@@@@@@@@@@@@@@',
            'NAME: 35D-2 — Contexting Follow-up',
            'Spec.',
            'R@@@@@@@@@@@@@@@@',
            'NAME: 35D-2 — Contexting Follow-up',
            'First result.',
            'R@@@@@@@@@@@@@@@@',
            'NAME: 35D-2 — Contexting Follow-up',
            'Second result.',
            '',
        ]));

        $this->importer()->import('archive.md', 'PreCanonical', true, false);

        $plan = (string) file_get_contents($this->project->root . '/Modules/PreCanonical/plans/035.004.002-contexting-follow-up.md');
        $this->assertStringContainsString("### Result Block 1\n\n- Name: `35D-2 — Contexting Follow-up`\n\nFirst result.", $plan);
        $this->assertStringContainsString("### Result Block 2\n\n- Name: `35D-2 — Contexting Follow-up`\n\nSecond result.", $plan);
    }

    public function test_trailing_preamble_is_preserved_as_global_context(): void
    {
        $this->writeFile('archive.md', implode("\n", [
            'S@@@@@@@@@@@@@@@@',
            'NAME: 1 — First Spec',
            'Spec.',
            'P@@@@@@@@@@@@@@@@',
            'NAME: Unassigned roadmap',
            'Global roadmap.',
            '',
        ]));

        $this->importer()->import('archive.md', 'PreCanonical', true, false);

        $state = (string) file_get_contents($this->project->root . '/Modules/PreCanonical/pre-canonical.md');
        $plan = (string) file_get_contents($this->project->root . '/Modules/PreCanonical/plans/001-first-spec.md');
        $log = (string) file_get_contents($this->project->root . '/Modules/implementation.log');
        $this->assertStringContainsString('- Global preamble 1: `Unassigned roadmap`', $state);
        $this->assertStringContainsString('No marked preamble block was associated with this spec.', $plan);
        $this->assertSame(1, substr_count($log, '- spec: Modules/PreCanonical/specs/001-first-spec.md'));
    }

    public function test_fails_for_orphan_result_block(): void
    {
        $this->writeFile('archive.md', implode("\n", [
            'S@@@@@@@@@@@@@@@@',
            'NAME: 1 — First Spec',
            'Spec.',
            'R@@@@@@@@@@@@@@@@',
            'NAME: 2 — Other Spec',
            'Result.',
            '',
        ]));

        $this->assertFoundryError(
            'PRECANONICAL_ARCHIVE_ORPHAN_RESULT_BLOCK',
            fn() => $this->importer()->import('archive.md', 'PreCanonical', false, false),
        );
    }

    public function test_fails_for_duplicate_spec_name_with_different_body(): void
    {
        $this->writeFile('archive.md', implode("\n", [
            'S@@@@@@@@@@@@@@@@',
            'NAME: 1 — First Spec',
            'Spec A.',
            'S@@@@@@@@@@@@@@@@',
            'NAME: 1 - First Spec',
            'Spec B.',
            '',
        ]));

        $this->assertFoundryError(
            'PRECANONICAL_ARCHIVE_DUPLICATE_SPEC_NAME',
            fn() => $this->importer()->import('archive.md', 'PreCanonical', false, false),
        );
    }

    public function test_fails_for_missing_name_and_malformed_legacy_id(): void
    {
        $this->writeFile('missing-name.md', "S@@@@@@@@@@@@@@@@\nSpec without name.\n");
        $this->assertFoundryError(
            'PRECANONICAL_ARCHIVE_BLOCK_NAME_MISSING',
            fn() => $this->importer()->import('missing-name.md', 'PreCanonical', false, false),
        );

        $this->writeFile('empty-name.md', "S@@@@@@@@@@@@@@@@\nNAME:   \nSpec without name.\n");
        $this->assertFoundryError(
            'PRECANONICAL_ARCHIVE_BLOCK_NAME_MISSING',
            fn() => $this->importer()->import('empty-name.md', 'PreCanonical', false, false),
        );

        $this->writeFile('bad-id.md', "S@@@@@@@@@@@@@@@@\nNAME: Alpha — No numeric legacy id\nBody.\n");
        $this->assertFoundryError(
            'PRECANONICAL_ARCHIVE_LEGACY_ID_INVALID',
            fn() => $this->importer()->import('bad-id.md', 'PreCanonical', false, false),
        );
    }

    public function test_conflict_requires_force_and_force_replaces_generated_artifact(): void
    {
        $this->writeFile('archive.md', "S@@@@@@@@@@@@@@@@\nNAME: 1 — First Spec\nSpec.\n");
        $this->writeFile('Modules/PreCanonical/specs/001-first-spec.md', "# Execution Spec: 001-first-spec\n\nDifferent.\n");

        $this->assertFoundryError(
            'PRECANONICAL_ARCHIVE_OUTPUT_CONFLICT',
            fn() => $this->importer()->import('archive.md', 'PreCanonical', true, false),
        );

        $dryRun = $this->importer()->import('archive.md', 'PreCanonical', false, true);
        $this->assertSame('would_replace', $dryRun['artifacts'][0]['action']);

        $payload = $this->importer()->import('archive.md', 'PreCanonical', true, true);
        $this->assertSame(1, $payload['summary']['replaced']);
        $this->assertStringContainsString('## Original Pre-Canonical Spec', (string) file_get_contents($this->project->root . '/Modules/PreCanonical/specs/001-first-spec.md'));
    }

    public function test_fails_for_invalid_target_module_name(): void
    {
        $this->writeFile('archive.md', "S@@@@@@@@@@@@@@@@\nNAME: 1 — First Spec\nSpec.\n");

        $this->assertFoundryError(
            'PRECANONICAL_ARCHIVE_TARGET_MODULE_INVALID',
            fn() => $this->importer()->import('archive.md', 'pre-canonical', false, false),
        );
    }

    private function importer(): PreCanonicalArchiveImporter
    {
        return new PreCanonicalArchiveImporter(new Paths($this->project->root));
    }

    private function assertFoundryError(string $code, callable $callback): void
    {
        try {
            $callback();
            self::fail('Expected FoundryError with code ' . $code . '.');
        } catch (FoundryError $error) {
            $this->assertSame($code, $error->errorCode);
        }
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
