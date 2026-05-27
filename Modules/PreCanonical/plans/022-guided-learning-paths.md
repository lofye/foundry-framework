# Implementation Plan: 022-guided-learning-paths

## Historical Provenance

- Imported spec path: `Modules/PreCanonical/specs/022-guided-learning-paths.md`
- Source archive: `_import/pre-canonical-specs.md`
- Legacy name: `22 — Guided Learning Paths`
- Legacy id: `22`
- Canonical pre-canonical id: `022`

## Historical Specification Summary

The original pre-canonical specification body is preserved in the imported execution spec. This reconstruction note records adjacent marked context and result evidence without inferring modern module ownership.

## Historical Preamble Context

No marked preamble block was associated with this spec.

## Historical Implementation Evidence

### Result Block 1

- Name: `22 — Guided Learning Paths`

RESULT

The guided learning paths feature now ships as a static docs page at `guided-learning-paths.html`, implemented in [LearningPathsPage.php](/Users/lo_fye/code/foundry-framework/src/Documentation/LearningPathsPage.php#L11) and wired into the docs build and navigation in [DocsSiteBuilder.php](/Users/lo_fye/code/foundry-framework/src/Documentation/DocsSiteBuilder.php#L42) and [DocsSiteBuilder.php](/Users/lo_fye/code/foundry-framework/src/Documentation/DocsSiteBuilder.php#L1123). It includes the three required paths, ordered steps, progress UI, previous/next controls, step jumping, and step-level links back into existing docs, command-playground CLI concepts, and explain-oriented docs where they fit.

I also surfaced it in the main docs entry points through [intro.md](/Users/lo_fye/code/foundry-framework/docs/intro.md#L5) and [quick-tour.md](/Users/lo_fye/code/foundry-framework/docs/quick-tour.md#L3), and extended coverage in [DocsSiteBuilderTest.php](/Users/lo_fye/code/foundry-framework/tests/Unit/DocsSiteBuilderTest.php#L87). This stays fully static, uses existing content only, and doesn’t touch runtime or scaffold behavior.

Verified with `vendor/bin/phpunit tests/Unit/ApiSurfaceRegistryTest.php tests/Unit/DocsSiteBuilderTest.php tests/Unit/GraphDocsGeneratorTest.php tests/Integration/CLIApplicationTest.php tests/Integration/CLIInitAppCommandTest.php tests/Integration/LocalFoundryExecutableTest.php`.

5 files changed
+704
-0
Undo
docs/intro.md
docs/quick-tour.md
src/Documentation/DocsSiteBuilder.php
src/Documentation/LearningPathsPage.php
tests/Unit/DocsSiteBuilderTest.php


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
