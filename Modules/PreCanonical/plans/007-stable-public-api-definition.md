# Implementation Plan: 007-stable-public-api-definition

## Historical Provenance

- Imported spec path: `Modules/PreCanonical/specs/007-stable-public-api-definition.md`
- Source archive: `_import/pre-canonical-specs.md`
- Legacy name: `7 - Stable Public API Definition`
- Legacy id: `7`
- Canonical pre-canonical id: `007`

## Historical Specification Summary

The original pre-canonical specification body is preserved in the imported execution spec. This reconstruction note records adjacent marked context and result evidence without inferring modern module ownership.

## Historical Preamble Context

### Preamble Block 1

- Name: `7 - Stable Public API Definition`

Preface

Foundry has reached the point where it needs a clearly defined distinction between:
	•	public, supported framework surface
	•	internal, changeable implementation details

Without this distinction, every release risks accidental breakage, documentation ambiguity, and extension instability.

Spec 7 defines Foundry’s stable public API surface so that users, extension authors, and future maintainers know what is safe to depend on.

All new code must maintain ≥ 90% automated test coverage.

## Historical Implementation Evidence

### Result Block 1

- Name: `7 - Stable Public API Definition`

RESULT

Implemented Spec 7 around a single registry in [ApiSurfaceRegistry.php](/Users/lo_fye/code/foundry-framework/src/Support/ApiSurfaceRegistry.php#L1). It now classifies PHP namespace rules, exact extension hooks, CLI commands, config/manifests, generated metadata, and the semver policy attached to each surface. That registry is wired into CLI help in [Application.php](/Users/lo_fye/code/foundry-framework/src/CLI/Application.php#L43), exposed via `inspect api-surface` in [InspectGraphCommand.php](/Users/lo_fye/code/foundry-framework/src/CLI/Commands/InspectGraphCommand.php#L21), and reused by docs generation in [GraphDocsGenerator.php](/Users/lo_fye/code/foundry-framework/src/Documentation/GraphDocsGenerator.php#L11) so `generate docs` now emits `api-surface` and `cli-reference` pages from the same source of truth.

The human-facing policy is documented in [docs/public-api-policy.md](/Users/lo_fye/code/foundry-framework/docs/public-api-policy.md#L1), and the CLI/readme entry points were updated in [README.md](/Users/lo_fye/code/foundry-framework/README.md#L176). Coverage for symbol detection, CLI stability/help exposure, and generated docs was added in [ApiSurfaceRegistryTest.php](/Users/lo_fye/code/foundry-framework/tests/Unit/ApiSurfaceRegistryTest.php#L1), [CLIApplicationTest.php](/Users/lo_fye/code/foundry-framework/tests/Integration/CLIApplicationTest.php#L104), and the existing docs/CLI tests.

Verification: `php vendor/bin/phpunit` passed (`225` tests, `1315` assertions, `3` skipped). I also ran `php bin/foundry help --json`, `php bin/foundry help graph visualize --json`, `php bin/foundry inspect api-surface --command='compile graph' --json`, and `php bin/foundry generate docs --format=markdown --json`.

12 files changed
+1096
-3
Undo
README.md
docs/public-api-policy.md
src/CLI/Application.php
src/CLI/CommandContext.php
src/CLI/Commands/InspectGraphCommand.php
src/Documentation/GraphDocsGenerator.php
src/Support/ApiSurfaceRegistry.php
tests/Integration/CLIApplicationTest.php
tests/Integration/CLIIntegrationCommandsTest.php
tests/Unit/ApiSurfaceRegistryTest.php
tests/Unit/CLICommandMatchesTest.php
tests/Unit/GraphDocsGeneratorTest.php

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
