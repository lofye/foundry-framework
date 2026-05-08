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
