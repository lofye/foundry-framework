# Implementation Plan: 027-examples-taxonomy-cleanup

## Historical Provenance

- Imported spec path: `Modules/PreCanonical/specs/027-examples-taxonomy-cleanup.md`
- Source archive: `_import/pre-canonical-specs.md`
- Legacy name: `27 - Examples Taxonomy Cleanup`
- Legacy id: `27`
- Canonical pre-canonical id: `027`

## Historical Specification Summary

The original pre-canonical specification body is preserved in the imported execution spec. This reconstruction note records adjacent marked context and result evidence without inferring modern module ownership.

## Historical Preamble Context

No marked preamble block was associated with this spec.

## Historical Implementation Evidence

### Result Block 1

- Name: `27 - Examples Taxonomy Cleanup`

RESULT

Updated the example taxonomy to use only `canonical`, `reference`, and `framework` across the example system. The main changes are in [examples/catalog.php](/Users/lo_fye/code/foundry-framework/examples/catalog.php), [examples/README.md](/Users/lo_fye/code/foundry-framework/examples/README.md), [docs/example-applications.md](/Users/lo_fye/code/foundry-framework/docs/example-applications.md), and [README.md](/Users/lo_fye/code/foundry-framework/README.md). I renamed the catalog key from `official` to `canonical`, collapsed all example `kind` values to the same three taxonomy words, and removed the `thresholds` catalog entry entirely.

I also cleaned the related wording in [examples/extensions-migrations/README.md](/Users/lo_fye/code/foundry-framework/examples/extensions-migrations/README.md), [examples/reference-blog/README.md](/Users/lo_fye/code/foundry-framework/examples/reference-blog/README.md), and [src/Documentation/LearningPathsPage.php](/Users/lo_fye/code/foundry-framework/src/Documentation/LearningPathsPage.php), then updated [tests/Integration/ExamplesStructureTest.php](/Users/lo_fye/code/foundry-framework/tests/Integration/ExamplesStructureTest.php) so it enforces the new taxonomy and the absence of Thresholds in the framework examples docs/index.

Verified with `vendor/bin/phpunit tests/Integration/ExamplesStructureTest.php`. I did not run the full test suite.

8 files changed
+39
-54
Undo
README.md
docs/example-applications.md
examples/README.md
examples/catalog.php
examples/extensions-migrations/README.md
examples/reference-blog/README.md
src/Documentation/LearningPathsPage.php
tests/Integration/ExamplesStructureTest.php

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
