# Spec 19O-B WS - Post-Move Docs Integration After Framework Ownership Changes (linker)

Context

Two website-authored docs have already been moved into the framework repo:
	•	core-concepts.md
	•	framework-capabilities.md

The docs pipeline already:
	•	reads framework/docs/
	•	syncs into content/docs/imported/
	•	reads content/docs/authored/
	•	reads content/docs/generated/
	•	renders public/docs/
	•	preserves immutable version snapshots by framework tag.  ￼  ￼

Do not redesign the pipeline. Integrate the move safely.

⸻

Goals
	1.	Make the moved docs render from framework/imported sources instead of website-authored sources
	2.	Keep existing public slugs stable unless a slug change is explicitly required
	3.	Add explicit slug ownership
	4.	Prevent duplicate source conflicts
	5.	Keep current build + version snapshot behavior working
	6.  Preserve current slugs wherever possible, and only change slugs when there is a semantic error to fix
⸻

Do Not
	•	do not replace scripts/build-docs.php
	•	do not replace DocsPipeline.php
	•	do not manually edit public/docs/*
	•	do not remove legacy imports unless they are no longer mapped
	•	do not move website landing pages out of the website repo

⸻

Required Tasks
	1.	Update the docs pipeline so the moved docs are sourced from the framework/imported side, not content/docs/authored/
	2.	Remove the old website-authored copies from active rendering inputs if they still exist locally
	3.	Add or update:

content/docs/slug-map.json

	4.	Put explicit ownership entries in the slug map for at least:

	•	core-concepts
	•	framework-capabilities
	•	execution-model
	•	execution-pipeline
	•	reference
	•	architecture-reference
	•	getting-started
	•	docs-index
	•	ai-development
	•	architecture-explorer
	•	contributing

	5.	Use these ownership rules:

	•	core-concepts → framework-owned
	•	framework-capabilities → framework-owned
	•	execution-model → framework-owned
	•	execution-pipeline → framework-owned
	•	reference → framework-owned
	•	architecture-reference → website-owned
	•	getting-started → website-owned
	•	docs-index → website-owned
	•	ai-development → website-owned
	•	architecture-explorer → website-owned
	•	contributing → website-owned for now

	6.	Fix the known wrong semantic mapping:

	•	execution-model must not resolve from execution-pipeline
	•	execution-model and execution-pipeline must be separate slugs/pages

The attached docs prove these files are not equivalent:
	•	execution-model.md is a high-level architectural doctrine document.  ￼
	•	execution-pipeline.md is a concrete runtime subsystem document.  ￼

	7.	Keep reference and architecture-reference as separate pages/slugs

The attached docs prove these are not duplicates:
	•	reference.md is a framework reference index page.  ￼
	•	architecture-reference.md is a website-owned augmented explainer for generated metadata and LLM context.  ￼

	8.	Add a slug resolution + enforcement pass to the existing pipeline:

	•	fail if one slug has multiple owners
	•	fail if mapped source file is missing
	•	fail if framework + website both claim same slug
	•	fail if canonical mapping exists but build silently uses a wrong legacy source

Keep this as a small pre-render validation layer. Do not rewrite the build.
	9.	Emit a deterministic report, e.g.:

docs-build/slug-resolution-report.json

including:
	•	slug
	•	owner
	•	source
	•	augmentations if any
	•	validation errors/warnings

	10.	Verify:

	•	current docs build still works
	•	version snapshots still work
	•	core-concepts renders correctly from framework/imported source
	•	framework-capabilities renders correctly from framework/imported source
	•	execution-model and execution-pipeline are both present and separate
	•	reference and architecture-reference are both present and separate

⸻

Acceptance Criteria
	•	no duplicate ownership for moved docs
	•	moved docs render from framework/imported source
	•	website-authored docs still render where intended
	•	slug map exists and is authoritative
	•	slug enforcement runs before render
	•	existing versioned docs process still works
	•	no silent source collisions remain

------------------------------------------------------------------------------------------

RESULT

The linker pass is in place, and all changes stayed in the website repo only. [slug-map.json](/Users/lo_fye/code/foundry-website/content/docs/slug-map.json#L1) now defines the 11 required slugs with explicit `framework-owned` and `website-owned` entries, [navigation.json](/Users/lo_fye/code/foundry-website/content/docs/navigation.json#L57) now routes `core-concepts` and `framework-capabilities` from imported framework mirrors, splits `execution-model` from `execution-pipeline`, and keeps `reference` separate from `architecture-reference`, and [DocsPipeline.php](/Users/lo_fye/code/foundry-website/scripts/lib/DocsPipeline.php#L1144) now runs a pre-render slug validation pass that loads the slug map, writes [slug-resolution-report.json](/Users/lo_fye/code/foundry-website/docs-build/slug-resolution-report.json#L1), and fails on missing mapped sources, multiple active claims, framework/website ownership conflicts, or source mismatches. I did not edit anything under `framework/`.

Verification passed. `php scripts/build-docs.php` completed successfully, and [slug-resolution-report.json](/Users/lo_fye/code/foundry-website/docs-build/slug-resolution-report.json#L1) shows `entry_count: 11`, `error_count: 0`, and `warning_count: 0`, with `core-concepts`, `execution-model`, `execution-pipeline`, `framework-capabilities`, and `reference` resolving from imported framework sources while `architecture-reference` stays website-owned. The live generated navigation confirms the active sources in [public/docs/generated/navigation.json](/Users/lo_fye/code/foundry-website/public/docs/generated/navigation.json#L69), and the rendered pages exist at [core-concepts](/Users/lo_fye/code/foundry-website/public/docs/core-concepts/index.html), [framework-capabilities](/Users/lo_fye/code/foundry-website/public/docs/framework-capabilities/index.html), [execution-model](/Users/lo_fye/code/foundry-website/public/docs/execution-model/index.html), [execution-pipeline](/Users/lo_fye/code/foundry-website/public/docs/execution-pipeline/index.html), [reference](/Users/lo_fye/code/foundry-website/public/docs/reference/index.html), and [architecture-reference](/Users/lo_fye/code/foundry-website/public/docs/architecture-reference/index.html). The old authored `core-concepts.md` and `framework-capabilities.md` copies are absent, so there is no remaining website-side active source for those moved docs, and the build preserved version snapshot behavior by reusing the existing `v0.5.2` snapshot instead of regenerating it.

3 files changed
+343
-20
Undo
content/docs/navigation.json
content/docs/slug-map.json
scripts/lib/DocsPipeline.php

------------------------------------------------------------------------------------------

Regarding updating the slug owner values, i want them to be framework instead of framework-owned, and website instead of website-owned. Also, if the public page is public/docs/index.html, then please change the docs-index slug from docs-index to index or whatever the pipeline actually uses as the canonical docs-home slug. After these changes are made, the slug-map should look just like the one I gave you earlier:

{
  "core-concepts": {
    "owner": "framework",
    "source": "content/docs/imported/architecture/core-concepts.md"
  },
  "framework-capabilities": {
    "owner": "framework",
    "source": "content/docs/imported/legacy/framework-capabilities.md"
  },
  "execution-model": {
    "owner": "framework",
    "source": "content/docs/imported/architecture/execution-model.md"
  },
  "execution-pipeline": {
    "owner": "framework",
    "source": "content/docs/imported/legacy/execution-pipeline.md"
  },
  "reference": {
    "owner": "framework",
    "source": "content/docs/imported/legacy/reference.md"
  },
  "architecture-reference": {
    "owner": "website",
    "source": "content/docs/authored/architecture-reference.md"
  },
  "getting-started": {
    "owner": "website",
    "source": "content/docs/authored/getting-started.md"
  },
  "index": {
    "owner": "website",
    "source": "content/docs/authored/docs-index.md"
  },
  "ai-development": {
    "owner": "website",
    "source": "content/docs/authored/ai-development.md"
  },
  "architecture-explorer": {
    "owner": "website",
    "source": "content/docs/authored/architecture-explorer.md"
  },
  "contributing": {
    "owner": "website",
    "source": "content/docs/authored/contributing.md"
  }
}

------------------------------------------------------------------------------------------

[slug-map.json](/Users/lo_fye/code/foundry-website/content/docs/slug-map.json#L1) now matches your JSON exactly: owner values are `framework` and `website`, and the docs-home slug is `index` instead of `docs-index`. I also updated the validator special case in [DocsPipeline.php](/Users/lo_fye/code/foundry-website/scripts/lib/DocsPipeline.php#L1587) so the canonical docs-home slug resolves as `index` to `content/docs/authored/docs-index.md`.

I rebuilt the docs and rechecked the report. [slug-resolution-report.json](/Users/lo_fye/code/foundry-website/docs-build/slug-resolution-report.json#L1) now shows the `index` slug as website-owned, with `entry_count: 11`, `error_count: 0`, and `warning_count: 0`, and `public/docs/index.html` remains the rendered docs home.

2 files changed
+13
-13
Undo
content/docs/slug-map.json
scripts/lib/DocsPipeline.php

------------------------------------------------------------------------------------------

git commit -m "implement Spec 19O-B WS"
