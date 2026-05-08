<?php

declare(strict_types=1);

namespace Foundry\Tests\Unit;

use Foundry\FeatureSystem\HistoricalModuleContextGenerator;
use Foundry\Support\Paths;
use Foundry\Tests\Fixtures\TempProject;
use PHPUnit\Framework\TestCase;

final class HistoricalModuleContextGeneratorTest extends TestCase
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

    public function test_apply_creates_missing_module_context_files_from_imported_specs(): void
    {
        $this->writeImportedSpec('LegacyModule', '001-recovered-runtime', true);
        $this->writeImportedSpec('LegacyModule', '002-uncertain-runtime', false);

        $payload = $this->generator()->generate(null, true, false);

        $this->assertSame('ok', $payload['status']);
        $this->assertSame(1, $payload['summary']['modules']);
        $this->assertSame(2, $payload['summary']['imported_specs']);
        $this->assertSame(3, $payload['summary']['created']);
        $this->assertSame(3, $payload['summary']['written']);

        $state = (string) file_get_contents($this->project->root . '/Modules/LegacyModule/legacy-module.md');
        $spec = (string) file_get_contents($this->project->root . '/Modules/LegacyModule/legacy-module.spec.md');
        $decisions = (string) file_get_contents($this->project->root . '/Modules/LegacyModule/legacy-module.decisions.md');

        $this->assertStringStartsWith("# Feature: legacy-module\n", $state);
        $this->assertStringContainsString('Historical import note:', $state);
        $this->assertStringContainsString('Modules/LegacyModule/specs/001-recovered-runtime.md', $state);
        $this->assertStringContainsString('Modules/LegacyModule/specs/drafts/002-uncertain-runtime.md', $state);
        $this->assertStringStartsWith("# Feature Spec: legacy-module\n", $spec);
        $this->assertStringContainsString('Historical imported specs are documented in module context with explicit uncertainty markers.', $spec);
        $this->assertStringContainsString('### Decision: record historical module context import for LegacyModule', $decisions);
        $this->assertStringContainsString('Timestamp: <ISO-8601>', $decisions);
    }

    public function test_existing_context_files_are_updated_without_destructive_rewrite(): void
    {
        $this->writeImportedSpec('LegacyModule', '001-recovered-runtime', true);
        $this->writeRawFile('Modules/LegacyModule/legacy-module.md', <<<'MD'
# Feature: legacy-module

## Purpose

- Existing purpose remains.

## Current State

- Existing state remains.

## Open Questions

- Existing question remains.

## Next Steps

- Existing next step remains.
MD);
        $this->writeRawFile('Modules/LegacyModule/legacy-module.spec.md', <<<'MD'
# Feature Spec: legacy-module

## Purpose

Existing spec purpose remains.

## Goals

- Existing goal remains.

## Non-Goals

- Existing non-goal remains.

## Constraints

- Existing constraint remains.

## Expected Behavior

- Existing behavior remains.

## Acceptance Criteria

- Existing acceptance remains.

## Assumptions

- Existing assumption remains.
MD);
        $this->writeRawFile('Modules/LegacyModule/legacy-module.decisions.md', <<<'MD'
### Decision: existing decision remains

Timestamp: 2026-01-01T00:00:00-05:00

**Context**

- Existing context remains.

**Decision**

- Existing decision remains.

**Reasoning**

- Existing reasoning remains.

**Alternatives Considered**

- Existing alternative remains.

**Impact**

- Existing impact remains.

**Spec Reference**

- Existing reference remains.
MD);

        $payload = $this->generator()->generate('LegacyModule', true, false);

        $state = (string) file_get_contents($this->project->root . '/Modules/LegacyModule/legacy-module.md');
        $spec = (string) file_get_contents($this->project->root . '/Modules/LegacyModule/legacy-module.spec.md');
        $decisions = (string) file_get_contents($this->project->root . '/Modules/LegacyModule/legacy-module.decisions.md');

        $this->assertSame(3, $payload['summary']['updated']);
        $this->assertStringContainsString('Existing state remains.', $state);
        $this->assertStringContainsString('## Implemented Specs', $state);
        $this->assertStringContainsString('Existing behavior remains.', $spec);
        $this->assertStringContainsString('## Historical Imports', $spec);
        $this->assertStringContainsString('### Decision: existing decision remains', $decisions);
        $this->assertStringContainsString('### Decision: record historical module context import for LegacyModule', $decisions);
    }

    public function test_dry_run_reports_without_writing(): void
    {
        $this->writeImportedSpec('LegacyModule', '001-recovered-runtime', true);

        $payload = $this->generator()->generate(null, false, true);

        $this->assertTrue($payload['dry_run']);
        $this->assertSame(3, $payload['summary']['created']);
        $this->assertSame(0, $payload['summary']['written']);
        $this->assertFileDoesNotExist($this->project->root . '/Modules/LegacyModule/legacy-module.md');
    }

    public function test_reports_modules_and_specs_in_deterministic_order(): void
    {
        $this->writeImportedSpec('ZetaModule', '002-zeta', true);
        $this->writeImportedSpec('AlphaModule', '001-alpha', true);

        $first = $this->generator()->generate(null, false, true);
        $second = $this->generator()->generate(null, false, true);

        $this->assertSame($first['modules'], $second['modules']);
        $this->assertSame('AlphaModule', $first['modules'][0]['module']);
        $this->assertSame('ZetaModule', $first['modules'][1]['module']);
    }

    private function generator(): HistoricalModuleContextGenerator
    {
        return new HistoricalModuleContextGenerator(new Paths($this->project->root));
    }

    private function writeImportedSpec(string $module, string $name, bool $active): void
    {
        $directory = $active ? 'specs' : 'specs/drafts';
        $this->writeRawFile('Modules/' . $module . '/' . $directory . '/' . $name . '.md', "# Execution Spec: {$name}\n\n## Historical Import Note\n\nThis spec was imported from archived pre-repository implementation records. Details marked inferred should be treated as lower-confidence historical reconstruction.\n\n## Purpose\n\nRecover context.\n");
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
