# Implementation Plan: 032-hosted-pack-registry-aka-read-only-public-no-auth-marketplace

## Historical Provenance

- Imported spec path: `Modules/PreCanonical/specs/032-hosted-pack-registry-aka-read-only-public-no-auth-marketplace.md`
- Source archive: `_import/pre-canonical-specs.md`
- Legacy name: `32 — Hosted Pack Registry (aka read-only, public, no auth Marketplace)`
- Legacy id: `32`
- Canonical pre-canonical id: `032`

## Historical Specification Summary

The original pre-canonical specification body is preserved in the imported execution spec. This reconstruction note records adjacent marked context and result evidence without inferring modern module ownership.

## Historical Preamble Context

### Preamble Block 1

- Name: `32 — Hosted Pack Registry (aka read-only, public, no auth Marketplace)`

Purpose

Introduce a minimal hosted registry that allows Foundry to:
	•	discover packs remotely
	•	search available packs
	•	install packs via download URLs

This spec is intentionally simple and must:
	•	require no authentication
	•	require no payments
	•	remain fully optional (offline-first still works via Spec 31)

⸻

Core Principle

The hosted registry is a read-only index, not a control system.

It provides discovery and metadata only.
All installation and execution logic remains local.

## Historical Implementation Evidence

### Result Block 1

- Name: `32 — Hosted Pack Registry (aka read-only, public, no auth Marketplace)`

RESULT

Spec 32 is implemented. Foundry now has a minimal hosted pack registry client in [HostedPackRegistry.php](/Users/lo_fye/code/foundry-framework/src/Packs/HostedPackRegistry.php) and [HostedPackRegistryEntry.php](/Users/lo_fye/code/foundry-framework/src/Packs/HostedPackRegistryEntry.php), plus secure zip extraction in [PackArchiveExtractor.php](/Users/lo_fye/code/foundry-framework/src/Packs/PackArchiveExtractor.php). `pack install` now resolves local paths first and valid `vendor/pack` names second through the hosted registry inside [PackManager.php](/Users/lo_fye/code/foundry-framework/src/Packs/PackManager.php), so remote installs still reuse the same local install/activation pipeline from Spec 31 after download and extraction. The CLI now supports `foundry pack search <query>` and hosted installs in [PackCommand.php](/Users/lo_fye/code/foundry-framework/src/CLI/Commands/PackCommand.php), with help/discovery/docs surfaced through [ApiSurfaceRegistry.php](/Users/lo_fye/code/foundry-framework/src/Support/ApiSurfaceRegistry.php), [Application.php](/Users/lo_fye/code/foundry-framework/src/CLI/Application.php), and [CommandCatalog.php](/Users/lo_fye/code/foundry-framework/src/Documentation/CommandCatalog.php).

The hosted layer stays read-only and optional: it reads a public `registry.json`, chooses the highest semver deterministically, requires HTTPS `download_url` values, caches registry metadata at `.foundry/cache/registry.json`, and leaves local/offline pack installs unchanged when the registry is unavailable. I also updated the framework/app docs in [README.md](/Users/lo_fye/code/foundry-framework/README.md), [APP-README.md](/Users/lo_fye/code/foundry-framework/APP-README.md), [APP-AGENTS.md](/Users/lo_fye/code/foundry-framework/APP-AGENTS.md), [extension-author-guide.md](/Users/lo_fye/code/foundry-framework/docs/extension-author-guide.md), and [extensions-and-migrations.md](/Users/lo_fye/code/foundry-framework/docs/extensions-and-migrations.md), and added coverage in [HostedPackRegistryTest.php](/Users/lo_fye/code/foundry-framework/tests/Unit/HostedPackRegistryTest.php), [PackArchiveExtractorTest.php](/Users/lo_fye/code/foundry-framework/tests/Unit/PackArchiveExtractorTest.php), and [CLIPackCommandsTest.php](/Users/lo_fye/code/foundry-framework/tests/Integration/CLIPackCommandsTest.php).

Verification passed:
- `php bin/foundry verify cli-surface --json` -> `128` valid, `0` invalid, `0` ambiguous, `0` orphan handlers, coverage `1`
- `php bin/foundry verify graph --json` -> `ok: true`
- `php vendor/bin/phpunit` -> `386` tests passed, `3062` assertions

19 files changed
+1372
-33
Undo
APP-AGENTS.md
APP-README.md
README.md
docs/extension-author-guide.md
docs/extensions-and-migrations.md
src/CLI/Application.php
src/CLI/Commands/PackCommand.php
src/Documentation/CommandCatalog.php
src/Packs/HostedPackRegistry.php
src/Packs/HostedPackRegistryEntry.php
src/Packs/PackArchiveExtractor.php
src/Packs/PackManager.php
src/Support/ApiSurfaceRegistry.php
tests/Integration/CLIApplicationTest.php
tests/Integration/CLIPackCommandsTest.php
tests/Unit/ApiSurfaceRegistryTest.php
tests/Unit/CLICommandMatchesTest.php
tests/Unit/HostedPackRegistryTest.php
tests/Unit/PackArchiveExtractorTest.php


=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-
=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-
=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-
=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-
=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-

BUILD ORDER:
33, 34, 35, 30D, 30E, 30F, 30G, 36, 37, 38, 39, 40, 41, 42, 43, 44, 44B, 45A, 45B, 45C, 46, 47

## Historical Verification Evidence

Historical verification details, when present, are preserved verbatim inside the paired result blocks above.

## Historical Stabilization Notes

Historical stabilization details, when present, are preserved verbatim inside the paired result blocks above.

## Current Repository Alignment

The imported artifact is intentionally retained under `Modules/PreCanonical` as archive-lineage context. Modern module ownership remains deferred until a separate explicit alignment spec maps the pre-canonical intent into current modules.

## Uncertainty And Reconstruction Notes

No modern module inference was performed. The generated note preserves only the marked archive relationships available through `S`, `R`, and `P` blocks.
