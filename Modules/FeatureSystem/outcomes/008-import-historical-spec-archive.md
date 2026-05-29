# 008-import-historical-spec-archive

## Spec Implemented

`Modules/FeatureSystem/specs/008-import-historical-spec-archive.md`

## Implementation Summary

- Added deterministic historical archive import through `historical-specs:import` with report-only default behavior, explicit `--apply` write mode, and repository-relative JSON output.
- Added a FeatureSystem importer service that scans sorted archive candidate directories containing `spec.md`, validates `metadata.json`, renders canonical imported spec content, and classifies unmapped, invalid, duplicate, conflict, importable, and written candidates.
- Routed completed historical imports to active module specs and uncertain imports to module drafts while preserving archived text below a historical import note.
- Added focused unit and integration coverage for dry-run reports, apply writes, duplicate exact matches, conflicting destinations, missing/malformed metadata, uncertain draft placement, deterministic ordering, command errors, and command registration.

## Files Introduced

- `src/FeatureSystem/HistoricalSpecArchiveImporter.php`
- `src/CLI/Commands/HistoricalSpecsImportCommand.php`
- `tests/Unit/HistoricalSpecArchiveImporterTest.php`
- `tests/Integration/CLIHistoricalSpecsImportCommandTest.php`
- `Modules/FeatureSystem/outcomes/008-import-historical-spec-archive.md`

## Files Modified

- `src/CLI/Application.php`
- `tests/Unit/CLICommandMatchesTest.php`
- `Modules/FeatureSystem/feature-system.spec.md`
- `Modules/FeatureSystem/feature-system.md`
- `Modules/FeatureSystem/feature-system.decisions.md`
- `Modules/implementation.log`

## Runtime Contracts

- `historical-specs:import --source=<path> --dry-run --json` scans candidate bundles and writes no files.
- `historical-specs:import --source=<path> --apply --json` writes only importable candidates and leaves unmapped, invalid, duplicate, and conflict candidates untouched.
- Candidate metadata must provide a canonical module name, padded spec id, lowercase slug, boolean `implemented` when present, and optional confidence (`high`, `medium`, `low`, or `unknown`).
- Completed candidates target `Modules/<Module>/specs/<id>-<slug>.md`; uncertain candidates target `Modules/<Module>/specs/drafts/<id>-<slug>.md`.
- Existing destinations with exact rendered content report `already_imported`; existing destinations with different content report `HISTORICAL_SPEC_IMPORT_CONFLICT`.

## Deterministic Outputs

- Candidate traversal is sorted by archive directory path.
- JSON report paths are repository-relative and contain no host-specific absolute paths.
- Imported spec content contains no timestamps and always uses a canonical heading plus fixed `Historical Import Note`.
- Summary counts are stable across reruns for the same archive input and repository state.

## Tests Added Or Updated

- `tests/Unit/HistoricalSpecArchiveImporterTest.php`
- `tests/Integration/CLIHistoricalSpecsImportCommandTest.php`
- `tests/Unit/CLICommandMatchesTest.php`

## Verification Commands

- `php vendor/bin/phpunit tests/Unit/HistoricalSpecArchiveImporterTest.php tests/Integration/CLIHistoricalSpecsImportCommandTest.php tests/Unit/CLICommandMatchesTest.php`
- `php vendor/bin/phpunit tests/Unit/HistoricalSpecArchiveExtractorTest.php tests/Unit/HistoricalSpecEvidenceMapperTest.php tests/Unit/HistoricalSpecArchiveImporterTest.php tests/Integration/CLIHistoricalSpecsExtractCommandTest.php tests/Integration/CLIHistoricalSpecsEvidenceCommandTest.php tests/Integration/CLIHistoricalSpecsImportCommandTest.php`
- `php bin/foundry spec:validate --json`
- `php bin/foundry verify context --json`
- `php bin/foundry verify features --json`
- `php bin/foundry verify contracts --json`
- `php vendor/bin/phpunit`
- `XDEBUG_MODE=coverage php vendor/bin/phpunit --coverage-clover build/coverage/clover.xml`
- `php bin/foundry verify coverage --min=90 --clover=build/coverage/clover.xml --json`

## Decisions And Tradeoffs

- Required explicit metadata for import instead of deriving module/spec identity from archived prose so uncertain mapping stays visible.
- Routed non-implemented or uncertain candidates to drafts so active specs do not imply historical completion without evidence.
- Prepended canonical heading/import note while preserving archived text below it to satisfy current spec validation without rewriting the historical body.
- Kept import separate from reconstruction-note, decision-ledger, and implementation-log generation, which remain follow-up specs.

## Reconstruction Notes

- Reconstruct by scanning the source directory recursively for `spec.md` files, sorting candidate directories, validating metadata, then rendering destination paths from module/spec-id/slug/status metadata.
- Compare rendered content against any existing destination before writing; exact matches are idempotent and different content remains a conflict unless explicit future force behavior is exercised.
- Keep all report paths relative to the repository root and keep imported content deterministic.

## Follow-Up Dependencies

- `Modules/FeatureSystem/specs/drafts/009-generate-historical-module-context-docs.md`
- `Modules/FeatureSystem/specs/drafts/010-generate-historical-reconstruction-notes-and-log-entries.md`
- `Modules/FeatureSystem/specs/drafts/011-add-decision-summaries-without-compacting-ledgers.md`
