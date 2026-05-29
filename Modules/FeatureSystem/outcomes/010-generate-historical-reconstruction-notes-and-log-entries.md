# 010-generate-historical-reconstruction-notes-and-log-entries

## Spec Implemented

`Modules/FeatureSystem/specs/010-generate-historical-reconstruction-notes-and-log-entries.md`

## Implementation Summary

- Added deterministic reconstruction-note and implementation-log generation through `historical-specs:reconstruct` with report-only default behavior and explicit `--apply` write mode.
- Added a FeatureSystem reconstruction generator that targets completed imported historical specs, excludes draft imports, creates missing historical reconstruction notes, preserves existing notes, and appends missing canonical log entries once.
- Added embedded OUTPUT/RESULT evidence extraction with concise implementation, verification, stabilization, path-reference, repository-alignment, and uncertainty summaries.
- Added website-spec exclusion so `*WS.md` historical records are treated as supporting/ignored evidence and skipped by framework import.
- Added focused unit/integration coverage for note generation, existing-note preservation, explicit unknown evidence, canonical/idempotent log entries, deterministic ordering, draft exclusion, command wiring, and `spec:validate` pass after generation.

## Files Introduced

- `src/FeatureSystem/HistoricalReconstructionGenerator.php`
- `src/CLI/Commands/HistoricalSpecsReconstructCommand.php`
- `tests/Unit/HistoricalReconstructionGeneratorTest.php`
- `tests/Integration/CLIHistoricalSpecsReconstructCommandTest.php`
- `Modules/FeatureSystem/outcomes/010-generate-historical-reconstruction-notes-and-log-entries.md`

## Files Modified

- `src/FeatureSystem/HistoricalSpecArchiveImporter.php`
- `src/FeatureSystem/HistoricalSpecEvidenceMapper.php`
- `src/CLI/Application.php`
- `tests/Unit/CLICommandMatchesTest.php`
- `tests/Unit/HistoricalSpecArchiveImporterTest.php`
- `tests/Unit/HistoricalSpecEvidenceMapperTest.php`
- `Modules/FeatureSystem/feature-system.spec.md`
- `Modules/FeatureSystem/feature-system.md`
- `Modules/FeatureSystem/feature-system.decisions.md`
- `Modules/implementation.log`

## Runtime Contracts

- `historical-specs:reconstruct --json` runs in report mode and writes no files.
- `historical-specs:reconstruct --apply --json` creates missing reconstruction notes and appends missing implementation-log entries.
- `--module=<Module>` limits reconstruction generation to one canonical module name.
- Only completed imported specs under `Modules/<Module>/specs/*.md` are targeted; draft imported specs are excluded.
- Existing reconstruction notes are reported as existing and are not overwritten.
- Missing log entries are appended to `Modules/implementation.log` using canonical `Modules/<Module>/specs/<id-and-slug>.md` paths.

## Deterministic Outputs

- Imported completed spec ordering is stable by repository-relative spec path.
- Generated reconstruction notes use stable section order and repository-relative paths.
- Embedded OUTPUT/RESULT sections are summarized into bounded evidence lines rather than copied wholesale.
- Missing historical evidence is represented with explicit `unknown`/`inferred` wording.
- Generated implementation-log entries are appended once and contain no timestamps.
- Website-owned `*WS.md` historical records are excluded from framework import and downstream context/reconstruction/log generation.

## Tests Added Or Updated

- `tests/Unit/HistoricalReconstructionGeneratorTest.php`
- `tests/Unit/HistoricalSpecArchiveImporterTest.php`
- `tests/Unit/HistoricalSpecEvidenceMapperTest.php`
- `tests/Integration/CLIHistoricalSpecsReconstructCommandTest.php`
- `tests/Unit/CLICommandMatchesTest.php`

## Verification Commands

- `php vendor/bin/phpunit tests/Unit/HistoricalReconstructionGeneratorTest.php tests/Integration/CLIHistoricalSpecsReconstructCommandTest.php tests/Unit/CLICommandMatchesTest.php`
- `php vendor/bin/phpunit tests/Unit/HistoricalSpecArchiveExtractorTest.php tests/Unit/HistoricalSpecEvidenceMapperTest.php tests/Unit/HistoricalSpecArchiveImporterTest.php tests/Unit/HistoricalModuleContextGeneratorTest.php tests/Unit/HistoricalReconstructionGeneratorTest.php tests/Integration/CLIHistoricalSpecsExtractCommandTest.php tests/Integration/CLIHistoricalSpecsEvidenceCommandTest.php tests/Integration/CLIHistoricalSpecsImportCommandTest.php tests/Integration/CLIHistoricalSpecsContextCommandTest.php tests/Integration/CLIHistoricalSpecsReconstructCommandTest.php tests/Unit/CLICommandMatchesTest.php`
- `php bin/foundry spec:validate --json`
- `php bin/foundry verify context --json`
- `php bin/foundry verify features --json`
- `php bin/foundry verify contracts --json`
- `php vendor/bin/phpunit`
- `XDEBUG_MODE=coverage php vendor/bin/phpunit --coverage-clover build/coverage/clover.xml`
- `php bin/foundry verify coverage --min=90 --clover=build/coverage/clover.xml --json`

## Decisions And Tradeoffs

- Kept reconstruction generation separate from import and context generation so each historical phase remains reviewable and idempotent.
- Used legacy `# Implementation Plan:` headings for generated historical notes because spec 010 explicitly defines that structure and current validation grandfathers legacy plan headings deterministically.
- Excluded draft imports from reconstruction/log generation to avoid treating uncertain historical specs as completed.
- Summarized embedded RESULT/OUTPUT evidence instead of duplicating full transcripts.
- Excluded `*WS.md` website historical specs from framework import because they belong to website repository context, not framework module context.

## Reconstruction Notes

- Reconstruct by scanning active module specs for importer-shaped historical import notes, sorting by path, extracting bounded OUTPUT/RESULT evidence summaries, creating missing plan files, and appending missing canonical log entries.
- Preserve existing notes and log entries on repeated apply runs.
- Treat absent embedded evidence as unknown rather than inferring implementation details.
- Treat `*WS.md` records as ignored/supporting evidence so they remain visible but cannot become framework module imports.

## Follow-Up Dependencies

- `Modules/FeatureSystem/specs/drafts/011-add-decision-summaries-without-compacting-ledgers.md`
