<?php

declare(strict_types=1);

namespace Foundry\Tests\Unit;

use Foundry\FeatureSystem\HistoricalSpecArchiveImporter;
use Foundry\Support\FoundryError;
use Foundry\Support\Paths;
use Foundry\Tests\Fixtures\TempProject;
use PHPUnit\Framework\TestCase;

final class HistoricalSpecArchiveImporterTest extends TestCase
{
    private TempProject $project;

    protected function setUp(): void
    {
        $this->project = new TempProject();
        $this->writeRawFile('Modules/FeatureSystem/feature-system.spec.md', "# Feature Spec: feature-system\n");
    }

    protected function tearDown(): void
    {
        $this->project->cleanup();
    }

    public function test_dry_run_reports_importable_completed_spec_without_writing(): void
    {
        $this->writeArchiveCandidate('spec-001', [
            'module' => 'FeatureSystem',
            'spec_id' => '001',
            'slug' => 'historical-import',
            'implemented' => true,
            'source_confidence' => 'high',
        ], "# Legacy Spec\n\nPurpose: Preserve history.\n");

        $payload = $this->importer()->import('historical-specs', false, true, false);

        $this->assertSame('ok', $payload['status']);
        $this->assertTrue($payload['dry_run']);
        $this->assertSame(1, $payload['summary']['candidates']);
        $this->assertSame(1, $payload['summary']['importable']);
        $this->assertSame(0, $payload['summary']['written']);
        $this->assertSame('Modules/FeatureSystem/specs/001-historical-import.md', $payload['candidates'][0]['destination_path']);
        $this->assertSame('importable', $payload['candidates'][0]['status']);
        $this->assertFileDoesNotExist($this->project->root . '/Modules/FeatureSystem/specs/001-historical-import.md');
    }

    public function test_apply_writes_completed_spec_with_canonical_heading_and_import_note(): void
    {
        $this->writeArchiveCandidate('spec-001', [
            'module' => 'FeatureSystem',
            'spec_id' => '001',
            'slug' => 'historical-import',
            'implemented' => true,
            'source_confidence' => 'medium',
        ], "# Legacy Spec\n\nPurpose: Preserve original text.\n");

        $payload = $this->importer()->import('historical-specs', true, false, false);

        $path = $this->project->root . '/Modules/FeatureSystem/specs/001-historical-import.md';
        $this->assertSame(1, $payload['summary']['written']);
        $this->assertSame('written', $payload['candidates'][0]['action']);
        $this->assertFileExists($path);

        $contents = (string) file_get_contents($path);
        $this->assertStringStartsWith("# Execution Spec: 001-historical-import\n\n## Historical Import Note\n", $contents);
        $this->assertStringContainsString("# Legacy Spec\n\nPurpose: Preserve original text.\n", $contents);
    }

    public function test_exact_duplicate_is_reported_as_already_imported(): void
    {
        $this->writeArchiveCandidate('spec-001', [
            'module' => 'FeatureSystem',
            'spec_id' => '001',
            'slug' => 'historical-import',
            'implemented' => true,
        ], "Purpose: Same.\n");

        $first = $this->importer()->import('historical-specs', true, false, false);
        $second = $this->importer()->import('historical-specs', false, true, false);

        $this->assertSame(1, $first['summary']['written']);
        $this->assertSame(1, $second['summary']['already_imported']);
        $this->assertSame('already_imported', $second['candidates'][0]['status']);
        $this->assertNull($second['candidates'][0]['code']);
    }

    public function test_conflicting_destination_is_not_overwritten(): void
    {
        $this->writeArchiveCandidate('spec-001', [
            'module' => 'FeatureSystem',
            'spec_id' => '001',
            'slug' => 'historical-import',
            'implemented' => true,
        ], "Purpose: Import text.\n");
        $this->writeRawFile('Modules/FeatureSystem/specs/001-historical-import.md', "# Execution Spec: 001-historical-import\n\nExisting content.\n");

        $payload = $this->importer()->import('historical-specs', true, false, false);

        $this->assertSame(1, $payload['summary']['conflicts']);
        $this->assertSame(0, $payload['summary']['written']);
        $this->assertSame('HISTORICAL_SPEC_IMPORT_CONFLICT', $payload['candidates'][0]['code']);
        $this->assertSame("# Execution Spec: 001-historical-import\n\nExisting content.\n", file_get_contents($this->project->root . '/Modules/FeatureSystem/specs/001-historical-import.md'));
    }

    public function test_force_allows_conflicting_destination_replacement(): void
    {
        $this->writeArchiveCandidate('spec-001', [
            'module' => 'FeatureSystem',
            'spec_id' => '001',
            'slug' => 'historical-import',
            'implemented' => true,
        ], "Purpose: Replacement text.\n");
        $this->writeRawFile('Modules/FeatureSystem/specs/001-historical-import.md', "# Execution Spec: 001-historical-import\n\nExisting content.\n");

        $payload = $this->importer()->import('historical-specs', true, false, true);

        $contents = (string) file_get_contents($this->project->root . '/Modules/FeatureSystem/specs/001-historical-import.md');
        $this->assertSame(1, $payload['summary']['written']);
        $this->assertContains('Force enabled; destination content will be replaced in apply mode.', $payload['candidates'][0]['notes']);
        $this->assertStringContainsString("Purpose: Replacement text.\n", $contents);
        $this->assertStringNotContainsString('Existing content.', $contents);
    }


