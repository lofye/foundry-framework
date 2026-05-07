<?php

declare(strict_types=1);

namespace Foundry\Tests\Unit;

use Foundry\FeatureSystem\HistoricalSpecEvidenceMapper;
use Foundry\Support\FoundryError;
use Foundry\Support\Paths;
use Foundry\Tests\Fixtures\TempProject;
use PHPUnit\Framework\TestCase;

final class HistoricalSpecEvidenceMapperTest extends TestCase
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

    public function test_build_parses_legacy_labels_and_order_keys_deterministically(): void
    {
        $this->writeSpecFile('Foundry-Spec-1.md', "Spec 1\nTitle: One\n");
        $this->writeSpecFile('Foundry-Spec-19A.md', "Spec 19A\nTitle: Nineteen A\n");
        $this->writeSpecFile('Foundry-Spec-19FB.md', "Spec 19FB\nTitle: Nineteen FB\n");
        $this->writeSpecFile('Foundry-Spec-30C-2.md', "Spec 30C-2\nTitle: Thirty C2\n");
        $this->writeSpecFile('Foundry-Spec-35D7C.md', "Spec 35D7C\nTitle: Thirty Five D7C\n");
        $this->writeSpecFile('Foundry-Spec-35D7JA.md', "Spec 35D7JA\nTitle: Thirty Five D7JA\n");
        $this->writeSpecFile('Foundry-Spec-Feature-Alignment-Pass-Skill.md', "Execution Spec: feature-alignment-pass-skill\nTitle: Alignment skill\n");

        $payload = $this->mapper()->build(
            sourcePath: '_import/historical-specs',
            anchorsPath: null,
            withGitEvidence: false,
            write: false,
            dryRun: true,
        );

        $rowsByLabel = [];
        foreach ($payload['candidates'] as $row) {
            $rowsByLabel[(string) $row['legacy_label']] = $row;
        }

        $this->assertSame('001', $rowsByLabel['Spec1']['legacy_order_key']);
        $this->assertSame('019.A', $rowsByLabel['Spec19A']['legacy_order_key']);
        $this->assertSame('019.F.B', $rowsByLabel['Spec19FB']['legacy_order_key']);
        $this->assertSame('030.C.002', $rowsByLabel['Spec30C-2']['legacy_order_key']);
        $this->assertSame('035.D.007.C', $rowsByLabel['Spec35D7C']['legacy_order_key']);
        $this->assertSame('035.D.007.J.A', $rowsByLabel['Spec35D7JA']['legacy_order_key']);

        $orderedLabels = array_map(
            static fn(array $candidate): string => (string) $candidate['legacy_label'],
            $payload['candidates'],
        );
        $this->assertSame('Spec1', $orderedLabels[0]);
        $this->assertSame('', end($orderedLabels));
    }

    public function test_build_handles_summary_file_as_supporting_evidence(): void
    {
        $this->writeSpecFile('Foundry-Spec-Summaries.md', "Summary notes only.\n");
        $this->writeSpecFile('Foundry-Spec-2.md', "Spec 2\nTitle: Two\n");

        $payload = $this->mapper()->build(
            sourcePath: '_import/historical-specs',
            anchorsPath: null,
            withGitEvidence: false,
            write: false,
            dryRun: true,
        );

        $this->assertContains('_import/historical-specs/Foundry-Spec-Summaries.md', $payload['supporting_evidence_files']);
        $rowsBySource = [];
        foreach ($payload['candidates'] as $row) {
            $rowsBySource[(string) $row['source_file']] = $row;
        }

        $this->assertSame('pre_canonical', $rowsBySource['_import/historical-specs/Foundry-Spec-2.md']['era']);
        $this->assertSame('supporting_evidence', $rowsBySource['_import/historical-specs/Foundry-Spec-Summaries.md']['era']);
        $this->assertSame('ignore_supporting', $rowsBySource['_import/historical-specs/Foundry-Spec-Summaries.md']['import_action']);
    }

    public function test_build_supports_multi_spec_segments_result_detection_and_filename_mismatch_note(): void
    {
        $this->writeSpecFile('Foundry-Spec-35D7G-015.md', <<<'TXT'
Spec 35D7G2
Title: Segment one
RESULT:
Completed output.

Spec 35D7H
Title: Segment two
TXT);

        $payload = $this->mapper()->build(
            sourcePath: '_import/historical-specs',
            anchorsPath: null,
            withGitEvidence: false,
            write: false,
            dryRun: true,
        );

        $this->assertCount(2, $payload['candidates']);
        $this->assertSame(1, $payload['candidates'][0]['source_segment']);
        $this->assertSame(2, $payload['candidates'][0]['source_segments_total']);
        $this->assertSame('Spec35D7G2', $payload['candidates'][0]['legacy_label']);
        $this->assertContains(
            'Filename label differs from internal segment label; internal heading used for candidate identity.',
            $payload['candidates'][0]['notes'],
        );
        $this->assertTrue($payload['candidates'][0]['result_detected']);
        $this->assertSame('candidate-001/result.md', $payload['candidates'][0]['result_file']);
        $this->assertFalse($payload['candidates'][1]['result_detected']);
    }

    public function test_anchors_affect_mapping_with_explicit_evidence_notes(): void
    {
        $this->writeSpecFile('Foundry-Spec-35D7C.md', "Spec 35D7C\nTitle: Auto planning\n");
        $this->writeRawFile('_import/historical-specs/import-anchors.json', json_encode([
            'anchors' => [[
                'legacy_label' => 'Spec35D7C',
                'canonical_module' => 'ContextPersistence',
                'canonical_spec_id' => '011',
                'canonical_slug' => 'auto-planning-from-canonical-feature-context',
                'confidence' => 'high',
                'notes' => 'Known anchor.',
            ]],
        ], JSON_THROW_ON_ERROR));

        $payload = $this->mapper()->build(
            sourcePath: '_import/historical-specs',
            anchorsPath: '_import/historical-specs/import-anchors.json',
            withGitEvidence: false,
            write: false,
            dryRun: true,
        );

        $candidate = $payload['candidates'][0];
        $this->assertSame('ContextPersistence', $candidate['suggested_module']);
        $this->assertSame('Modules/ContextPersistence/specs/011-auto-planning-from-canonical-feature-context.md', $candidate['suggested_spec_path']);
        $this->assertSame('high', $candidate['module_inference']['confidence']);
        $this->assertSame('review', $candidate['import_action']);
        $this->assertSame('inferred', $candidate['evidence']['current_source']);
        $this->assertContains('Anchor matched: Spec35D7C', $candidate['notes']);
    }

    public function test_build_classifies_eras_and_import_actions_around_transition_anchor(): void
    {
        $this->writeRawFile('Modules/ContextPersistence/specs/001-initial.md', "# Execution Spec: 001-initial\n");
        $this->writeSpecFile('Foundry-Spec-35D.md', "Spec 35D\nTitle: Marketplace bridge\n");
        $this->writeSpecFile('Foundry-Spec-35D1.md', "Spec 35D1\nTitle: Canonical transition\n");
        $this->writeSpecFile('Foundry-Spec-35D2.md', "Spec 35D2\nTitle: Canonical after anchor\n");
        $this->writeSpecFile('Foundry-Spec-36.md', "Spec 36\nTitle: Canonical numeric era\n");
        $this->writeSpecFile('Foundry-Spec-Unknown.md', "# Planning Notes\nTitle: Unknown\n");

        $payload = $this->mapper()->build(
            sourcePath: '_import/historical-specs',
            anchorsPath: null,
            withGitEvidence: false,
            write: false,
            dryRun: true,
        );

        $rowsByLabel = [];
        foreach ($payload['candidates'] as $row) {
            $rowsByLabel[(string) $row['legacy_label']] = $row;
        }

        $this->assertSame('pre_canonical', $rowsByLabel['Spec35D']['era']);
        $this->assertSame('before', $rowsByLabel['Spec35D']['canonical_transition_relative']);
        $this->assertSame('import', $rowsByLabel['Spec35D']['import_action']);

        $this->assertSame('canonical_existing', $rowsByLabel['Spec35D1']['era']);
        $this->assertSame('at', $rowsByLabel['Spec35D1']['canonical_transition_relative']);
        $this->assertSame('link_existing', $rowsByLabel['Spec35D1']['import_action']);
        $this->assertSame('Modules/ContextPersistence/specs/001-initial.md', $rowsByLabel['Spec35D1']['existing_spec_path']);

        $this->assertSame('canonical_existing', $rowsByLabel['Spec35D2']['era']);
        $this->assertSame('review', $rowsByLabel['Spec35D2']['import_action']);
        $this->assertSame('canonical_existing', $rowsByLabel['Spec36']['era']);
        $this->assertSame('review', $rowsByLabel['Spec36']['import_action']);

        $ambiguous = null;
        foreach ($payload['candidates'] as $row) {
            if ((string) $row['source_file'] === '_import/historical-specs/Foundry-Spec-Unknown.md') {
                $ambiguous = $row;
                break;
            }
        }

        self::assertIsArray($ambiguous);
        $this->assertSame('', $ambiguous['legacy_label']);
        $this->assertSame('ambiguous', $ambiguous['era']);
        $this->assertSame('review', $ambiguous['import_action']);
        $this->assertSame('unknown', $ambiguous['canonical_transition_relative']);
    }

    public function test_build_marks_low_confidence_pre_canonical_candidates_for_review(): void
    {
        $this->writeSpecFile('Foundry-Spec-20.md', "Spec 20\nTitle: General planning\n");

        $payload = $this->mapper()->build(
            sourcePath: '_import/historical-specs',
            anchorsPath: null,
            withGitEvidence: false,
            write: false,
            dryRun: true,
        );

        $candidate = $payload['candidates'][0];
        $this->assertSame('pre_canonical', $candidate['era']);
        $this->assertSame('review', $candidate['import_action']);
        $this->assertNull($candidate['suggested_module']);
        $this->assertSame('low', $candidate['module_inference']['confidence']);
    }

    public function test_build_marks_conflicting_module_inference_as_review(): void
    {
        $this->writeSpecFile(
            'Foundry-Spec-34.md',
            "Spec 34\nTitle: MCP quality guard\nPurpose: mcp runtime and quality checks.\n",
        );

        $payload = $this->mapper()->build(
            sourcePath: '_import/historical-specs',
            anchorsPath: null,
            withGitEvidence: false,
            write: false,
            dryRun: true,
        );

        $candidate = $payload['candidates'][0];
        $this->assertSame('pre_canonical', $candidate['era']);
        $this->assertSame('review', $candidate['import_action']);
        $this->assertSame('conflict', $candidate['evidence']['current_source']);
        $this->assertNotEmpty($candidate['module_inference']['alternatives']);
    }

    public function test_build_supports_transition_anchor_override_via_config(): void
    {
        $this->writeSpecFile('Foundry-Spec-19A.md', "Spec 19A\nTitle: Override boundary\n");
        $this->writeRawFile(
            '_import/historical-specs/import-anchors.json',
            json_encode([
                'canonical_transition' => [
                    'legacy_label' => 'Spec19A',
                    'canonical_path' => 'docs/features/custom/019-a.md',
                    'canonical_module' => 'FeatureSystem',
                    'canonical_spec_id' => '019',
                    'canonical_slug' => 'custom-boundary',
                    'confidence' => 'medium',
                ],
            ], JSON_THROW_ON_ERROR),
        );

        $payload = $this->mapper()->build(
            sourcePath: '_import/historical-specs',
            anchorsPath: '_import/historical-specs/import-anchors.json',
            withGitEvidence: false,
            write: false,
            dryRun: true,
        );

        $candidate = $payload['candidates'][0];
        $this->assertSame('canonical_existing', $candidate['era']);
        $this->assertSame('at', $candidate['canonical_transition_relative']);
        $this->assertSame('review', $candidate['import_action']);
        $this->assertSame('Spec19A', $payload['canonical_transition']['legacy_label']);
        $this->assertSame('docs/features/custom/019-a.md', $payload['canonical_transition']['canonical_path']);
        $this->assertSame('medium', $payload['canonical_transition']['confidence']);
    }

    public function test_build_includes_deterministic_transition_and_counts(): void
    {
        $this->writeRawFile('Modules/ContextPersistence/specs/001-initial.md', "# Execution Spec: 001-initial\n");
        $this->writeSpecFile('Foundry-Spec-35D.md', "Spec 35D\nTitle: Marketplace\n");
        $this->writeSpecFile('Foundry-Spec-35D1.md', "Spec 35D1\nTitle: Context persistence\n");
        $this->writeSpecFile('Foundry-Spec-Summaries.md', "Summary only\n");
        $this->writeSpecFile('Foundry-Spec-Unknown.md', "# Notes\n");

        $payload = $this->mapper()->build(
            sourcePath: '_import/historical-specs',
            anchorsPath: null,
            withGitEvidence: false,
            write: false,
            dryRun: true,
        );

        $this->assertSame('Spec35D1', $payload['canonical_transition']['legacy_label']);
        $this->assertSame('docs/features/context-persistence/001-initial.md', $payload['canonical_transition']['canonical_path']);
        $this->assertSame('Modules/ContextPersistence/specs/001-initial.md', $payload['canonical_transition']['current_canonical_path']);
        $this->assertSame('high', $payload['canonical_transition']['confidence']);
        $this->assertSame([
            'pre_canonical' => 1,
            'canonical_existing' => 1,
            'ambiguous' => 1,
            'supporting_evidence' => 1,
        ], $payload['counts']);
    }

    public function test_write_and_dry_run_behaviors_are_deterministic(): void
    {
        $this->writeSpecFile('Foundry-Spec-3.md', "Spec 3\nTitle: Three\n");

        $dry = $this->mapper()->build(
            sourcePath: '_import/historical-specs',
            anchorsPath: null,
            withGitEvidence: false,
            write: true,
            dryRun: true,
        );
        $this->assertFalse($dry['outputs']['written']);
        $this->assertFileDoesNotExist($this->project->root . '/_import/historical-specs/evidence-map.json');

        $first = $this->mapper()->build(
            sourcePath: '_import/historical-specs',
            anchorsPath: null,
            withGitEvidence: false,
            write: true,
            dryRun: false,
        );
        $firstJson = (string) file_get_contents($this->project->root . '/_import/historical-specs/evidence-map.json');

        $second = $this->mapper()->build(
            sourcePath: '_import/historical-specs',
            anchorsPath: null,
            withGitEvidence: false,
            write: true,
            dryRun: false,
        );
        $secondJson = (string) file_get_contents($this->project->root . '/_import/historical-specs/evidence-map.json');

        $this->assertTrue($first['outputs']['written']);
        $this->assertTrue($second['outputs']['written']);
        $this->assertSame($first['candidates'], $second['candidates']);
        $this->assertSame($firstJson, $secondJson);
    }

    public function test_with_git_evidence_marks_unavailable_history_as_unknown(): void
    {
        $this->writeSpecFile('Foundry-Spec-4.md', "Spec 4\nTitle: Four\n");

        $payload = $this->mapper()->build(
            sourcePath: '_import/historical-specs',
            anchorsPath: null,
            withGitEvidence: true,
            write: false,
            dryRun: true,
        );

        $this->assertSame('unknown', $payload['candidates'][0]['evidence']['git_commit']);
        $this->assertSame([], $payload['candidates'][0]['git']['matched_commits']);
    }

    public function test_with_git_evidence_infers_commit_matches_when_history_exists(): void
    {
        $this->writeSpecFile('Foundry-Spec-35D7C.md', "Spec 35D7C\nTitle: Auto planning\n");
        $this->initGitRepository();

        $payload = $this->mapper()->build(
            sourcePath: '_import/historical-specs',
            anchorsPath: null,
            withGitEvidence: true,
            write: false,
            dryRun: true,
        );

        $this->assertSame('inferred', $payload['candidates'][0]['evidence']['git_commit']);
        $this->assertNotEmpty($payload['candidates'][0]['git']['matched_commits']);
        $this->assertSame(7, strlen((string) $payload['candidates'][0]['git']['matched_commits'][0]['hash']));
    }

    public function test_build_fails_when_source_directory_is_missing(): void
    {
        $this->expectException(FoundryError::class);

        try {
            $this->mapper()->build(
                sourcePath: '_import/missing',
                anchorsPath: null,
                withGitEvidence: false,
                write: false,
                dryRun: true,
            );
        } catch (FoundryError $error) {
            $this->assertSame('HISTORICAL_SPECS_EVIDENCE_SOURCE_DIRECTORY_MISSING', $error->errorCode);
            throw $error;
        }
    }

    private function mapper(): HistoricalSpecEvidenceMapper
    {
        return new HistoricalSpecEvidenceMapper(new Paths($this->project->root));
    }

    private function writeSpecFile(string $filename, string $contents): void
    {
        $this->writeRawFile('_import/historical-specs/' . $filename, $contents);
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

    private function initGitRepository(): void
    {
        $this->runGit('init');
        $this->runGit('config user.name "Foundry Tests"');
        $this->runGit('config user.email "foundry-tests@example.invalid"');
        $this->runGit('add .');
        $this->runGit('commit -m "Implement Spec35D7C auto planning"');
    }

    private function runGit(string $args): void
    {
        $command = sprintf('cd %s && git %s 2>&1', escapeshellarg($this->project->root), $args);
        $output = shell_exec($command);
        self::assertNotFalse($output);
    }
}
