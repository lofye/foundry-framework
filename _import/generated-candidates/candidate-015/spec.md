# Spec 13 — Upgrade Safety Tools

Preface

A real 1.0 framework needs a safe upgrade story. Users should be able to understand whether their app is ready for a new framework version before blindly upgrading.

Spec 13 introduces upgrade-check and deprecation safety tools.

All new code must maintain ≥ 90% automated test coverage.

Goals

Spec 13 must:
	•	help users assess upgrade readiness
	•	detect deprecated usage
	•	detect incompatible extension usage
	•	reduce fear of framework upgrades

Requirements

1. Upgrade-check command

Provide a command such as:

foundry upgrade-check

with machine-readable output support.

2. Detectable issues

The upgrade-check system should detect at least:
	•	deprecated APIs or config
	•	removed/changed CLI usage
	•	incompatible extensions/packs
	•	unsupported schema versions
	•	risky graph/compiler changes where detectable

3. Actionable reporting

Reports should show:
	•	what is affected
	•	why it matters
	•	what version introduced the issue
	•	how to migrate

4. Deprecation metadata

Support framework-level deprecation metadata so the tool has structured information to work from.

5. Docs integration

Upgrade guidance should be documentable and referenceable.

Deliverables
	•	upgrade-check command
	•	deprecation metadata system
	•	actionable upgrade reports
	•	extension compatibility checks

Testing Requirements

Tests must cover:
	•	deprecated usage detection
	•	extension incompatibility detection
	•	JSON output
	•	report quality
	•	version-aware behavior

Coverage must remain ≥ 90%.

==========================================================================================

RESULT

**Changes**
- Added `foundry upgrade-check` in [UpgradeCheckCommand.php](/Users/lo_fye/code/foundry-framework/src/CLI/Commands/UpgradeCheckCommand.php) and wired it through [Application.php](/Users/lo_fye/code/foundry-framework/src/CLI/Application.php), [CommandContext.php](/Users/lo_fye/code/foundry-framework/src/CLI/CommandContext.php), and [ApiSurfaceRegistry.php](/Users/lo_fye/code/foundry-framework/src/Support/ApiSurfaceRegistry.php). It supports `--json` and optional `--target=<version>`, defaulting to `1.0.0` when the current framework is pre-1.0 or `dev-main`.
- Implemented a dedicated upgrade-analysis subsystem in [src/Upgrade](/Users/lo_fye/code/foundry-framework/src/Upgrade) centered on [UpgradeAnalyzer.php](/Users/lo_fye/code/foundry-framework/src/Upgrade/UpgradeAnalyzer.php) plus structured deprecation metadata. The report now covers deprecated config aliases, legacy `init app` CLI usage, feature manifest v1 migration readiness, extension/pack incompatibilities, and legacy projection fallback risk, with `affected`, `why_it_matters`, `introduced_in`, `migration`, and `reference` fields.
- Added docs integration in [GraphDocsGenerator.php](/Users/lo_fye/code/foundry-framework/src/Documentation/GraphDocsGenerator.php) via generated `upgrade-reference` output, and added the source guide [upgrade-safety.md](/Users/lo_fye/code/foundry-framework/docs/upgrade-safety.md). README command/docs references were updated in [README.md](/Users/lo_fye/code/foundry-framework/README.md).

**Verification**
- Focused PHPUnit suites passed for the new behavior and adjacent CLI/docs surfaces, including [UpgradeAnalyzerTest.php](/Users/lo_fye/code/foundry-framework/tests/Unit/UpgradeAnalyzerTest.php), [CLIUpgradeCheckCommandTest.php](/Users/lo_fye/code/foundry-framework/tests/Integration/CLIUpgradeCheckCommandTest.php), [CLICommandMatchesTest.php](/Users/lo_fye/code/foundry-framework/tests/Unit/CLICommandMatchesTest.php), [CLIApplicationTest.php](/Users/lo_fye/code/foundry-framework/tests/Integration/CLIApplicationTest.php), [GraphDocsGeneratorTest.php](/Users/lo_fye/code/foundry-framework/tests/Unit/GraphDocsGeneratorTest.php), [CLIGraphCommandsTest.php](/Users/lo_fye/code/foundry-framework/tests/Integration/CLIGraphCommandsTest.php), [CLIArchitectureToolsCommandsTest.php](/Users/lo_fye/code/foundry-framework/tests/Integration/CLIArchitectureToolsCommandsTest.php), [CompatibilityCheckerTest.php](/Users/lo_fye/code/foundry-framework/tests/Unit/CompatibilityCheckerTest.php), and [ConfigSchemaValidationTest.php](/Users/lo_fye/code/foundry-framework/tests/Unit/ConfigSchemaValidationTest.php).
- I did not run the full PHPUnit suite. I also tried [ForbiddenInternalTerminologyTest.php](/Users/lo_fye/code/foundry-framework/tests/Phrasing/ForbiddenInternalTerminologyTest.php), but it currently errors independently of this change because it resolves SPL iterator classes inside the test namespace (`Foundry\Tests\Phrasing\RecursiveIteratorIterator`).

19 files changed
+1431
-1
Undo
README.md
docs/upgrade-safety.md
src/CLI/Application.php
src/CLI/CommandContext.php
src/CLI/Commands/UpgradeCheckCommand.php
src/Documentation/GraphDocsGenerator.php
src/Support/ApiSurfaceRegistry.php
src/Upgrade/DeprecationMetadata.php
src/Upgrade/FrameworkDeprecationRegistry.php
src/Upgrade/UpgradeAnalyzer.php
src/Upgrade/UpgradeIssue.php
src/Upgrade/UpgradeReport.php
src/Upgrade/VersionComparator.php
tests/Fixtures/CustomUpgradeExtension.php
tests/Integration/CLIApplicationTest.php
tests/Integration/CLIUpgradeCheckCommandTest.php
tests/Unit/CLICommandMatchesTest.php
tests/Unit/GraphDocsGeneratorTest.php
tests/Unit/UpgradeAnalyzerTest.php
