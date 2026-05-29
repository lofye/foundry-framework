# Implementation Plan: 016-foundry-pro-cli-licensing-layer

## Historical Provenance

- Imported spec path: `Modules/PreCanonical/specs/016-foundry-pro-cli-licensing-layer.md`
- Source archive: `_import/pre-canonical-specs.md`
- Legacy name: `16 — Foundry Pro (CLI + Licensing Layer)`
- Legacy id: `16`
- Canonical pre-canonical id: `016`

## Historical Specification Summary

The original pre-canonical specification body is preserved in the imported execution spec. This reconstruction note records adjacent marked context and result evidence without inferring modern module ownership.

## Historical Preamble Context

### Preamble Block 1

- Name: `16 — Foundry Pro (CLI + Licensing Layer)`

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

## Historical Implementation Evidence

### Result Block 1

- Name: `16 — Foundry Pro (CLI + Licensing Layer)`

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
