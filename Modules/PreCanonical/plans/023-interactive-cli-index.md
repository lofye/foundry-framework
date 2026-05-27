# Implementation Plan: 023-interactive-cli-index

## Historical Provenance

- Imported spec path: `Modules/PreCanonical/specs/023-interactive-cli-index.md`
- Source archive: `_import/pre-canonical-specs.md`
- Legacy name: `23 — Interactive CLI Index`
- Legacy id: `23`
- Canonical pre-canonical id: `023`

## Historical Specification Summary

The original pre-canonical specification body is preserved in the imported execution spec. This reconstruction note records adjacent marked context and result evidence without inferring modern module ownership.

## Historical Preamble Context

No marked preamble block was associated with this spec.

## Historical Implementation Evidence

### Result Block 1

- Name: `23 — Interactive CLI Index`

RESULT

Implemented Spec 23 inside the framework repo only.

The new static CLI index lives in [CliIndexPage.php](/Users/lo_fye/code/foundry-framework/src/Documentation/CliIndexPage.php#L12) and is wired into the docs build in [DocsSiteBuilder.php](/Users/lo_fye/code/foundry-framework/src/Documentation/DocsSiteBuilder.php#L42) and [DocsSiteBuilder.php](/Users/lo_fye/code/foundry-framework/src/Documentation/DocsSiteBuilder.php#L1145). It renders a deterministic `cli-index.html` from framework CLI metadata, with full-text search plus category, pipeline stage, extension, and command-type filters. Selecting a command shows usage, related docs, a command-playground link, explain targets, related commands, and related graph nodes.

To keep the new index consistent with actual CLI help output, I added the new metadata fields to [ApiSurfaceRegistry.php](/Users/lo_fye/code/foundry-framework/src/Support/ApiSurfaceRegistry.php#L646) and extracted shared command enrichment into [CommandCatalog.php](/Users/lo_fye/code/foundry-framework/src/Documentation/CommandCatalog.php#L14), which is now also used by [CommandPlaygroundPage.php](/Users/lo_fye/code/foundry-framework/src/Documentation/CommandPlaygroundPage.php#L12). I also linked the feature into generated and curated docs via [GraphDocsGenerator.php](/Users/lo_fye/code/foundry-framework/src/Documentation/GraphDocsGenerator.php#L108), [reference.md](/Users/lo_fye/code/foundry-framework/docs/reference.md), and [quick-tour.md](/Users/lo_fye/code/foundry-framework/docs/quick-tour.md).

Coverage was updated in [ApiSurfaceRegistryTest.php](/Users/lo_fye/code/foundry-framework/tests/Unit/ApiSurfaceRegistryTest.php), [CLIApplicationTest.php](/Users/lo_fye/code/foundry-framework/tests/Integration/CLIApplicationTest.php), [DocsSiteBuilderTest.php](/Users/lo_fye/code/foundry-framework/tests/Unit/DocsSiteBuilderTest.php), and [GraphDocsGeneratorTest.php](/Users/lo_fye/code/foundry-framework/tests/Unit/GraphDocsGeneratorTest.php).

Verified with `vendor/bin/phpunit tests/Unit/ApiSurfaceRegistryTest.php tests/Unit/DocsSiteBuilderTest.php tests/Unit/GraphDocsGeneratorTest.php tests/Integration/CLIApplicationTest.php tests/Integration/CLIInitAppCommandTest.php tests/Integration/LocalFoundryExecutableTest.php`.

12 files changed
+1466
-595
Undo
docs/quick-tour.md
docs/reference.md
src/Documentation/CliIndexPage.php
src/Documentation/CommandCatalog.php
src/Documentation/CommandPlaygroundPage.php
src/Documentation/DocsSiteBuilder.php
src/Documentation/GraphDocsGenerator.php
src/Support/ApiSurfaceRegistry.php
tests/Integration/CLIApplicationTest.php
tests/Unit/ApiSurfaceRegistryTest.php
tests/Unit/DocsSiteBuilderTest.php
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
