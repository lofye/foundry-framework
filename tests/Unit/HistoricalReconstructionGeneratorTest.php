<?php

declare(strict_types=1);

namespace Foundry\Tests\Unit;

use Foundry\FeatureSystem\HistoricalReconstructionGenerator;
use Foundry\Support\Paths;
use Foundry\Tests\Fixtures\TempProject;
use PHPUnit\Framework\TestCase;

final class HistoricalReconstructionGeneratorTest extends TestCase
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

    public function test_apply_creates_reconstruction_note_and_log_entry_for_imported_spec(): void
    {
        $this->writeImportedSpec('LegacyModule', '001-recovered-runtime', <<<'MD'
RESULT
Implemented src/Legacy/Runtime.php and tests/Unit/LegacyRuntimeTest.php.
php vendor/bin/phpunit passed.
Coverage verified at 92%.
Follow-up fix stabilized validation warnings.
MD);

        $payload = $this->generator()->generate(null, true, false);

        $notePath = $this->project->root . '/Modules/LegacyModule/plans/001-recovered-runtime.md';
        $log = (string) file_get_contents($this->project->root . '/Modules/implementation.log');
        $note = (string) file_get_contents($notePath);

        $this->assertSame(1, $payload['summary']['notes_created']);
        $this->assertSame(1, $payload['summary']['log_entries_appended']);
        $this->assertFileExists($notePath);
        $this->assertStringStartsWith("# Implementation Plan: 001-recovered-runtime\n", $note);
        $this->assertStringContainsString('## Historical Provenance', $note);
        $this->assertStringContainsString('confirmed path reference: `src/Legacy/Runtime.php`', $note);
        $this->assertStringContainsString('php vendor/bin/phpunit passed.', $note);
        $this->assertStringContainsString('- spec: Modules/LegacyModule/specs/001-recovered-runtime.md', $log);
    }

    public function test_existing_reconstruction_note_is_not_overwritten_silently(): void
    {
        $this->writeImportedSpec('LegacyModule', '001-recovered-runtime', "RESULT\nImplemented src/Legacy/Runtime.php.\n");
        $this->writeRawFile('Modules/LegacyModule/plans/001-recovered-runtime.md', "# Implementation Plan: 001-recovered-runtime\n\nExisting historical note.\n");

        $payload = $this->generator()->generate(null, true, false);

        $note = (string) file_get_contents($this->project->root . '/Modules/LegacyModule/plans/001-recovered-runtime.md');

        $this->assertSame(0, $payload['summary']['notes_created']);
        $this->assertSame(1, $payload['summary']['notes_existing']);
        $this->assertStringContainsString('Existing historical note.', $note);
        $this->assertStringNotContainsString('confirmed path reference', $note);
    }

    public function test_incomplete_evidence_is_marked_unknown_explicitly(): void
    {
        $this->writeImportedSpec('LegacyModule', '001-recovered-runtime', "## Purpose\nOnly archived purpose is available.\n");

        $this->generator()->generate(null, true, false);

        $note = (string) file_get_contents($this->project->root . '/Modules/LegacyModule/plans/001-recovered-runtime.md');

        $this->assertStringContainsString('No confirmed historical evidence available.', $note);
        $this->assertStringContainsString('unknown: original full terminal transcript is not reproduced', $note);
    }

    public function test_log_entry_is_appended_once_with_canonical_path(): void
    {
        $this->writeImportedSpec('LegacyModule', '001-recovered-runtime', "RESULT\nImplemented.\n");

        $this->generator()->generate(null, true, false);
        $this->generator()->generate(null, true, false);

        $log = (string) file_get_contents($this->project->root . '/Modules/implementation.log');

        $this->assertSame(1, substr_count($log, '- spec: Modules/LegacyModule/specs/001-recovered-runtime.md'));
    }

    public function test_multiple_imported_specs_are_processed_in_deterministic_order(): void
    {
        $this->writeImportedSpec('ZetaModule', '002-zeta', "RESULT\nImplemented src/Zeta.php.\n");
        $this->writeImportedSpec('AlphaModule', '001-alpha', "RESULT\nImplemented src/Alpha.php.\n");

        $first = $this->generator()->generate(null, false, true);
        $second = $this->generator()->generate(null, false, true);

        $this->assertSame($first['specs'], $second['specs']);
        $this->assertSame('Modules/AlphaModule/specs/001-alpha.md', $first['specs'][0]['spec_path']);
        $this->assertSame('Modules/ZetaModule/specs/002-zeta.md', $first['specs'][1]['spec_path']);
    }

    public function test_draft_imports_are_not_treated_as_completed_specs(): void
    {
        $this->writeRawFile('Modules/LegacyModule/specs/drafts/001-draft.md', "# Execution Spec: 001-draft\n\n## Historical Import Note\n\nArchived.\n");

        $payload = $this->generator()->generate(null, false, true);

        $this->assertSame(0, $payload['summary']['imported_specs']);
        $this->assertSame([], $payload['specs']);
    }

    private function generator(): HistoricalReconstructionGenerator
    {
        return new HistoricalReconstructionGenerator(new Paths($this->project->root));
    }

    private function writeImportedSpec(string $module, string $name, string $body): void
    {
        $this->writeRawFile('Modules/' . $module . '/specs/' . $name . '.md', "# Execution Spec: {$name}\n\n## Historical Import Note\n\nThis spec was imported from archived pre-repository implementation records. Details marked inferred should be treated as lower-confidence historical reconstruction.\n\n" . rtrim($body, "\n") . "\n");
    }

    private function writeRawFile(string $relativePath, string $contents): void
    {
        $path = $this->project->root . '/' . $relativePath;
        $directory = dirname($path);
        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        file_put_contents($path, rtrim($contents, "\n") . "\n");
    }
}
