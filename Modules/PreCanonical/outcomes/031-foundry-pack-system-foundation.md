# Implementation Plan: 031-foundry-pack-system-foundation

## Historical Provenance

- Imported spec path: `Modules/PreCanonical/specs/031-foundry-pack-system-foundation.md`
- Source archive: `_import/pre-canonical-specs.md`
- Legacy name: `31 — Foundry Pack System Foundation`
- Legacy id: `31`
- Canonical pre-canonical id: `031`

## Historical Specification Summary

The original pre-canonical specification body is preserved in the imported execution spec. This reconstruction note records adjacent marked context and result evidence without inferring modern module ownership.

## Historical Preamble Context

### Preamble Block 1

- Name: `31 — Foundry Pack System Foundation`

Purpose

Introduce the foundational local pack/extension system for Foundry.

This spec establishes:
	•	local pack installation
	•	local pack removal/deactivation
	•	deterministic pack registration
	•	graph integration
	•	pack metadata contracts

This is the foundation for the later marketplace work, but it must work fully offline and without any remote dependency.

## Historical Implementation Evidence

### Result Block 1

- Name: `31 — Foundry Pack System Foundation`

RESULT

Spec 31 is implemented. Foundry now has a real offline pack foundation: local install/remove/list/info CLI support via [PackCommand.php](/Users/lo_fye/code/foundry-framework/src/CLI/Commands/PackCommand.php), manifest validation in [PackManifest.php](/Users/lo_fye/code/foundry-framework/src/Packs/PackManifest.php), deterministic storage and activation state in [InstalledPackRegistry.php](/Users/lo_fye/code/foundry-framework/src/Packs/InstalledPackRegistry.php), and pack boot/loading through [LocalPackLoader.php](/Users/lo_fye/code/foundry-framework/src/Packs/LocalPackLoader.php), [PackContext.php](/Users/lo_fye/code/foundry-framework/src/Packs/PackContext.php), and [PackServiceProvider.php](/Users/lo_fye/code/foundry-framework/src/Packs/PackServiceProvider.php). Packs install into `.foundry/packs/{vendor}/{pack}/{version}/`, activate through `.foundry/packs/installed.json`, load in deterministic name/version order, and fail explicitly on command/schema collisions or duplicate graph node ids.

I wired the pack layer into graph/compiler flows in [ExtensionRegistry.php](/Users/lo_fye/code/foundry-framework/src/Compiler/Extensions/ExtensionRegistry.php), [SourceScanner.php](/Users/lo_fye/code/foundry-framework/src/Compiler/SourceScanner.php), [ApplicationGraph.php](/Users/lo_fye/code/foundry-framework/src/Compiler/ApplicationGraph.php), and [ConfigSchemaCatalog.php](/Users/lo_fye/code/foundry-framework/src/Config/ConfigSchemaCatalog.php), then updated the public surface/docs in [README.md](/Users/lo_fye/code/foundry-framework/README.md), [APP-README.md](/Users/lo_fye/code/foundry-framework/APP-README.md), [APP-AGENTS.md](/Users/lo_fye/code/foundry-framework/APP-AGENTS.md), [extension-author-guide.md](/Users/lo_fye/code/foundry-framework/docs/extension-author-guide.md), [extensions-and-migrations.md](/Users/lo_fye/code/foundry-framework/docs/extensions-and-migrations.md), and [CommandCatalog.php](/Users/lo_fye/code/foundry-framework/src/Documentation/CommandCatalog.php). Coverage was added in [CLIPackCommandsTest.php](/Users/lo_fye/code/foundry-framework/tests/Integration/CLIPackCommandsTest.php), [PackManifestTest.php](/Users/lo_fye/code/foundry-framework/tests/Unit/PackManifestTest.php), and [ExtensionRegistryTest.php](/Users/lo_fye/code/foundry-framework/tests/Unit/ExtensionRegistryTest.php).

Verification passed:
- `php bin/foundry compile graph --json`
- `php bin/foundry verify graph --json`
- `php bin/foundry verify cli-surface --json` -> `127` valid, `0` invalid, `0` ambiguous, `0` orphan handlers, coverage `1`
- `php vendor/bin/phpunit` -> `373` tests passed, `3009` assertions

40 files changed
+2313
-55
Undo
APP-AGENTS.md
APP-README.md
README.md
docs/extension-author-guide.md
docs/extensions-and-migrations.md
src/CLI/Application.php
src/CLI/Commands/PackCommand.php
src/Compiler/ApplicationGraph.php
src/Compiler/Extensions/ExtensionMetadataValidator.php
src/Compiler/Extensions/ExtensionRegistry.php
src/Compiler/Passes/EnrichPass.php
src/Compiler/SourceScanner.php
src/Config/ConfigSchemaCatalog.php
src/Documentation/CommandCatalog.php
src/Packs/InstalledPackExtension.php
src/Packs/InstalledPackRegistry.php
src/Packs/LocalPackLoader.php
src/Packs/PackContext.php
src/Packs/PackManager.php
src/Packs/PackManifest.php
src/Packs/PackServiceProvider.php
src/Support/ApiSurfaceRegistry.php
tests/Fixtures/Packs/acme-zeta/foundry.json
tests/Fixtures/Packs/acme-zeta/src/AcmeZetaPackServiceProvider.php
tests/Fixtures/Packs/foundry-blog-duplicate/foundry.json
tests/Fixtures/Packs/foundry-blog-duplicate/src/FoundryBlogDuplicateExtension.php
tests/Fixtures/Packs/foundry-blog-duplicate/src/FoundryBlogDuplicateInterceptorPass.php
tests/Fixtures/Packs/foundry-blog-duplicate/src/FoundryBlogDuplicatePackServiceProvider.php
tests/Fixtures/Packs/foundry-blog-tools/foundry.json
tests/Fixtures/Packs/foundry-blog-tools/src/FoundryBlogToolsPackServiceProvider.php
tests/Fixtures/Packs/foundry-blog/foundry.json
tests/Fixtures/Packs/foundry-blog/src/FoundryBlogExtension.php
tests/Fixtures/Packs/foundry-blog/src/FoundryBlogInterceptorPass.php
tests/Fixtures/Packs/foundry-blog/src/FoundryBlogPackServiceProvider.php
tests/Integration/CLIApplicationTest.php
tests/Integration/CLIPackCommandsTest.php
tests/Unit/ApiSurfaceRegistryTest.php
tests/Unit/CLICommandMatchesTest.php
tests/Unit/ExtensionRegistryTest.php
tests/Unit/PackManifestTest.php


=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-
=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-
=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-
=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-
=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-

BUILD ORDER:
32, 33, 34, 35, 30D, 30E, 30F, 30G, 36, 37, 38, 39, 40, 41, 42, 43, 44, 44B, 45A, 45B, 45C, 46, 47

## Historical Verification Evidence

Historical verification details, when present, are preserved verbatim inside the paired result blocks above.

## Historical Stabilization Notes

Historical stabilization details, when present, are preserved verbatim inside the paired result blocks above.

## Current Repository Alignment

The imported artifact is intentionally retained under `Modules/PreCanonical` as archive-lineage context. Modern module ownership remains deferred until a separate explicit alignment spec maps the pre-canonical intent into current modules.

## Uncertainty And Reconstruction Notes

No modern module inference was performed. The generated note preserves only the marked archive relationships available through `S`, `R`, and `P` blocks.