    public function test_missing_metadata_reports_unmapped_candidate(): void
    {
        $this->writeRawFile('historical-specs/spec-001/spec.md', "Spec text.\n");

        $payload = $this->importer()->import('historical-specs', false, true, false);

        $this->assertSame(1, $payload['summary']['unmapped']);
        $this->assertSame('unmapped', $payload['candidates'][0]['status']);
        $this->assertSame('HISTORICAL_SPEC_IMPORT_UNMAPPED', $payload['candidates'][0]['code']);
    }

    public function test_malformed_metadata_reports_invalid_metadata(): void
    {
        $this->writeRawFile('historical-specs/spec-001/spec.md', "Spec text.\n");
        $this->writeRawFile('historical-specs/spec-001/metadata.json', "{not json}\n");

        $payload = $this->importer()->import('historical-specs', false, true, false);

        $this->assertSame(1, $payload['summary']['invalid_metadata']);
        $this->assertSame('invalid_metadata', $payload['candidates'][0]['status']);
        $this->assertSame('HISTORICAL_SPEC_IMPORT_INVALID_METADATA', $payload['candidates'][0]['code']);
    }

    public function test_uncertain_status_places_candidate_under_drafts(): void
    {
        $this->writeArchiveCandidate('spec-001', [
            'module' => 'FeatureSystem',
            'spec_id' => '002',
            'slug' => 'uncertain-import',
            'implemented' => false,
        ], "Purpose: Review before active import.\n");

        $payload = $this->importer()->import('historical-specs', true, false, false);

        $this->assertSame('Modules/FeatureSystem/specs/drafts/002-uncertain-import.md', $payload['candidates'][0]['destination_path']);
        $this->assertFileExists($this->project->root . '/Modules/FeatureSystem/specs/drafts/002-uncertain-import.md');
    }

    public function test_website_specs_are_skipped_even_when_metadata_maps_to_framework_module(): void
    {
        $this->writeArchiveCandidate('Foundry-Spec-19L-WS', [
            'module' => 'FeatureSystem',
            'spec_id' => '019',
            'slug' => 'website-only',
            'implemented' => true,
            'original_file' => '_import/historical-specs/Foundry-Spec-19L-WS.md',
        ], "Purpose: Website-only behavior.\n");

        $payload = $this->importer()->import('historical-specs', true, false, false);

        $this->assertSame(1, $payload['summary']['skipped_website']);
        $this->assertSame(0, $payload['summary']['written']);
        $this->assertSame('website_skipped', $payload['candidates'][0]['status']);
        $this->assertSame('HISTORICAL_SPEC_IMPORT_WEBSITE_SPEC_SKIPPED', $payload['candidates'][0]['code']);
        $this->assertFileDoesNotExist($this->project->root . '/Modules/FeatureSystem/specs/019-website-only.md');
    }

    public function test_report_ordering_is_deterministic(): void
    {
        $this->writeArchiveCandidate('z-last', [
            'module' => 'FeatureSystem',
            'spec_id' => '002',
            'slug' => 'second',
            'implemented' => false,
        ], "Second.\n");
        $this->writeArchiveCandidate('a-first', [
            'module' => 'FeatureSystem',
            'spec_id' => '001',
            'slug' => 'first',
            'implemented' => false,
        ], "First.\n");

        $first = $this->importer()->import('historical-specs', false, true, false);
        $second = $this->importer()->import('historical-specs', false, true, false);

        $this->assertSame($first['candidates'], $second['candidates']);
        $this->assertSame('historical-specs/a-first', $first['candidates'][0]['source_directory']);
        $this->assertSame('historical-specs/z-last', $first['candidates'][1]['source_directory']);
    }

    public function test_import_fails_when_source_directory_is_missing(): void
    {
        $this->expectException(FoundryError::class);

        try {
            $this->importer()->import('historical-specs-missing', false, true, false);
        } catch (FoundryError $error) {
            $this->assertSame('HISTORICAL_SPEC_IMPORT_SOURCE_DIRECTORY_MISSING', $error->errorCode);
            throw $error;
        }
    }

    private function importer(): HistoricalSpecArchiveImporter
    {
        return new HistoricalSpecArchiveImporter(new Paths($this->project->root));
    }

    /**
     * @param array<string,mixed> $metadata
     */
    private function writeArchiveCandidate(string $directory, array $metadata, string $specText): void
    {
        $this->writeRawFile('historical-specs/' . $directory . '/spec.md', $specText);
        $this->writeRawFile(
            'historical-specs/' . $directory . '/metadata.json',
            json_encode($metadata, \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES | \JSON_THROW_ON_ERROR) . "\n",
        );
    }

    private function writeRawFile(string $relativePath, string $contents): void
    {
        $path = $this->project->root . '/' . $relativePath;
        $directory = dirname($path);
        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        file_put_contents($path, $contents);
    }
}
