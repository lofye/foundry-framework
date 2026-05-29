# 013-import-explicitly-marked-precanonical-archive

## Spec Implemented

`Modules/FeatureSystem/specs/013-import-explicitly-marked-precanonical-archive.md`

## Implementation Summary

- Added a deterministic marked pre-canonical archive importer for single-file `S`, `R`, and `P` marker archives.
- Added the `precanonical:import` CLI with report-only default behavior, explicit `--apply`, optional `--force`, and `--target-module=PreCanonical`.
- Mapped legacy alphanumeric IDs into padded dot-separated canonical IDs and generated validator-compatible PreCanonical spec filenames.
- Generated imported specs, reconstruction notes, PreCanonical context files, and idempotent implementation-log entries during apply mode.
- Preserved result blocks as reconstruction evidence and preamble blocks as associated or global context without inferring modern module ownership.

## Files Introduced

- `src/FeatureSystem/PreCanonicalArchiveImporter.php`
- `src/CLI/Commands/PreCanonicalImportCommand.php`
- `tests/Unit/PreCanonicalArchiveImporterTest.php`
- `tests/Integration/CLIPreCanonicalImportCommandTest.php`
- `Modules/FeatureSystem/outcomes/013-import-explicitly-marked-precanonical-archive.md`

## Files Modified

- `src/CLI/Application.php`
- `src/Support/ApiSurfaceRegistry.php`
- `tests/Unit/CLICommandMatchesTest.php`
- `tests/Unit/ApiSurfaceRegistryTest.php`
- `Modules/FeatureSystem/specs/013-import-explicitly-marked-precanonical-archive.md`
- `Modules/FeatureSystem/feature-system.spec.md`
- `Modules/FeatureSystem/feature-system.md`
- `Modules/FeatureSystem/feature-system.decisions.md`
- `Modules/implementation.log`

## Runtime Contracts

- `precanonical:import --source=<path> --json` parses the archive and reports what would be written without changing files.
- `precanonical:import --source=<path> --apply --json` writes PreCanonical specs, plans, context files, and implementation-log entries.
- `S` blocks are required and become imported specs; `R` blocks are optional result evidence; `P` blocks are contextual evidence only.
- Result blocks pair to specs by normalized `NAME:` text across dash variants and case differences.
- Orphan results, missing names, duplicate spec names with different content, malformed legacy IDs, canonical ID collisions, and output conflicts fail deterministically.
- `--force` permits replacement of generated PreCanonical artifacts only; implementation-log entries remain append-only and idempotent.

## Deterministic Outputs

- Canonical IDs preserve legacy numeric, alphabetic, and hyphen-suffix segment order with 3-digit padding.
- Slugs are generated from the legacy `NAME:` description using lowercase kebab-case normalization.
- JSON reports use repository-relative paths and stable summary keys.
- Generated PreCanonical context and reconstruction files are timestamp-free.
- Implementation-log entries are appended once per imported PreCanonical spec in canonical ID order.

## Tests Added Or Updated

- `tests/Unit/PreCanonicalArchiveImporterTest.php`
- `tests/Integration/CLIPreCanonicalImportCommandTest.php`
- `tests/Unit/CLICommandMatchesTest.php`
- `tests/Unit/ApiSurfaceRegistryTest.php`

## Verification Commands

- `php vendor/bin/phpunit tests/Unit/PreCanonicalArchiveImporterTest.php tests/Integration/CLIPreCanonicalImportCommandTest.php tests/Unit/CLICommandMatchesTest.php tests/Unit/ApiSurfaceRegistryTest.php`
- `php bin/foundry precanonical:import --source=_import/precanonical/marked-archive.md --json`
- `php bin/foundry spec:validate --json`
- `php bin/foundry verify context --json`
- `php bin/foundry verify features --json`
- `php bin/foundry verify contracts --json`
- `php vendor/bin/phpunit`
- `XDEBUG_MODE=coverage php vendor/bin/phpunit --coverage-clover build/coverage/clover.xml`
- `php bin/foundry verify coverage --min=90 --clover=build/coverage/clover.xml --json`

## Decisions And Tradeoffs

- Kept the marked archive importer separate from candidate-directory historical import because this source format has different deterministic boundaries and output responsibilities.
- Preserved pre-canonical lineage under `Modules/PreCanonical` instead of inferring modern module ownership from legacy names or result prose.
- Rendered imported specs with current `# Execution Spec:` headings so generated artifacts satisfy current validation while preserving the original archive body under a historical section.
- Used broad PreCanonical context summaries so large imports remain inspectable without requiring every imported spec to be summarized in module state.

## Reconstruction Notes

- Reconstruct the behavior by parsing marker lines, requiring a following `NAME:` line, preserving body text with only edge blank-line normalization, grouping specs/results/preambles by normalized names and source order, and rendering artifacts from the assembled import model.
- The importer refuses ambiguous archive states before writing any files.
- Apply mode writes every artifact through a single artifact list, making dry-run report paths and write paths share the same deterministic model.

## Follow-Up Dependencies

- A later alignment spec may map imported PreCanonical records into modern framework modules when ownership can be decided explicitly.
- The repository currently has no `_import/precanonical/marked-archive.md`, so full real-archive apply verification is gated on that archive being present.
