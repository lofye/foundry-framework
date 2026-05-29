# Implementation Plan: 018-documentation-system-site-generation-structure

## Historical Provenance

- Imported spec path: `Modules/PreCanonical/specs/018-documentation-system-site-generation-structure.md`
- Source archive: `_import/pre-canonical-specs.md`
- Legacy name: `18 — Documentation System (Site + Generation + Structure)`
- Legacy id: `18`
- Canonical pre-canonical id: `018`

## Historical Specification Summary

The original pre-canonical specification body is preserved in the imported execution spec. This reconstruction note records adjacent marked context and result evidence without inferring modern module ownership.

## Historical Preamble Context

### Preamble Block 1

- Name: `18 — Documentation System (Site + Generation + Structure)`

Foundry’s documentation is not just explanatory—it is a reflection of the architecture itself.

The docs system must:
	•	align with Foundry’s graph and structure
	•	be partially generated from source metadata
	•	remain human-readable and curated

All new code must maintain ≥ 90% automated test coverage.

## Historical Implementation Evidence

### Result Block 1

- Name: `18 — Documentation System (Site + Generation + Structure)`

RESULT (Spec 18)

Implemented Spec 18 as a framework docs build system.

The main pieces are in [DocsSiteBuilder.php](/Users/lo_fye/code/foundry-framework/src/Documentation/DocsSiteBuilder.php), [GraphDocsGenerator.php](/Users/lo_fye/code/foundry-framework/src/Documentation/GraphDocsGenerator.php), and the new entrypoint [build-docs.php](/Users/lo_fye/code/foundry-framework/scripts/build-docs.php). The builder now merges curated docs with generated graph/schema/CLI content, renders a static site with top nav + section nav, writes current docs to `public/docs`, and writes versioned snapshots to `public/docs/versions/<version>`. It also scans `docs/versions/*` for archived snapshot sources and rewrites example README links into site-local pages so the static output stays deployable.

I added curated landing pages at [intro.md](/Users/lo_fye/code/foundry-framework/docs/intro.md), [quick-tour.md](/Users/lo_fye/code/foundry-framework/docs/quick-tour.md), [how-it-works.md](/Users/lo_fye/code/foundry-framework/docs/how-it-works.md), and [reference.md](/Users/lo_fye/code/foundry-framework/docs/reference.md), plus snapshot guidance at [docs/versions/README.md](/Users/lo_fye/code/foundry-framework/docs/versions/README.md). Tests for generation, version handling, navigation, and content correctness are in [DocsSiteBuilderTest.php](/Users/lo_fye/code/foundry-framework/tests/Unit/DocsSiteBuilderTest.php) and the updated [GraphDocsGeneratorTest.php](/Users/lo_fye/code/foundry-framework/tests/Unit/GraphDocsGeneratorTest.php).

Verified with:
- `vendor/bin/phpunit tests/Unit/GraphDocsGeneratorTest.php tests/Unit/DocsSiteBuilderTest.php tests/Unit/CLICommandsTest.php tests/Unit/CLICommandMatchesTest.php tests/Integration/CLIIntegrationCommandsTest.php tests/Integration/CLIInitAppCommandTest.php`
- `php bin/foundry compile graph --json`

I did not run `php scripts/build-docs.php` in the repo worktree, so `public/docs` has not been materialized here yet.

12 files changed
+1613
-39
Undo
README.md
docs/how-it-works.md
docs/intro.md
docs/quick-tour.md
docs/reference.md
docs/versions/README.md
scripts/build-docs.php
src/Documentation/DocsSiteBuilder.php
src/Documentation/GraphDocsGenerator.php
src/Documentation/MarkdownPageRenderer.php
tests/Unit/DocsSiteBuilderTest.php
tests/Unit/GraphDocsGeneratorTest.php


=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-
=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-
=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-
=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-
=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-

# Operator Notes (FOR CODEX EXECUTION)

## What to give Codex
- Entire file

## Verify
- Clear layering: CLI → Resolver → Engine → Collectors → Analyzers → Plan → Renderer
- No analysis logic inside CLI command
- No rendering logic inside analyzers or collectors
- ExplanationPlan is the single source of truth for output

## Anti-patterns to avoid
- Command directly accessing graph/projections
- Collectors returning formatted text instead of structured data
- Analyzers generating human-readable strings
- Renderer querying graph or projections

## Green-light criteria
- Fully deterministic output
- Clean separation of concerns across all layers
- DTOs (Target, Subject, Plan) are stable and testable
- Architecture supports future contributors and extensions cleanly
- >90% test coverage

Please fix any issues and ensure that all Green-light criteria are met.

---

Please design the internal architecture for foundry explain, and then give me the spec for me to hand off to the Codex app for implementation. Also tell me whether I should use Codex 5.3 or GPT-5.4 in that app to implement it. Think long and hard about this.	

Why: foundry explain is not just “add a command.” It touches the compiler outputs, graph model, projections, CLI UX, JSON contracts, diagnostics, and future extension points. That is exactly the kind of cross-cutting architectural work where GPT-5.4 tends to do better. I’d use Codex 5.3 later for narrower follow-up tasks like polishing tests, tightening renderers, or fixing small regressions.

Use GPT-5.4 for this one.

Reason:
	•	cross-cutting architecture
	•	needs disciplined layering
	•	likely requires refactoring multiple metadata/projection systems
	•	easier to keep deterministic if the planner is stronger

Then use Codex 5.3 later for:
	•	follow-up bugfixes
	•	renderer polish
	•	test additions
	•	smaller subcommands

If you only choose one, choose GPT-5.4.


----

In total, you will be receiving 6 specs in order to fully implement `foundry explain`.
They are:
Spec 19A → Architecture (what it is)
Spec 19B → Implementation (how it works end-to-end)
Spec 19C → UX contract (what it feels like)
Spec 19D → Foundation slice (safe starting point)
Spec 19E → Intelligence layer (collectors + analyzers)
Spec 19F → Final polish (rendering + contributors + docs)

## Historical Verification Evidence

Historical verification details, when present, are preserved verbatim inside the paired result blocks above.

## Historical Stabilization Notes

Historical stabilization details, when present, are preserved verbatim inside the paired result blocks above.

## Current Repository Alignment

The imported artifact is intentionally retained under `Modules/PreCanonical` as archive-lineage context. Modern module ownership remains deferred until a separate explicit alignment spec maps the pre-canonical intent into current modules.

## Uncertainty And Reconstruction Notes

No modern module inference was performed. The generated note preserves only the marked archive relationships available through `S`, `R`, and `P` blocks.
