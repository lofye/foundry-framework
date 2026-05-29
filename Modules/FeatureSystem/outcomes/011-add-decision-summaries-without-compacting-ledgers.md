# 011-add-decision-summaries-without-compacting-ledgers

## Spec Implemented

`Modules/FeatureSystem/specs/011-add-decision-summaries-without-compacting-ledgers.md`

## Implementation Summary

- Added deterministic non-blocking decision-summary warnings to `spec:validate` so modules can preserve append-only decision ledgers while still surfacing missing/stale summary guidance.
- Added validator support for module-state `## Decision Summary` sections with `Refreshed Through Spec:` markers.
- Updated CLI rendering and validation payload summaries to include warning counts and warning detail output.
- Updated framework docs and implementation skills to instruct agents to refresh summaries instead of compacting decision ledgers.

## Files Introduced

- `Modules/FeatureSystem/outcomes/011-add-decision-summaries-without-compacting-ledgers.md`

## Files Modified

- `src/Context/ExecutionSpecValidationService.php`
- `src/CLI/Commands/SpecValidateCommand.php`
- `tests/Unit/ExecutionSpecValidationServiceTest.php`
- `tests/Integration/CLISpecValidateCommandTest.php`
- `AGENTS.md`
- `APP-AGENTS.md`
- `README.md`
- `.skills/implement-spec-and-stabilize.skill.md`
- `.skills/implement-spec-and-stabilize-strict.skill.md`
- `Modules/FeatureSystem/feature-system.spec.md`
- `Modules/FeatureSystem/feature-system.md`
- `Modules/FeatureSystem/feature-system.decisions.md`
- `Modules/implementation.log`

## Runtime Contracts

- `php bin/foundry spec:validate --json` now returns `summary.warnings` and top-level `warnings`.
- Decision-summary warnings are non-blocking (`ok` remains `true` when only warnings exist).
- Module decision-summary warnings:
  - `DECISION_SUMMARY_MISSING`
  - `DECISION_SUMMARY_POSSIBLY_STALE`
- Stale checks are based on module state summary markers (`Refreshed Through Spec`) compared against canonical module implementation-log references.

## Deterministic Outputs

- Warning ordering is deterministic by file path and warning code.
- `spec:validate` warning counts are deterministic in JSON and plain-text output.
- Decision summary staleness checks consume canonical `Modules/implementation.log` entries only.

## Tests Added Or Updated

- `tests/Unit/ExecutionSpecValidationServiceTest.php`
- `tests/Integration/CLISpecValidateCommandTest.php`

## Verification Commands

- `php vendor/bin/phpunit tests/Unit/ExecutionSpecValidationServiceTest.php tests/Integration/CLISpecValidateCommandTest.php`
- `php bin/foundry spec:validate --json`
- `php bin/foundry verify context --feature=feature-system --json`
- `php bin/foundry verify context --json`
- `php bin/foundry verify features --json`
- `php bin/foundry verify contracts --json`
- `php vendor/bin/phpunit`
- `XDEBUG_MODE=coverage php vendor/bin/phpunit --coverage-clover build/coverage/clover.xml`
- `php bin/foundry verify coverage --min=90 --clover=build/coverage/clover.xml --json`

## Decisions And Tradeoffs

- Kept summary checks as warnings (not violations) to avoid blocking modules that have append-only ledgers but no summary migration yet.
- Implemented staleness checks through explicit summary refresh markers to keep behavior deterministic and machine-checkable.
- Kept summary location in module state files (`<module>.md`) to avoid introducing a new required summary-file type.

## Reconstruction Notes

- Validator and CLI rendering changes are intentionally small and localized to preserve existing output contracts while adding warning surfaces.
- Documentation and skill updates align with the non-destructive summary approach and preserve append-only ledger history.

## Follow-Up Dependencies

- Future specs may tighten stale-summary heuristics once every active module has adopted `## Decision Summary` markers.
