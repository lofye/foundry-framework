# Implementation Plan: 013-upgrade-safety-tools

## Historical Provenance

- Imported spec path: `Modules/PreCanonical/specs/013-upgrade-safety-tools.md`
- Source archive: `_import/pre-canonical-specs.md`
- Legacy name: `13 — Upgrade Safety Tools`
- Legacy id: `13`
- Canonical pre-canonical id: `013`

## Historical Specification Summary

The original pre-canonical specification body is preserved in the imported execution spec. This reconstruction note records adjacent marked context and result evidence without inferring modern module ownership.

## Historical Preamble Context

### Preamble Block 1

- Name: `13 — Upgrade Safety Tools`

A real 1.0 framework needs a safe upgrade story. Users should be able to understand whether their app is ready for a new framework version before blindly upgrading.

Spec 13 introduces upgrade-check and deprecation safety tools.

All new code must maintain ≥ 90% automated test coverage.

## Historical Implementation Evidence

### Result Block 1

- Name: `13 — Upgrade Safety Tools`

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
