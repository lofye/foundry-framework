Spec 9 — Framework Doctor and Environment Diagnostics

Preface

A mature framework should be able to explain what is wrong with an installation or app environment before users have to dig through stack traces.

Foundry already points in this direction. Spec 9 turns foundry doctor into a robust environment and architecture diagnostics tool suitable for 1.0.

All new code must maintain ≥ 90% automated test coverage.

Goals

Spec 9 must:
	•	strengthen foundry doctor
	•	make installation problems obvious
	•	validate architecture integrity
	•	validate runtime environment assumptions
	•	produce machine-readable diagnostics

Requirements

1. Doctor command expansion

foundry doctor should validate at least:
	•	PHP version compatibility
	•	required extensions
	•	writable/cache/build directories
	•	framework install completeness
	•	extension compatibility
	•	graph integrity
	•	config/schema validity
	•	route/pipeline consistency
	•	generated metadata freshness where applicable

2. Structured output

Doctor output should support:
	•	human-readable mode
	•	JSON mode
	•	clear exit codes

Example:

foundry doctor
foundry doctor --json

3. Diagnostic codes

Use stable diagnostic codes for major failure categories so they can be:
	•	documented
	•	searched
	•	consumed by tools/LLMs

4. Fix guidance

Where practical, diagnostics should include:
	•	what failed
	•	why it matters
	•	how to fix it

5. App-level extensibility

Allow applications and extensions to register additional doctor checks.

Deliverables
	•	stronger foundry doctor
	•	structured JSON diagnostics
	•	stable diagnostic codes
	•	fix guidance
	•	app/extension diagnostic hook support

Testing Requirements

Tests must cover:
	•	doctor checks
	•	JSON output
	•	exit codes
	•	extension/app-added checks
	•	representative failure scenarios

Coverage must remain ≥ 90%.

==========================================================================================

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
