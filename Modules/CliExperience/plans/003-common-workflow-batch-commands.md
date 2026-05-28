# Implementation Plan: 003-common-workflow-batch-commands

## Implemented Spec Path
- `Modules/CliExperience/specs/003-common-workflow-batch-commands.md`

## Implementation Summary
- Added first-class batch workflow commands that compose existing CLI commands in deterministic order to reduce repetitive typing for common workflows.
- Introduced a shared CLI-owned batch runner that executes ordered steps, captures per-step results, and emits a consistent aggregate workflow payload.
- Integrated new workflow commands and options into command registration, API surface metadata, CLI surface verification probes, and demo workflow documentation.

## Files Introduced
- `src/CLI/Workflow/BatchWorkflowRunner.php`
- `src/CLI/Commands/ContextBootstrapCommand.php`
- `src/CLI/Commands/ContextRecoverCommand.php`
- `src/CLI/Commands/SpecPromoteCommand.php`
- `src/CLI/Commands/TestFeatureCommand.php`
- `src/CLI/Commands/VerifyArchitectureCommand.php`
- `src/CLI/Commands/VerifyDoneCommand.php`
- `src/CLI/Commands/VerifyFeatureWorkCommand.php`
- `tests/Integration/CLISpecPromoteCommandTest.php`
- `tests/Unit/ApiSurfaceRegistryBatchCommandsTest.php`
- `tests/Unit/BatchWorkflowRunnerTest.php`

## Files Modified
- `src/CLI/Application.php`
- `src/CLI/CliSurfaceVerifier.php`
- `src/CLI/Commands/DoctorCommand.php`
- `src/CLI/Commands/ExplainCommand.php`
- `src/CLI/Commands/GenerateIntegrationCommand.php`
- `src/Support/ApiSurfaceRegistry.php`
- `tests/Unit/CLICommandMatchesTest.php`
- `docs/demos/foundry-blog-live-demo-script.md`

## Runtime Contracts
- `doctor --ready` preserves existing `doctor` behavior when `--ready` is not provided and executes a deterministic readiness workflow when it is provided.
- `context bootstrap <feature>` initializes missing context artifacts, then inspects and verifies context for the feature.
- `context recover <feature>` runs doctor/alignment/repair/verify as one deterministic recovery flow.
- `spec:promote` resolves one draft spec identity, promotes it to active placement, and runs post-promotion context/feature checks.
- `verify architecture`, `verify feature-work <feature>`, and `verify done --feature=<feature>` provide stable aggregate verification workflows.
- `test feature <feature>` provides a short path for feature-focused test execution with optional full-suite and coverage gates.
- `generate docs --all` runs docs plus inspect-ui generation as one command flow.
- `explain feature <feature> --full` emits a full dossier by aggregating core explain and inspect surfaces.
- Batch workflow aggregate payload includes deterministic fields: `ok`, `status`, `workflow`, `steps`, `failed_step`, `summary`, and `next_actions`.

## Deterministic Outputs
- Workflow steps are executed in declared order and stop on first failure unless the workflow explicitly opts into continue-on-failure.
- Aggregate workflow JSON returns stable field names and ordered step reporting.
- New stable command surfaces are included in API surface registry classification and CLI surface verification probe mapping.

## Tests Added Or Updated
- Added `tests/Unit/BatchWorkflowRunnerTest.php` for failure-stop and continue-on-failure behavior.
- Added `tests/Unit/ApiSurfaceRegistryBatchCommandsTest.php` for stable registry classification of new command surfaces.
- Added `tests/Integration/CLISpecPromoteCommandTest.php` for draft promotion success and missing-target failure behavior.
- Updated `tests/Unit/CLICommandMatchesTest.php` for parsing/matching coverage of newly introduced command signatures and options.

## Verification Commands
```bash
php -l src/CLI/Workflow/BatchWorkflowRunner.php
php -l src/CLI/Commands/ContextBootstrapCommand.php
php -l src/CLI/Commands/ContextRecoverCommand.php
php -l src/CLI/Commands/SpecPromoteCommand.php
php -l src/CLI/Commands/TestFeatureCommand.php
php -l src/CLI/Commands/VerifyArchitectureCommand.php
php -l src/CLI/Commands/VerifyDoneCommand.php
php -l src/CLI/Commands/VerifyFeatureWorkCommand.php
php vendor/bin/phpunit tests/Unit/CLICommandMatchesTest.php tests/Unit/ApiSurfaceRegistryTest.php tests/Unit/ApiSurfaceRegistryBatchCommandsTest.php tests/Unit/BatchWorkflowRunnerTest.php tests/Integration/CLIApplicationTest.php tests/Integration/CLICompletionCommandTest.php tests/Integration/CLISpecPromoteCommandTest.php
```

## Decisions And Tradeoffs
- Implemented batch workflows as first-class CLI commands rather than shell aliases to keep behavior deterministic, inspectable, and testable in framework-owned surfaces.
- Extended existing commands with targeted options (`doctor --ready`, `generate docs --all`, `explain --full`) where that was lower-risk than introducing additional command families.
- Kept child command contracts intact by composing existing command handlers/services rather than shelling out to nested CLI processes.

## Reconstruction Notes
- A temporary standalone `DoctorReadyCommand` was created during implementation exploration, then removed to avoid ambiguity with the canonical `doctor` command signature.
- The final design keeps readiness behavior under `doctor --ready`, preserving user expectations and minimizing command-surface churn.

## Follow-up Dependencies
- None required for this spec.
