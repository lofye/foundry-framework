Master Spec 19J — Tooling, Observability, and Enforcement Layer

⸻

Preface

Foundry already provides:
	•	semantic compiler
	•	canonical graph
	•	execution pipeline
	•	graph-native diagnostics

However, it currently lacks:
	•	static enforcement (PHPStan, Pint, Rector)
	•	runtime observability tooling
	•	temporal comparison systems
	•	integrated quality signals

This spec upgrades Foundry from:

“architecturally strong”

to:

operationally and measurably strong

⸻

Goals
	1.	Add mandatory dev enforcement stack
	2.	Add graph-aware observability
	3.	Enable temporal comparison
	4.	Improve bug-fixing loops
	5.	Improve LLM reliability
	6.	Integrate with CLI integrity layer (Spec 19I)

⸻

Non-Goals
	•	no mandatory Xdebug in production
	•	no unsafe auto-refactoring
	•	no runtime magic

⸻

1. Dev Enforcement Stack

Required Dependencies

Add:
	•	PHPStan
	•	Pint
	•	Rector

⸻

Composer Scripts

composer lint
composer lint:fix
composer analyse
composer refactor
composer quality
composer quality:strict

⸻

2. Config Files

phpstan.neon
	•	moderate strictness
	•	baseline allowed but discouraged long-term

⸻

pint.json
	•	consistent formatting
	•	LLM-friendly code shape

⸻

rector.php
	•	conservative rules only

⸻

3. Doctor Extensions

New Modes

foundry doctor --static
foundry doctor --style
foundry doctor --quality

⸻

Output Must Include
	•	compiler diagnostics
	•	doctor diagnostics
	•	static analysis
	•	style violations
	•	test summary (optional)

⸻

4. Observability System

⸻

4.1 Trace Command

foundry observe:trace

Captures:
	•	request lifecycle
	•	pipeline stages
	•	guards
	•	interceptors

Mapped to graph concepts.

⸻

4.2 Profile Command

foundry observe:profile

Captures:
	•	timing
	•	memory
	•	hotspots

⸻

4.3 Compare Command

foundry observe:compare <run-a> <run-b>

Outputs:
	•	regressions
	•	performance changes
	•	changed execution paths

⸻

4.4 History Command

foundry history

Lists:
	•	prior runs
	•	builds
	•	profiles

⸻

4.5 Regressions Command

foundry regressions

Detects:
	•	new failures
	•	performance regressions
	•	static-analysis regressions

⸻

5. Build Artifacts

Extend:

app/.foundry/build/

Add:
	•	quality/
	•	observability/
	•	history/

Must store:
	•	PHPStan output
	•	Pint output
	•	trace summaries
	•	profile summaries
	•	run comparisons

⸻

6. Graph-Aware Mapping

All observability data must map to:
	•	features
	•	execution plans
	•	guards
	•	interceptors

This is mandatory.

⸻

7. Bug-Fix Loop Support

Foundry must support:
	1.	failure detection
	2.	evidence collection
	3.	localization
	4.	suggested fixes
	5.	re-validation

⸻

8. CLI Integration (Spec 19I)

All new commands must:
	•	be registered in handler layer
	•	be declared in ApiSurfaceRegistry
	•	pass verify cli-surface

⸻

9. CI Requirements

CI must run:
	•	Pint
	•	PHPStan
	•	PHPUnit
	•	foundry doctor --quality
	•	foundry verify cli-surface

⸻

10. Human Experience

Must feel:
	•	strict but not punishing
	•	transparent
	•	debuggable

⸻

11. LLM Experience

Must provide:
	•	structured outputs
	•	deterministic feedback
	•	minimal ambiguity

⸻

Acceptance Criteria
	1.	PHPStan, Pint, Rector installed and configured
	2.	new doctor modes implemented
	3.	observability commands implemented
	4.	artifacts persisted deterministically
	5.	graph-aware mapping exists
	6.	CI enforces all checks
	7.	CLI integrity maintained via Spec 19I

⸻

🧭 Final Recommendation

Implement in this order:
	1.	Spec 19I — CLI Surface Integrity
	2.	Spec 19J — Tooling & Observability
	3.	Then proceed to Specs 20–24

==========================================================================================

RESULT

**Implemented**
Spec 19J is wired through the framework now. The enforcement stack and scripts live in [composer.json](/Users/lo_fye/code/foundry-framework/composer.json#L13), with repo configs in [phpstan.neon](/Users/lo_fye/code/foundry-framework/phpstan.neon#L1), [phpstan-baseline.neon](/Users/lo_fye/code/foundry-framework/phpstan-baseline.neon), [pint.json](/Users/lo_fye/code/foundry-framework/pint.json#L1), [rector.php](/Users/lo_fye/code/foundry-framework/rector.php#L1), and CI in [.github/workflows/quality.yml](/Users/lo_fye/code/foundry-framework/.github/workflows/quality.yml#L1). `doctor` now supports `--static`, `--style`, `--quality`, and optional test summaries in [src/CLI/Commands/DoctorCommand.php](/Users/lo_fye/code/foundry-framework/src/CLI/Commands/DoctorCommand.php#L50).

The observability/history layer is in [src/CLI/Commands/ObserveCommand.php](/Users/lo_fye/code/foundry-framework/src/CLI/Commands/ObserveCommand.php#L17), [src/CLI/Commands/HistoryCommand.php](/Users/lo_fye/code/foundry-framework/src/CLI/Commands/HistoryCommand.php#L11), and [src/CLI/Commands/RegressionsCommand.php](/Users/lo_fye/code/foundry-framework/src/CLI/Commands/RegressionsCommand.php#L12), with deterministic artifact storage in [src/Compiler/BuildLayout.php](/Users/lo_fye/code/foundry-framework/src/Compiler/BuildLayout.php#L38) and [src/Tooling/BuildArtifactStore.php](/Users/lo_fye/code/foundry-framework/src/Tooling/BuildArtifactStore.php#L18). CLI-surface registration was updated in [src/Support/ApiSurfaceRegistry.php](/Users/lo_fye/code/foundry-framework/src/Support/ApiSurfaceRegistry.php#L339). I also added focused coverage in [tests/Integration/CLIQualityAndObservabilityCommandsTest.php](/Users/lo_fye/code/foundry-framework/tests/Integration/CLIQualityAndObservabilityCommandsTest.php), [tests/Unit/QualityToolRunnerTest.php](/Users/lo_fye/code/foundry-framework/tests/Unit/QualityToolRunnerTest.php), and [tests/Unit/ObservationComparatorTest.php](/Users/lo_fye/code/foundry-framework/tests/Unit/ObservationComparatorTest.php).

**Verification**
- `php vendor/bin/phpunit` passed: 362 tests, 2842 assertions, 3 skipped.
- `composer quality` passed.
- `php bin/foundry verify cli-surface --json` passed.
- `php bin/foundry compile graph --json`, `history`, `observe:trace`, `observe:profile`, and `observe:compare` all worked.
- `php bin/foundry regressions --json` correctly detected a profile memory delta between two local runs, so it exited non-zero by design.

**Notes**
Enabling Pint required a repo-wide normalization pass, so the diff is large. I also left the pre-existing staged docs changes untouched, and I did not run `composer quality:strict` because current doctor warnings in the demo app would make strict mode fail intentionally.

26 files changed
+2658
-44
Undo
.github/workflows/quality.yml
composer.json
phpstan.neon
pint.json
rector.php
src/CLI/Application.php
src/CLI/Commands/CompileGraphCommand.php
src/CLI/Commands/DoctorCommand.php
src/CLI/Commands/HistoryCommand.php
src/CLI/Commands/ObserveCommand.php
src/CLI/Commands/RegressionsCommand.php
src/Compiler/BuildLayout.php
src/Doctor/Checks/DirectoryHealthCheck.php
src/Observability/GraphExecutionMap.php
src/Observability/ObservationComparator.php
src/Observability/ProfileObserver.php
src/Observability/TraceObserver.php
src/Quality/QualityToolRunner.php
src/Support/ApiSurfaceRegistry.php
src/Tooling/BuildArtifactStore.php
src/Tooling/ProcessRunner.php
tests/Integration/CLIQualityAndObservabilityCommandsTest.php
tests/Unit/ApiSurfaceRegistryTest.php
tests/Unit/CLICommandMatchesTest.php
tests/Unit/ObservationComparatorTest.php
tests/Unit/QualityToolRunnerTest.php
