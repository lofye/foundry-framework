# 007-normalize-implementation-log-canonical-spec-paths

## Spec Implemented

`Modules/FeatureSystem/specs/007-normalize-implementation-log-canonical-spec-paths.md`

## Implementation Summary

- Promoted execution spec `007-normalize-implementation-log-canonical-spec-paths` from drafts into active module specs.
- Normalized historical `Modules/implementation.log` framework entries from slug-style references to canonical `Modules/<Module>/specs/<id>-<slug>.md` paths where deterministic mapping existed.
- Tightened execution-spec validation so module specs require canonical implementation-log references and raise deterministic `EXECUTION_SPEC_IMPLEMENTATION_LOG_PATH_NOT_CANONICAL` violations for slug-style references.
- Updated implementation-log entry suggestion/writer behavior so module execution specs now emit canonical module spec paths in `- spec:` lines.
- Added focused unit and integration coverage for canonical module-path acceptance, non-canonical rejection, and module `spec:log-entry` output shape.

## Files Introduced

- `Modules/FeatureSystem/outcomes/007-normalize-implementation-log-canonical-spec-paths.md`

## Files Modified

- `Modules/FeatureSystem/specs/007-normalize-implementation-log-canonical-spec-paths.md`
- `Modules/implementation.log`
- `src/Context/ExecutionSpecValidationService.php`
- `src/Context/ExecutionSpecImplementationLogService.php`
- `tests/Unit/ExecutionSpecValidationServiceTest.php`
- `tests/Unit/ExecutionSpecImplementationLogServiceTest.php`
- `tests/Integration/CLISpecValidateCommandTest.php`
- `tests/Integration/CLISpecLogEntryCommandTest.php`
- `tests/Integration/CLIFeatureSystemCommandTest.php`
- `Modules/FeatureSystem/feature-system.spec.md`
- `Modules/FeatureSystem/feature-system.md`
- `Modules/FeatureSystem/feature-system.decisions.md`

## Runtime Contracts

- Framework module completion ledger entries in `Modules/implementation.log` must reference canonical module spec paths (`Modules/<Module>/specs/<id>-<slug>.md`).
- Slug-style module references such as `feature-system/007-...` are invalid for module log coverage and must fail validation with deterministic violation code `EXECUTION_SPEC_IMPLEMENTATION_LOG_PATH_NOT_CANONICAL`.
- Missing module implementation-log coverage for active specs still reports `EXECUTION_SPEC_IMPLEMENTATION_LOG_MISSING`.

## Deterministic Outputs

- `spec:validate --json` now emits deterministic `EXECUTION_SPEC_IMPLEMENTATION_LOG_PATH_NOT_CANONICAL` violations with `entry`, `expected`, and `spec_path` details when module log entries use slug-style references.
- Module `spec:log-entry` output now emits canonical `spec_ref` and `spec_log_line` values for module specs.

## Tests Added Or Updated

- `tests/Unit/ExecutionSpecValidationServiceTest.php`
- `tests/Unit/ExecutionSpecImplementationLogServiceTest.php`
- `tests/Integration/CLISpecValidateCommandTest.php`
- `tests/Integration/CLISpecLogEntryCommandTest.php`
- `tests/Integration/CLIFeatureSystemCommandTest.php`

## Verification Commands

- `php bin/foundry spec:validate --json`
- `php bin/foundry verify context --json`
- `php bin/foundry verify features --json`
- `php bin/foundry verify contracts --json`
- `php vendor/bin/phpunit`
- `XDEBUG_MODE=coverage php vendor/bin/phpunit --coverage-clover build/coverage/clover.xml`
- `php bin/foundry verify coverage --min=90 --clover=build/coverage/clover.xml --json`

## Decisions And Tradeoffs

- Canonical-path enforcement was applied to framework module implementation-log entries immediately while preserving existing non-module slug behavior to avoid introducing unrelated app-log contract changes in this spec.
- Log normalization rewrites only deterministic slug-to-module mappings; ambiguous or unmapped references remain validation-managed instead of being guessed.

## Reconstruction Notes

- Reconstruct by promoting spec `007` into active module specs, then implementing canonical module log reference checks inside `ExecutionSpecValidationService` while keeping deterministic ordering and continuity semantics unchanged.
- Update `ExecutionSpecImplementationLogService` to emit module spec paths for module entries and maintain existing legacy behavior for non-module scopes.
- Normalize historical `Modules/implementation.log` entries via deterministic slug-to-module mapping constrained to existing `Modules/<Module>/specs/*.md` files.
- Confirm behavior through focused spec-log and spec-validate tests before running full strict gates.

## Follow-Up Dependencies

- `Modules/FeatureSystem/specs/008-import-historical-spec-archive.md`
- `Modules/FeatureSystem/specs/010-generate-historical-reconstruction-notes-and-log-entries.md`
