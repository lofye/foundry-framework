# 011-rename-reconstruction-plans-to-outcomes

## Spec Implemented
- `Modules/ExecutionSpecSystem/specs/011-rename-reconstruction-plans-to-outcomes.md`

## Implementation Summary
- Replaced reconstruction-note directory contracts from `plans/` to `outcomes/` across framework modules and feature workspaces.
- Updated execution-spec validation and CLI metadata to treat `--require-outcomes` as canonical while preserving `--require-plans` as a deprecated compatibility alias.
- Migrated repository reconstruction-note directories from `Modules/*/plans` and `Features/*/plans` to `outcomes`.

## Files Introduced
- `Modules/ExecutionSpecSystem/outcomes/011-rename-reconstruction-plans-to-outcomes.md`

## Files Modified
- `src/CLI/Commands/SpecValidateCommand.php`
- `src/Context/ExecutionSpecValidationService.php`
- `src/Context/ExecutionSpecPlanService.php`
- `src/FeatureSystem/FeatureWorkspaceService.php`
- `src/FeatureSystem/HistoricalReconstructionGenerator.php`
- `src/FeatureSystem/PreCanonicalArchiveImporter.php`
- `src/Support/ApiSurfaceRegistry.php`
- `stubs/specs/implementation-plan.stub.md`
- `tests/Integration/CLIApplicationTest.php`
- `tests/Integration/CLISpecPlanCommandTest.php`
- `tests/Unit/ApiSurfaceRegistryTest.php`
- `Modules/ExecutionSpecSystem/execution-spec-system.spec.md`
- `Modules/ExecutionSpecSystem/execution-spec-system.md`
- `Modules/ExecutionSpecSystem/execution-spec-system.decisions.md`
- `Modules/implementation.log`
- repository-wide reconstruction-note path references updated from `plans/` to `outcomes/` where they refer to reconstruction artifacts
- moved directories:
- `Modules/*/plans` -> `Modules/*/outcomes`
- `Features/*/plans` -> `Features/*/outcomes`

## Runtime Contracts
- `spec:validate` now accepts canonical `--require-outcomes` and also accepts deprecated `--require-plans`.
- When `--require-plans` is used, `spec:validate` emits metadata indicating the canonical flag is `--require-outcomes`.
- Reconstruction-note path expectations now resolve to `outcomes/` for module, feature, and legacy docs-feature workspaces.
- `spec:plan` writes generated files to `docs/features/<feature>/outcomes/<id>-<slug>.md`.
- `spec:plan` error identifiers remain stable (`plan_*`) for compatibility.

## Deterministic Outputs
- Validation diagnostics for missing reconstruction notes now point to `.../outcomes/<id>-<slug>.md`.
- API-surface command usage string for `spec:validate` now includes both canonical and deprecated flags.
- Historical reconstruction path generation now emits `outcomes/` paths and provides compatibility payload keys where needed.

## Tests Added Or Updated
- Updated `tests/Integration/CLISpecPlanCommandTest.php` for `outcomes/` target paths and blocked-directory behavior.
- Updated `tests/Unit/ApiSurfaceRegistryTest.php` and `tests/Integration/CLIApplicationTest.php` expected `spec:validate` usage strings.
- Ran focused regression suite covering:
- spec-plan command behavior
- spec-validate command/validation service behavior
- API surface and CLI help output contracts
- historical reconstruction and pre-canonical import paths
- feature-system command integrations

## Verification Commands
```bash
php -l src/CLI/Commands/SpecValidateCommand.php
php -l src/Context/ExecutionSpecValidationService.php
php -l src/Context/ExecutionSpecPlanService.php
php -l src/FeatureSystem/FeatureWorkspaceService.php
php -l src/FeatureSystem/PreCanonicalArchiveImporter.php
php -l src/FeatureSystem/HistoricalReconstructionGenerator.php
php -l src/Support/ApiSurfaceRegistry.php
php vendor/bin/phpunit tests/Integration/CLISpecPlanCommandTest.php tests/Unit/ApiSurfaceRegistryTest.php tests/Integration/CLIApplicationTest.php tests/Integration/CLISpecValidateCommandTest.php tests/Unit/ExecutionSpecValidationServiceTest.php tests/Integration/CLIHistoricalSpecsReconstructCommandTest.php tests/Unit/HistoricalReconstructionGeneratorTest.php tests/Unit/PreCanonicalArchiveImporterTest.php tests/Integration/CLIFeatureSystemCommandTest.php
php bin/foundry verify context --feature=execution-spec-system --json
```

## Decisions And Tradeoffs
- Chose compatibility-window behavior for `--require-plans` to reduce migration risk for existing scripts and tests.
- Kept `spec:plan` output payload key named `plan` and stable `plan_*` error codes to avoid unnecessary breaking changes while path semantics migrate.
- Retained `.foundry/plans/` unchanged because it represents generate-plan persistence, not reconstruction-note artifacts.

## Reconstruction Notes
- Directory migration was applied by moving existing `plans` directories to `outcomes` under `Modules/` and `Features/`.
- A broad reference rewrite was then constrained by focused test runs and compatibility fixes to avoid collateral breakage.

## Follow-Up Dependencies
- Replace deprecated `--require-plans` usage in remaining legacy docs/spec archives as a non-blocking cleanup pass.
- Decide deprecation-removal timing for `--require-plans` once downstream automation usage is confirmed migrated.
