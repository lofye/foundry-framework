**Changed**
- [slug-map.json](/Users/lo_fye/code/foundry-website/content/docs/slug-map.json#L1) is now the authoritative map for the required slug set, with explicit owners, base sources, and explicit `augment` lists where generated markdown is allowed.
- [DocsPipeline.php](/Users/lo_fye/code/foundry-website/scripts/lib/DocsPipeline.php#L1407) now runs a deterministic pre-render enforcement pass that:
  - validates required slug mappings
  - accepts `framework`, `website`, and `generated` owners
  - rejects missing or invalid sources
  - rejects conflicting active claims
  - rejects implicit augmentations
  - emits `legacy`, `migration_target`, `validation_status`, `warnings`, and `errors` in the report
- I kept the docs-home slug canonical as `index`, because the actual page is [public/docs/index.html](/Users/lo_fye/code/foundry-website/public/docs/index.html). The validator also accepts `docs-index` as an alias for the required-slug check, so the spec wording is covered without changing the live docs-home slug.

**Verified**
- `php scripts/build-docs.php` succeeded.
- Existing immutable snapshot behavior stayed intact: the build reported the existing `v0.5.2` version snapshot and machine-readable snapshot were skipped, not regenerated.
- [slug-resolution-report.json](/Users/lo_fye/code/foundry-website/docs-build/slug-resolution-report.json#L1) now contains 11 enforced entries with `error_count: 0` and `warning_count: 0`.
- `execution-model` and `execution-pipeline` remain separate slugs/pages, and `reference` remains separate from `architecture-reference`, with the active sources recorded in the report and the generated docs metadata.

2 files changed
+214
-12
Undo
content/docs/slug-map.json
scripts/lib/DocsPipeline.php

------------------------------------------------------------------------------------------

QUESTION 2

You are analyzing the Foundry framework repository.

Explain why the following items exist and whether they are still required:

1. public/
2. public/index.php
3. docs/versions/ (with README.md)
4. scripts/build-docs.php

---

For each item:

• Describe its original purpose
• Determine if it is:
  - actively used
  - legacy
  - redundant with website repo
  - potentially dangerous/confusing

---

Then provide:
