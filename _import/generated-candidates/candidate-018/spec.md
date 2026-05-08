# Spec 16 — Foundry Pro (CLI + Licensing Layer)

Preface

Foundry is an open-source framework (MIT) intended to remain fully usable without restriction.

However, additional tooling may be provided to enhance:
	•	developer insight
	•	debugging capabilities
	•	architecture understanding
	•	AI-assisted workflows

These capabilities will be delivered through a Foundry Pro layer.

The Pro layer must:
	•	not restrict core framework usage
	•	not introduce operational dependencies (e.g., SaaS requirement)
	•	remain optional and additive

All new code must maintain ≥ 90% automated test coverage.

⸻

Goals

Spec 16 must:
	•	introduce a Pro feature layer without impacting core functionality
	•	provide a minimal, local-first licensing mechanism
	•	cleanly separate Pro features from core Foundry
	•	ensure Pro features degrade gracefully when not enabled

⸻

Requirements

1. Separation of concerns

Pro functionality must be isolated from core framework logic.

Acceptable approaches include:
	•	separate package (e.g., foundry-pro)
	•	or clearly isolated module within the repo

Core Foundry must not depend on Pro.

⸻

2. Licensing model (local-first)

Implement a simple license mechanism:
	•	license key stored locally (e.g., ~/.foundry/license.json)
	•	no required external API calls
	•	no runtime dependency on external services

CLI command:

foundry pro enable <license-key>

Behavior:
	•	valid key → enables Pro features
	•	no/invalid key → Pro features unavailable

⸻

3. Feature gating

Pro-only commands must include:
	•	deep diagnostics
	•	architecture explanation
	•	graph diffing
	•	trace analysis
	•	AI-assisted generation (see Spec 17)

If a Pro command is used without a valid license:
	•	output a clear message
	•	exit with non-zero status
	•	do not crash or degrade core functionality

⸻

4. CLI integration

Introduce a Pro namespace or command group:

foundry pro

Subcommands may include:

foundry doctor --deep
foundry explain <target>
foundry diff
foundry trace <target>
foundry generate "<prompt>"

⸻

5. Graceful degradation

Without Pro:
	•	commands may still appear in help
	•	but clearly marked as Pro
	•	execution blocked with informative message

⸻

6. No telemetry requirement

The Pro system must not require telemetry or usage tracking.

⸻

7. Documentation

Docs must clearly explain:
	•	what Pro is
	•	what is free vs paid
	•	that core framework is fully usable without Pro

⸻

Deliverables
	•	Pro feature layer (isolated from core)
	•	local license system
	•	CLI integration for Pro commands
	•	graceful fallback behavior
	•	documentation updates

⸻

Testing Requirements

Tests must cover:
	•	license validation
	•	Pro feature gating
	•	CLI behavior with/without license
	•	failure messaging
	•	isolation from core functionality

Coverage must remain ≥ 90%.

⸻⸻⸻⸻⸻⸻⸻⸻⸻⸻⸻⸻⸻⸻⸻⸻⸻⸻⸻⸻---

RESULT (Spec 16)

Implemented Spec 16 as an isolated Pro module under [src/Pro](/Users/lo_fye/code/foundry-framework/src/Pro). The core runtime/compiler behavior stays untouched; the only core-facing changes are CLI registration in [src/CLI/Application.php](/Users/lo_fye/code/foundry-framework/src/CLI/Application.php) and help/API metadata in [src/Support/ApiSurfaceRegistry.php](/Users/lo_fye/code/foundry-framework/src/Support/ApiSurfaceRegistry.php).

The new Pro layer adds a local-first license store and validator, plus gated commands for `pro enable`, `pro status`, `doctor --deep`, `explain`, `diff`, `trace`, and `generate "<prompt>"`. Unlicensed use now fails cleanly with a clear non-zero Pro error, while help still shows those commands marked as Pro. The default license path is `~/.foundry/license.json`, with testable env overrides via `FOUNDRY_HOME` or `FOUNDRY_LICENSE_PATH`.

Coverage and docs were updated too: new unit/integration coverage lives in [tests/Unit/ProLicenseTest.php](/Users/lo_fye/code/foundry-framework/tests/Unit/ProLicenseTest.php), [tests/Unit/ProAnalysisToolsTest.php](/Users/lo_fye/code/foundry-framework/tests/Unit/ProAnalysisToolsTest.php), and [tests/Integration/CLIProCommandsTest.php](/Users/lo_fye/code/foundry-framework/tests/Integration/CLIProCommandsTest.php). Documentation now explains free vs Pro and the no-telemetry local license model in [README.md](/Users/lo_fye/code/foundry-framework/README.md) and [docs/architecture-tools.md](/Users/lo_fye/code/foundry-framework/docs/architecture-tools.md).

Verification: `php vendor/bin/phpunit` passed, `303` tests, `2266` assertions, `3` skipped.

24 files changed
+1993
-4
Undo
README.md
docs/architecture-tools.md
src/CLI/Application.php
src/Pro/ArchitectureExplainer.php
src/Pro/CLI/Concerns/InteractsWithPro.php
src/Pro/CLI/DeepDoctorCommand.php
src/Pro/CLI/DiffCommand.php
src/Pro/CLI/ExplainCommand.php
src/Pro/CLI/GenerateCommand.php
src/Pro/CLI/ProCommand.php
src/Pro/CLI/TraceCommand.php
src/Pro/DeepDiagnosticsBuilder.php
src/Pro/FeatureGate.php
src/Pro/GraphDiffAnalyzer.php
src/Pro/LicenseStore.php
src/Pro/LicenseValidator.php
src/Pro/TraceAnalyzer.php
src/Support/ApiSurfaceRegistry.php
tests/Integration/CLIApplicationTest.php
tests/Integration/CLIProCommandsTest.php
tests/Unit/ApiSurfaceRegistryTest.php
tests/Unit/CLICommandMatchesTest.php
tests/Unit/ProAnalysisToolsTest.php
tests/Unit/ProLicenseTest.php
