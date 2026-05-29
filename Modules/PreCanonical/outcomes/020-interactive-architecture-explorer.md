# Implementation Plan: 020-interactive-architecture-explorer

## Historical Provenance

- Imported spec path: `Modules/PreCanonical/specs/020-interactive-architecture-explorer.md`
- Source archive: `_import/pre-canonical-specs.md`
- Legacy name: `20 — Interactive Architecture Explorer`
- Legacy id: `20`
- Canonical pre-canonical id: `020`

## Historical Specification Summary

The original pre-canonical specification body is preserved in the imported execution spec. This reconstruction note records adjacent marked context and result evidence without inferring modern module ownership.

## Historical Preamble Context

No marked preamble block was associated with this spec.

## Historical Implementation Evidence

### Result Block 1

- Name: `20 — Interactive Architecture Explorer`

RESULT

The interactive explorer now lives in [DocsSiteBuilder.php](/Users/lo_fye/code/foundry-framework/src/Documentation/DocsSiteBuilder.php#L37) and [DocsSiteBuilder.php](/Users/lo_fye/code/foundry-framework/src/Documentation/DocsSiteBuilder.php#L183). It generates `architecture-explorer.html` from the existing compiled graph JSON, with a deterministic SVG layout, full-text search, type/extension/pipeline filters, clickable nodes, dependency/dependent highlighting, inline metadata, and related-doc links. This stays read-only and does not introduce any new backend graph system.

Docs now link both directions: [GraphDocsGenerator.php](/Users/lo_fye/code/foundry-framework/src/Documentation/GraphDocsGenerator.php#L92) adds explorer entry points from graph overview and node-oriented reference pages, while [reference.md](/Users/lo_fye/code/foundry-framework/docs/reference.md#L5) and [quick-tour.md](/Users/lo_fye/code/foundry-framework/docs/quick-tour.md#L7) surface the explorer in the docs flow. Coverage was updated in [DocsSiteBuilderTest.php](/Users/lo_fye/code/foundry-framework/tests/Unit/DocsSiteBuilderTest.php#L87) and [GraphDocsGeneratorTest.php](/Users/lo_fye/code/foundry-framework/tests/Unit/GraphDocsGeneratorTest.php#L81). Because all changes were confined to the framework repo, I did not touch the website repo, `framework/public/`, or scaffold/runtime paths.

Verified with `vendor/bin/phpunit tests/Unit/DocsSiteBuilderTest.php tests/Unit/GraphDocsGeneratorTest.php tests/Integration/CLIInitAppCommandTest.php tests/Integration/LocalFoundryExecutableTest.php`.

6 files changed
+1026
-2
Undo
docs/quick-tour.md
docs/reference.md
src/Documentation/DocsSiteBuilder.php
src/Documentation/GraphDocsGenerator.php
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
