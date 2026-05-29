# Implementation Plan: 028-ws-framework-internals-reference-from-framework-src

## Historical Provenance

- Imported spec path: `Modules/PreCanonical/specs/028-ws-framework-internals-reference-from-framework-src.md`
- Source archive: `_import/pre-canonical-specs.md`
- Legacy name: `28-WS - Framework Internals Reference from framework/src/`
- Legacy id: `28`
- Canonical pre-canonical id: `028`

## Historical Specification Summary

The original pre-canonical specification body is preserved in the imported execution spec. This reconstruction note records adjacent marked context and result evidence without inferring modern module ownership.

## Historical Preamble Context

### Preamble Block 1

- Name: `28-WS - Framework Internals Reference from framework/src/`

Purpose

Add generated docs pages to the website that explain the framework’s src/ directory structure and major classes.

Do NOT create hand-written pages for every file.
Generate a structured reference from the framework codebase.

## Historical Implementation Evidence

### Result Block 1

- Name: `28-WS - Framework Internals Reference from framework/src/`

RESULT

**Implemented**
- Added a deterministic `framework/src/` generator in [FrameworkInternalsGenerator.php](/Users/lo_fye/code/foundry-website/scripts/lib/FrameworkInternalsGenerator.php) and hooked it into the existing pipeline in [DocsPipeline.php](/Users/lo_fye/code/foundry-website/scripts/lib/DocsPipeline.php). The pipeline now writes a generated internals index plus subsystem pages and JSON, then lets the normal navigation/search/LLM/render flow consume them.
- Added the landing-page nav entry in [navigation.json](/Users/lo_fye/code/foundry-website/content/docs/navigation.json) and linked the new section from [architecture-reference.md](/Users/lo_fye/code/foundry-website/content/docs/authored/architecture-reference.md), [architecture-explorer.md](/Users/lo_fye/code/foundry-website/content/docs/authored/architecture-explorer.md), [contributing.md](/Users/lo_fye/code/foundry-website/content/docs/authored/contributing.md), [docs-index.md](/Users/lo_fye/code/foundry-website/content/docs/authored/docs-index.md), and [README.md](/Users/lo_fye/code/foundry-website/README.md).

**Verified**
- `php scripts/build-docs.php` succeeded.
- The current docs alias now includes 30 internals pages in [public/docs/generated/navigation.json](/Users/lo_fye/code/foundry-website/public/docs/generated/navigation.json), including [framework-internals/index.html](/Users/lo_fye/code/foundry-website/public/docs/framework-internals/index.html) and subsystem pages like [framework-internals-compiler/index.html](/Users/lo_fye/code/foundry-website/public/docs/framework-internals-compiler/index.html).
- Machine-readable outputs are emitted at [framework-internals.json](/Users/lo_fye/code/foundry-website/content/docs/generated/framework-internals.json), per-subsystem JSON like [compiler.json](/Users/lo_fye/code/foundry-website/content/docs/generated/framework-internals/compiler.json), and matching public copies under [public/docs/generated/framework-internals.json](/Users/lo_fye/code/foundry-website/public/docs/generated/framework-internals.json).
- Slug enforcement still passes with `error_count: 0` and `warning_count: 0` in [slug-resolution-report.json](/Users/lo_fye/code/foundry-website/docs-build/slug-resolution-report.json).
- Existing immutable `v0.5.2` snapshot behavior was preserved. The new internals section is present in current `/docs` and current `/docs/generated`, but the already-existing `public/docs/versions/v0.5.2/` snapshot was not regenerated.

8 files changed
+1640
-8
Undo
README.md
content/docs/authored/architecture-explorer.md
content/docs/authored/architecture-reference.md
content/docs/authored/contributing.md
content/docs/authored/docs-index.md
content/docs/navigation.json
scripts/lib/DocsPipeline.php
scripts/lib/FrameworkInternalsGenerator.php

------------------------------------------------------------------------------------------

That sounds excellent.

Spec 28 appears to have landed in exactly the right way:
	•	deterministic generator
	•	integrated into the existing pipeline
	•	no rewrite of the docs system
	•	machine-readable outputs
	•	navigation + docs cross-linking
	•	slug enforcement still clean
	•	immutable version snapshot behavior preserved

That is the ideal outcome.

That’s a strong result.

The key things I like about it:
	•	you integrated the internals generator into the existing docs pipeline instead of inventing a parallel one
	•	it’s deterministic
	•	it emits both human pages and machine-readable JSON
	•	it cleanly plugs into navigation/search/LLM flows
	•	it preserved slug enforcement
	•	it did not mutate the existing immutable version snapshot

That is exactly the right shape.

The most important strategic win is this:

Foundry now has a generated “map of itself” on the docs site.

That makes the framework much easier for:
	•	new contributors
	•	advanced users
	•	LLMs
	•	future Pro / explain / generate tooling

A few practical implications:
	•	this makes your future explain and generate work more valuable, because the docs site now exposes subsystem structure in a deterministic way
	•	it strengthens the “architecture platform” story, not just the “framework docs” story
	•	it gives you a much better contributor ramp than relying only on prose docs

I would treat Spec 28 as a success.

The next small things I’d eventually want to confirm are:
	•	whether the generated subsystem summaries are actually good enough semantically, not just present
	•	whether the internals pages should link back to specific examples where useful
	•	whether ARCHITECTURE.md / architecture-overview.md should explicitly point readers to this new internals section

But none of those sound like blockers. This sounds like a clean landing.

## Historical Verification Evidence

Historical verification details, when present, are preserved verbatim inside the paired result blocks above.

## Historical Stabilization Notes

Historical stabilization details, when present, are preserved verbatim inside the paired result blocks above.

## Current Repository Alignment

The imported artifact is intentionally retained under `Modules/PreCanonical` as archive-lineage context. Modern module ownership remains deferred until a separate explicit alignment spec maps the pre-canonical intent into current modules.

## Uncertainty And Reconstruction Notes

No modern module inference was performed. The generated note preserves only the marked archive relationships available through `S`, `R`, and `P` blocks.
