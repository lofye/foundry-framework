# Implementation Plan: 009-framework-doctor-and-environment-diagnostics

## Historical Provenance

- Imported spec path: `Modules/PreCanonical/specs/009-framework-doctor-and-environment-diagnostics.md`
- Source archive: `_import/pre-canonical-specs.md`
- Legacy name: `9 — Framework Doctor and Environment Diagnostics`
- Legacy id: `9`
- Canonical pre-canonical id: `009`

## Historical Specification Summary

The original pre-canonical specification body is preserved in the imported execution spec. This reconstruction note records adjacent marked context and result evidence without inferring modern module ownership.

## Historical Preamble Context

### Preamble Block 1

- Name: `9 — Framework Doctor and Environment Diagnostics`

A mature framework should be able to explain what is wrong with an installation or app environment before users have to dig through stack traces.

Foundry already points in this direction. Spec 9 turns foundry doctor into a robust environment and architecture diagnostics tool suitable for 1.0.

All new code must maintain ≥ 90% automated test coverage.

## Historical Implementation Evidence

### Result Block 1

- Name: `9 — Framework Doctor and Environment Diagnostics`

RESULT

Expanded `foundry doctor` into a framework-level diagnostics pipeline in [`src/CLI/Commands/DoctorCommand.php`](/Users/lo_fye/code/foundry-framework/src/CLI/Commands/DoctorCommand.php) and [`src/Doctor/FrameworkDoctor.php`](/Users/lo_fye/code/foundry-framework/src/Doctor/FrameworkDoctor.php). It now runs built-in checks for runtime compatibility, install completeness, writable/build directories, extension compatibility, graph artifact integrity, metadata freshness, and pipeline consistency, while keeping the existing architecture analyzers. New diagnostics carry stable codes plus `why_it_matters` and `suggested_fix`, and non-`--json` mode now renders a real text report instead of dumping raw payload JSON.

App/extension doctor hooks are now part of the extension API via [`src/Doctor/DoctorCheck.php`](/Users/lo_fye/code/foundry-framework/src/Doctor/DoctorCheck.php) and the new `doctorChecks()` hook on [`src/Compiler/Extensions/CompilerExtension.php`](/Users/lo_fye/code/foundry-framework/src/Compiler/Extensions/CompilerExtension.php). Pipeline verification was refactored onto [`src/Pipeline/PipelineIntegrityInspector.php`](/Users/lo_fye/code/foundry-framework/src/Pipeline/PipelineIntegrityInspector.php) so `verify pipeline` and `doctor` share the same structural checks. I also updated the temp app fixture and added coverage around custom doctor checks and human/JSON output in [`tests/Unit/FrameworkDoctorTest.php`](/Users/lo_fye/code/foundry-framework/tests/Unit/FrameworkDoctorTest.php), [`tests/Unit/PipelineIntegrityInspectorTest.php`](/Users/lo_fye/code/foundry-framework/tests/Unit/PipelineIntegrityInspectorTest.php), and [`tests/Integration/CLIArchitectureToolsCommandsTest.php`](/Users/lo_fye/code/foundry-framework/tests/Integration/CLIArchitectureToolsCommandsTest.php).

Verification: `php vendor/bin/phpunit` passed with `233` tests, `1386` assertions, `3` skipped. I also ran `XDEBUG_MODE=coverage php vendor/bin/phpunit --coverage-text`, but coverage could not be verified in this environment because no coverage driver is installed (`xdebug`/`pcov` not loaded).

31 files changed
+2019
-161
Undo
docs/architecture-tools.md
src/CLI/Application.php
src/CLI/CommandContext.php
src/CLI/Commands/DoctorCommand.php
src/CLI/Commands/VerifyPipelineCommand.php
src/Compiler/Analysis/ArchitectureDoctor.php
src/Compiler/Diagnostics/Diagnostic.php
src/Compiler/Diagnostics/DiagnosticBag.php
src/Compiler/Extensions/AbstractCompilerExtension.php
src/Compiler/Extensions/CompilerExtension.php
src/Compiler/Extensions/ExtensionRegistry.php
src/Doctor/Checks/CompileHealthCheck.php
src/Doctor/Checks/DirectoryHealthCheck.php
src/Doctor/Checks/ExtensionCompatibilityCheck.php
src/Doctor/Checks/GraphIntegrityCheck.php
src/Doctor/Checks/InstallCompletenessCheck.php
src/Doctor/Checks/MetadataFreshnessCheck.php
src/Doctor/Checks/PipelineConsistencyCheck.php
src/Doctor/Checks/RuntimeCompatibilityCheck.php
src/Doctor/DoctorCheck.php
src/Doctor/DoctorContext.php
src/Doctor/DoctorSummary.php
src/Doctor/FrameworkDoctor.php
src/Pipeline/PipelineIntegrityInspector.php
src/Support/ApiSurfaceRegistry.php
tests/Fixtures/CustomDoctorCheck.php
tests/Fixtures/CustomDoctorExtension.php
tests/Fixtures/TempProject.php
tests/Integration/CLIArchitectureToolsCommandsTest.php
tests/Unit/FrameworkDoctorTest.php
tests/Unit/PipelineIntegrityInspectorTest.php

=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-
=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-
=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-
=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-
=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-

## Historical Verification Evidence

Historical verification details, when present, are preserved verbatim inside the paired result blocks above.

## Historical Stabilization Notes

Historical stabilization details, when present, are preserved verbatim inside the paired result blocks above.

## Current Repository Alignment

The imported artifact is intentionally retained under `Modules/PreCanonical` as archive-lineage context. Modern module ownership remains deferred until a separate explicit alignment spec maps the pre-canonical intent into current modules.

## Uncertainty And Reconstruction Notes

No modern module inference was performed. The generated note preserves only the marked archive relationships available through `S`, `R`, and `P` blocks.
