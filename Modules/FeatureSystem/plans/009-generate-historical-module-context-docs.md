# 009-generate-historical-module-context-docs

## Spec Implemented

`Modules/FeatureSystem/specs/009-generate-historical-module-context-docs.md`

## Implementation Summary

- Added deterministic historical module context generation through `historical-specs:context` with report-only default behavior and explicit `--apply` write mode.
- Added a FeatureSystem generator service that scans imported historical specs, groups them by module, creates missing canonical module context files, updates existing context without destructive rewrite, and appends decision-ledger entries idempotently.
- Added shape-based imported-spec detection so prose examples that mention `Historical Import Note` are not misclassified as imported history.
- Added focused unit and integration coverage for missing context creation, non-destructive updates, decision appends, uncertainty marking, deterministic ordering, dry-run behavior, command wiring, and post-generation context validation.

## Files Introduced

- `src/FeatureSystem/HistoricalModuleContextGenerator.php`
- `src/CLI/Commands/HistoricalSpecsContextCommand.php`
- `tests/Unit/HistoricalModuleContextGeneratorTest.php`
- `tests/Integration/CLIHistoricalSpecsContextCommandTest.php`
- `Modules/FeatureSystem/plans/009-generate-historical-module-context-docs.md`

## Files Modified

- `src/CLI/Application.php`
- `tests/Unit/CLICommandMatchesTest.php`
- `Modules/FeatureSystem/feature-system.spec.md`
- `Modules/FeatureSystem/feature-system.md`
- `Modules/FeatureSystem/feature-system.decisions.md`
- `Modules/implementation.log`

## Runtime Contracts

- `historical-specs:context --json` runs in report mode and writes no files.
- `historical-specs:context --apply --json` writes created or updated module context files for modules with imported historical specs.
- `--module=<Module>` limits context generation to one canonical module name.
- Imported historical specs are detected only when a canonical execution-spec heading is immediately followed by `## Historical Import Note`.
- Generated or repaired module context files live at `Modules/<Module>/<module>.md`, `Modules/<Module>/<module>.spec.md`, and `Modules/<Module>/<module>.decisions.md`.

## Deterministic Outputs

- Module ordering is stable by canonical module name.
- Imported spec ordering is stable by repository-relative path.
- Generated context sections use stable ordering and repository-relative spec references.
- Generated historical decision entries use `Timestamp: <ISO-8601>` rather than wall-clock values.
- Dry-run output contains no absolute local paths.

## Tests Added Or Updated

- `tests/Unit/HistoricalModuleContextGeneratorTest.php`
- `tests/Integration/CLIHistoricalSpecsContextCommandTest.php`
- `tests/Unit/CLICommandMatchesTest.php`

## Verification Commands

- `php vendor/bin/phpunit tests/Unit/HistoricalModuleContextGeneratorTest.php tests/Integration/CLIHistoricalSpecsContextCommandTest.php tests/Unit/CLICommandMatchesTest.php`
- `php vendor/bin/phpunit tests/Unit/HistoricalSpecArchiveExtractorTest.php tests/Unit/HistoricalSpecEvidenceMapperTest.php tests/Unit/HistoricalSpecArchiveImporterTest.php tests/Unit/HistoricalModuleContextGeneratorTest.php tests/Integration/CLIHistoricalSpecsExtractCommandTest.php tests/Integration/CLIHistoricalSpecsEvidenceCommandTest.php tests/Integration/CLIHistoricalSpecsImportCommandTest.php tests/Integration/CLIHistoricalSpecsContextCommandTest.php tests/Unit/CLICommandMatchesTest.php`
- `php bin/foundry historical-specs:context --dry-run --json`
- `php bin/foundry spec:validate --json`
- `php bin/foundry verify context --json`
- `php bin/foundry verify features --json`
- `php bin/foundry verify contracts --json`
- `php vendor/bin/phpunit`
- `XDEBUG_MODE=coverage php vendor/bin/phpunit --coverage-clover build/coverage/clover.xml`
- `php bin/foundry verify coverage --min=90 --clover=build/coverage/clover.xml --json`

## Decisions And Tradeoffs

- Used a separate context-generation command instead of extending import so 008 import behavior stays stable and context repair remains reviewable.
- Required imported specs to match the importer-produced heading/note shape to avoid treating examples in planning specs as imported history.
- Preserved existing context files by adding grounded bullets and bounded historical sections instead of regenerating whole files.
- Used deterministic placeholder timestamps for generated historical decision entries to avoid nondeterministic generated docs.

## Reconstruction Notes

- Reconstruct by scanning `Modules/*/specs/*.md` and `Modules/*/specs/drafts/*.md`, filtering to imported historical spec shape, sorting by module/path, and generating or repairing the three module context files.
- Preserve existing context and decision content, append historical sections/entries only when absent, and keep draft/inferred imported specs marked as uncertain.
- Re-run `historical-specs:context --dry-run --json` before apply mode to inspect affected modules and file statuses.

## Follow-Up Dependencies

- `Modules/FeatureSystem/specs/drafts/010-generate-historical-reconstruction-notes-and-log-entries.md`
- `Modules/FeatureSystem/specs/drafts/011-add-decision-summaries-without-compacting-ledgers.md`
