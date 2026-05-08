<?php

declare(strict_types=1);

namespace Foundry\Tests\Unit;

use Foundry\FeatureSystem\HistoricalSpecArchiveExtractor;
use Foundry\Support\FoundryError;
use Foundry\Support\Paths;
use Foundry\Tests\Fixtures\TempProject;
use PHPUnit\Framework\TestCase;

final class HistoricalSpecArchiveExtractorTest extends TestCase
{
    private TempProject $project;

    protected function setUp(): void
    {
        $this->project = new TempProject();
    }

    protected function tearDown(): void
    {
        $this->project->cleanup();
    }

    public function test_extracts_multiple_specs_from_single_file_with_deterministic_numbering(): void
    {
        $this->writeRawSpecFile('raw/spec-notes.md', <<<'TXT'
Spec 12A: User Identity
Title: Identity onboarding
Purpose: Keep auth deterministic.

Execution Spec: 35D-marketplace-auth
Title: Marketplace authentication
Purpose: Add deterministic auth contracts.
TXT);

        $payload = $this->extractor()->extract(
            '_import/raw-historical-specs',
            '_import/historical-specs',
            true,
        );

        $this->assertSame('ok', $payload['status']);
        $this->assertTrue($payload['dry_run']);
        $this->assertSame(1, $payload['summary']['files_scanned']);
        $this->assertSame(2, $payload['summary']['candidates']);
        $this->assertSame('candidate-001', $payload['candidates'][0]['candidate_id']);
        $this->assertSame('candidate-002', $payload['candidates'][1]['candidate_id']);
        $this->assertSame('Spec 12A', $payload['candidates'][0]['detected_spec_label']);
        $this->assertSame('35D-marketplace-auth', $payload['candidates'][1]['detected_spec_label']);
    }

    public function test_extract_reports_no_candidates_when_no_spec_markers_are_present(): void
    {
        $this->writeRawSpecFile('raw/notes.txt', "Random notes only.\nNo specs here.\n");

        $payload = $this->extractor()->extract(
            '_import/raw-historical-specs',
            '_import/historical-specs',
            true,
        );

        $this->assertSame(1, $payload['summary']['files_scanned']);
        $this->assertSame(0, $payload['summary']['candidates']);
        $this->assertSame([], $payload['candidates']);
    }

    public function test_extract_ignores_generated_evidence_maps_and_candidate_directories(): void
    {
        $this->writeRawSpecFile('raw/spec.md', "Spec 1: Real\nPurpose: Real candidate.\n");
        $this->writeRawSpecFile('evidence-map.md', "Spec 999: Generated report\nPurpose: Ignore me.\n");
        $this->writeRawSpecFile('candidate-001/source.md', "Spec 888: Generated candidate\nPurpose: Ignore me.\n");

        $payload = $this->extractor()->extract(
            '_import/raw-historical-specs',
            '_import/historical-specs',
            true,
        );

        $this->assertSame(1, $payload['summary']['files_scanned']);
        $this->assertSame(1, $payload['summary']['candidates']);
        $this->assertSame('Spec 1', $payload['candidates'][0]['detected_spec_label']);
    }

    public function test_extract_supports_weird_spec_headings_like_spec_35d_dash_2(): void
    {
        $this->writeRawSpecFile('raw/weird.md', <<<'TXT'
Spec 35D-2: Runtime Contracts
Purpose: Preserve deterministic behavior.
TXT);

        $payload = $this->extractor()->extract(
            '_import/raw-historical-specs',
            '_import/historical-specs',
            true,
        );

        $this->assertSame('Spec 35D-2', $payload['candidates'][0]['detected_spec_label']);
        $this->assertSame('high', $payload['candidates'][0]['confidence']);
    }

    public function test_extract_tracks_multi_segment_indices_and_result_detection(): void
    {
        $this->writeRawSpecFile('raw/multi.md', <<<'TXT'
Spec 10
Title: Segment one
RESULT:
Finished.

Spec 11
Title: Segment two
TXT);

        $payload = $this->extractor()->extract(
            '_import/raw-historical-specs',
            '_import/historical-specs',
            false,
        );

        $this->assertSame(2, $payload['summary']['candidates']);
        $this->assertSame(1, $payload['candidates'][0]['source_segment']);
        $this->assertSame(2, $payload['candidates'][0]['source_segments_total']);
        $this->assertTrue($payload['candidates'][0]['result_detected']);
        $this->assertFalse($payload['candidates'][1]['result_detected']);
        $this->assertFileExists($this->project->root . '/_import/historical-specs/candidate-001/result.md');
    }

    public function test_extract_deduplicates_slugs_for_duplicate_titles(): void
    {
        $this->writeRawSpecFile('raw/dupes.md', <<<'TXT'
Execution Spec: draft
Title: Shared title
Purpose: First.

Execution Spec: draft
Title: Shared title
Purpose: Second.
TXT);

        $payload = $this->extractor()->extract(
            '_import/raw-historical-specs',
            '_import/historical-specs',
            true,
        );

        $this->assertSame('draft-shared-title', $payload['candidates'][0]['suggested_slug']);
        $this->assertSame('draft-shared-title-2', $payload['candidates'][1]['suggested_slug']);
    }

    public function test_extract_rejects_title_purpose_fallback_without_spec_root(): void
    {
        $this->writeRawSpecFile('raw/fallback.txt', <<<'TXT'
Title: Legacy migration idea
Purpose: Capture old notes for manual review.
TXT);

        $payload = $this->extractor()->extract(
            '_import/raw-historical-specs',
            '_import/historical-specs',
            true,
        );

        $this->assertSame(0, $payload['summary']['candidates']);
        $this->assertSame([], $payload['candidates']);
    }

    public function test_extract_uses_legacy_filename_fallback_for_single_spec_file(): void
    {
        $this->writeRawSpecFile('raw/Foundry-Spec-30C-2.md', <<<'TXT'
Title: Historical module import
Purpose: Preserve archive import behavior.
Requirements: Keep output deterministic.
TXT);

        $payload = $this->extractor()->extract(
            '_import/raw-historical-specs',
            '_import/historical-specs',
            true,
        );

        $this->assertSame(1, $payload['summary']['candidates']);
        $this->assertSame('Spec 30C-2', $payload['candidates'][0]['detected_spec_label']);
        $this->assertSame('legacy_filename_single_spec', $payload['candidates'][0]['emission_reason']);
        $this->assertSame('probable', $payload['candidates'][0]['candidate_quality']);
    }

    public function test_extract_suppresses_section_fragments_and_embedded_prior_spec_references(): void
    {
        $this->writeRawSpecFile('raw/recap.md', <<<'TXT'
Spec 19A: CLI Entry
Purpose: Implement CLI entry contracts.
Requirements: Commands must remain deterministic.

Architecture (what it is)
Spec 19D established the foundations for foundry explain.
must:
introduced collectors and analyzers.

Spec 19B: Core Models
Purpose: Implement core models.
Requirements: Models must remain deterministic.
TXT);

        $payload = $this->extractor()->extract(
            '_import/raw-historical-specs',
            '_import/historical-specs',
            true,
        );

        $this->assertSame(2, $payload['summary']['candidates']);
        $this->assertSame('Spec 19A', $payload['candidates'][0]['detected_spec_label']);
        $this->assertSame('Spec 19B', $payload['candidates'][1]['detected_spec_label']);
        $this->assertSame([
            ['text' => 'Architecture (what it is)', 'reason' => 'section_fragment'],
            ['text' => 'Spec 19D established the foundations for foundry explain.', 'reason' => 'embedded_prior_spec_reference'],
            ['text' => 'must:', 'reason' => 'section_fragment'],
            ['text' => 'introduced collectors and analyzers.', 'reason' => 'section_fragment'],
        ], $payload['candidates'][0]['rejected_root_signals']);
    }

