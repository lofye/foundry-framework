# Implementation Plan: 024-contributor-portal

## Historical Provenance

- Imported spec path: `Modules/PreCanonical/specs/024-contributor-portal.md`
- Source archive: `_import/pre-canonical-specs.md`
- Legacy name: `24 — Contributor Portal`
- Legacy id: `24`
- Canonical pre-canonical id: `024`

## Historical Specification Summary

The original pre-canonical specification body is preserved in the imported execution spec. This reconstruction note records adjacent marked context and result evidence without inferring modern module ownership.

## Historical Preamble Context

No marked preamble block was associated with this spec.

## Historical Implementation Evidence

### Result Block 1

- Name: `24 — Contributor Portal`

RESULT

Added a new contributor hub at [contributor-portal.md](/Users/lo_fye/code/foundry-framework/docs/contributor-portal.md). It now centralizes the framework contributor mental model and workflow: architecture overview (`collect -> analyze -> assemble -> render`), graph and explain boundaries, CLI structure, extension guidance, contribution rules, the safe edit loop, and a contributor-facing roadmap section. I also tightened the extension guidance in [extension-author-guide.md](/Users/lo_fye/code/foundry-framework/docs/extension-author-guide.md) so it explicitly distinguishes extension-contributed analyzers/contributors from framework-owned explain collectors.

I wired the portal into the generated docs navigation in [DocsSiteBuilder.php](/Users/lo_fye/code/foundry-framework/src/Documentation/DocsSiteBuilder.php#L1683), exposed the existing checklist as a real docs page, and added cross-links from [intro.md](/Users/lo_fye/code/foundry-framework/docs/intro.md#L10), [how-it-works.md](/Users/lo_fye/code/foundry-framework/docs/how-it-works.md#L5), [contributor-pr-checklist.md](/Users/lo_fye/code/foundry-framework/docs/contributor-pr-checklist.md#L5), and [README.md](/Users/lo_fye/code/foundry-framework/README.md#L252). Coverage was updated in [DocsSiteBuilderTest.php](/Users/lo_fye/code/foundry-framework/tests/Unit/DocsSiteBuilderTest.php#L35) to assert the new portal/checklist pages and link rewrites.

Verified with `vendor/bin/phpunit tests/Unit/DocsSiteBuilderTest.php tests/Unit/GraphDocsGeneratorTest.php tests/Integration/CLIInitAppCommandTest.php tests/Integration/LocalFoundryExecutableTest.php`. No runtime or scaffold behavior was changed.

8 files changed
+179
-3
Undo
README.md
docs/contributor-portal.md
docs/contributor-pr-checklist.md
docs/extension-author-guide.md
docs/how-it-works.md
docs/intro.md
src/Documentation/DocsSiteBuilder.php
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