    public function test_extract_emits_result_only_content_as_supporting_evidence(): void
    {
        $this->writeRawSpecFile('raw/result-only.md', <<<'TXT'
RESULT:
Implementation completed in a prior session.
TXT);

        $payload = $this->extractor()->extract(
            '_import/raw-historical-specs',
            '_import/historical-specs',
            false,
        );

        $this->assertSame(1, $payload['summary']['candidates']);
        $this->assertSame('supporting_evidence', $payload['candidates'][0]['emission_reason']);
        $this->assertSame('supporting', $payload['candidates'][0]['candidate_quality']);
        $this->assertSame('low', $payload['candidates'][0]['result_association_confidence']);
        $this->assertFileExists($this->project->root . '/_import/historical-specs/candidate-001/result.md');
    }

    public function test_dry_run_writes_nothing_to_target_directory(): void
    {
        $this->writeRawSpecFile('raw/spec.md', "Spec 12: Demo\nPurpose: Demo.\n");

        $this->extractor()->extract(
            '_import/raw-historical-specs',
            '_import/historical-specs',
            true,
        );

        $this->assertDirectoryDoesNotExist($this->project->root . '/_import/historical-specs/candidate-001');
        $this->assertSame([], $this->candidateDirectories($this->project->root . '/_import/historical-specs'));
    }

    public function test_apply_writes_deterministic_candidate_files(): void
    {
        $this->writeRawSpecFile('raw/specs.md', <<<'TXT'
Spec 21: Demo
Title: Deterministic output
Purpose: Verify stable writes.
TXT);

        $first = $this->extractor()->extract(
            '_import/raw-historical-specs',
            '_import/historical-specs',
            false,
        );
        $second = $this->extractor()->extract(
            '_import/raw-historical-specs',
            '_import/historical-specs',
            false,
        );

        $this->assertSame($first['candidates'], $second['candidates']);
        $this->assertSame(1, $first['summary']['written']);

        $metadataPath = $this->project->root . '/_import/historical-specs/candidate-001/metadata.json';
        $specPath = $this->project->root . '/_import/historical-specs/candidate-001/spec.md';
        $sourcePath = $this->project->root . '/_import/historical-specs/candidate-001/source.md';

        $this->assertFileExists($metadataPath);
        $this->assertFileExists($specPath);
        $this->assertFileExists($sourcePath);

        $metadata = json_decode((string) file_get_contents($metadataPath), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('candidate-001', $metadata['candidate_id']);
        $this->assertSame('_import/raw-historical-specs/raw/specs.md', $metadata['original_file']);
        $this->assertSame('Spec 21', $metadata['detected_spec_label']);
        $this->assertSame('spec-21-deterministic-output', $metadata['suggested_slug']);
        $this->assertSame('unknown', $metadata['suggested_module']);
    }

    public function test_extract_fails_when_source_directory_is_missing(): void
    {
        $this->expectException(FoundryError::class);

        try {
            $this->extractor()->extract('_import/does-not-exist', '_import/historical-specs', true);
        } catch (FoundryError $error) {
            $this->assertSame('HISTORICAL_SPECS_SOURCE_DIRECTORY_MISSING', $error->errorCode);
            throw $error;
        }
    }

    private function extractor(): HistoricalSpecArchiveExtractor
    {
        return new HistoricalSpecArchiveExtractor(new Paths($this->project->root));
    }

    private function writeRawSpecFile(string $relativePath, string $contents): void
    {
        $path = $this->project->root . '/_import/raw-historical-specs/' . $relativePath;
        $directory = dirname($path);
        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        file_put_contents($path, $contents);
    }

    /**
     * @return list<string>
     */
    private function candidateDirectories(string $directory): array
    {
        if (!is_dir($directory)) {
            return [];
        }

        $entries = scandir($directory) ?: [];
        $matches = [];
        foreach ($entries as $entry) {
            if (preg_match('/^candidate-\d{3}$/', (string) $entry) !== 1) {
                continue;
            }

            $matches[] = (string) $entry;
        }

        sort($matches, \SORT_STRING);

        return $matches;
    }
}
