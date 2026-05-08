Spec 19P-WS — Slug Resolution + Enforcement Pass

⸻

Purpose

Add a small, deterministic enforcement pass to the existing website docs pipeline.

Do NOT replace the current docs build.
Do NOT replace the current versioned snapshot system.
Do NOT redesign immutable tag-based versioning.

This spec only adds:

• explicit slug ownership
• deterministic source resolution
• conflict detection
• build failure on ambiguity

⸻

Context

The current website docs build already works.

It already:

• reads framework docs
• reads imported docs
• reads authored docs
• reads generated docs
• renders current docs
• publishes immutable version snapshots by framework tag

Keep that behavior.

This spec adds a validation/linking layer before rendering.

⸻

Non-Negotiable Rule

Do NOT replace:

• scripts/build-docs.php
• DocsPipeline.php
• current version snapshot logic

Only add a pre-render enforcement phase.

⸻

Conceptual Model

Current system already acts like:

source docs → render → publish → version snapshot

This spec adds:

slug map → validate ownership → resolve source → existing render/publish/version flow

⸻

Deliverable

Add a slug resolution + enforcement pass that runs before page rendering.

This may be implemented as:

• a new class called by DocsPipeline
or
• a small pre-pass in DocsPipeline itself
or
• a helper loaded by build-docs.php before rendering

Use the smallest safe diff.

⸻

Required Input

Add authoritative file:

content/docs/slug-map.json

Example shape:

{
“execution-model”: {
“owner”: “framework”,
“source”: “framework/docs/architecture/execution-model.md”
},
“execution-pipeline”: {
“owner”: “framework”,
“source”: “framework/docs/execution-pipeline.md”
},
“architecture-reference”: {
“owner”: “website”,
“source”: “content/docs/authored/architecture-reference.md”
}
}

⸻

Ownership Rules

Allowed owners:

• framework
• website
• generated

Rules:
	1.	every public docs slug must have exactly one owner
	2.	every owner must resolve to exactly one source
	3.	imported docs inherit ownership from framework
	4.	generated docs may augment pages but do not own canonical framework concepts
	5.	website docs may explain/wrap framework concepts, but may not silently replace them

⸻

Pass Behavior

For each slug in slug-map.json:
	1.	resolve owner
	2.	resolve source path
	3.	verify source file exists
	4.	verify no competing source exists for same slug
	5.	verify no legacy fallback is being used when canonical source exists
	6.	pass resolved source into existing rendering flow

⸻

Strict Failure Conditions

The build must fail if:

• slug has no mapping
• slug has multiple mapped sources
• mapped source file does not exist
• framework and website both claim same slug
• legacy imported source is used when canonical mapped source exists
• current pipeline would silently render from wrong source

⸻

Allowed Legacy Behavior

Legacy mappings are allowed only if explicitly declared in slug-map.json.

Example:

{
“execution-model”: {
“owner”: “framework”,
“source”: “content/docs/imported/legacy/execution-pipeline.md”,
“legacy”: true,
“migration_target”: “framework/docs/architecture/execution-model.md”
}
}

Rules:

• no implicit legacy fallback
• all legacy usage must be visible
• legacy mappings should emit warnings
• canonical mappings should replace legacy mappings as soon as safe

⸻

Augmentation Rules

Generated markdown may augment a page only when explicitly allowed.

Example:

{
“architecture-reference”: {
“owner”: “website”,
“source”: “content/docs/authored/architecture-reference.md”,
“augment”: [
“content/docs/generated/graph-reference.md”,
“content/docs/generated/pipeline-reference.md”
]
}
}

Rules:

• augmentation does not change ownership
• augmentation does not replace base source
• augmentation must be deterministic
• augmentation order must be explicit

⸻

Integration With Existing Pipeline

Required sequence:
	1.	load slug-map.json
	2.	validate ownership and source resolution
	3.	build resolved slug/source table
	4.	pass resolved table into existing DocsPipeline rendering
	5.	run existing current-doc build
	6.	run existing immutable tag-based version snapshot logic

Do not alter steps 5 or 6 beyond what is necessary to use resolved sources.

⸻

Diagnostics

Emit deterministic diagnostics report, for example:

docs-build/slug-resolution-report.json

Include:

• slug
• owner
• source
• augmentation sources
• legacy flag
• validation status
• warnings
• errors

Optional markdown summary:

docs-build/slug-resolution-report.md

⸻

Minimum Required Slugs For This Spec

At minimum, explicitly map and enforce these:

• execution-model
• execution-pipeline
• reference
• architecture-reference
• getting-started
• docs-index
• framework-capabilities
• core-concepts
• ai-development
• architecture-explorer
• contributing

Additional slugs may be added incrementally.

⸻

Primary Corrections Required
	1.	execution-model must no longer resolve from execution-pipeline source
	2.	execution-pipeline must have its own slug
	3.	reference and architecture-reference must not conflict
	4.	framework concepts must not be silently overridden by website docs

⸻

Acceptance Criteria

• existing build still works
• existing version snapshot behavior still works
• slug ownership becomes explicit
• execution-model and execution-pipeline are separate
• reference and architecture-reference are separate
• build fails on ambiguity
• no silent source collisions remain
• diagnostics report is emitted

⸻

Implementation Bias

Prefer the smallest possible diff.

This is an enforcement pass inside the current pipeline, not a replacement pipeline.

⸻

End Spec


—

This is the version I’d use.

It preserves what already works:
	•	current build
	•	tag-based immutable versioning
	•	DocsPipeline
	•	generated metadata flow

And adds what you’re missing:
	•	source truth enforcement
	•	correct semantic mapping
	•	failure instead of silent wrongness

What you’ve just done (important, non-fluffy)

You now have:
	•	19O WS → fixes the meaning
	•	19P WS → enforces the mechanism

Together, they turn your docs system into:

a compiler with a type system (ownership) and a linker (slug resolution)

Which is exactly consistent with Foundry’s philosophy.


------------------------------------------------------------------------------------------

RESULT

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

### Section 1 — Still Required
(list + reasons)

### Section 2 — Likely Legacy / Redundant
(list + reasons)

### Section 3 — Recommended Actions

For each item:
• keep as-is
• move to website repo
• remove
• replace with clearer structure

---

Important constraints:

• The website repo is the source of truth for docs rendering and publishing
• The framework repo should NOT contain conflicting or duplicate docs pipelines
• Avoid breaking changes unless clearly safe

---

Do NOT modify files.
Do NOT delete anything.
This is analysis only.

------------------------------------------------------------------------------------------

ANSWER

This is the answer to Question 2, asked after successfully implementing Spec 19P-WS with no issues:

### Section 1 — Still Required

- `public/`: Original purpose was the framework repo’s web document root for running the root app and for scaffolding new Foundry apps. The framework CLI still points app serving at `public/index.php` in [ServeCommand.php](/Users/lo_fye/code/foundry-website/framework/src/CLI/Commands/ServeCommand.php#L24), scaffolds a `serve` script around it in [InitAppCommand.php](/Users/lo_fye/code/foundry-website/framework/src/CLI/Commands/InitAppCommand.php#L228), and writes that file into new apps in [InitAppCommand.php](/Users/lo_fye/code/foundry-website/framework/src/CLI/Commands/InitAppCommand.php#L351). Status: actively used; not legacy; not redundant with the website repo because this is runtime app infrastructure, not docs publishing; only mildly confusing because both repos have a `public/` directory.

- `public/index.php`: Original purpose was and still is the HTTP front controller for a Foundry app. It boots the runtime kernel and emits the HTTP response in [public/index.php](/Users/lo_fye/code/foundry-website/framework/public/index.php#L1), and the README still tells developers to serve the framework repo through it in [README.md](/Users/lo_fye/code/foundry-website/framework/README.md#L70). Status: actively used; not legacy; not redundant with the website repo; only mildly confusing because the website repo also has a `public/` area, but that one is for the website itself.

### Section 2 — Likely Legacy / Redundant

- `docs/versions/` with `README.md`: Original purpose was to store hand-authored archived docs snapshots in the framework repo and let the framework-local docs builder publish them to `public/docs/versions/<tag>` as described in [docs/versions/README.md](/Users/lo_fye/code/foundry-website/framework/docs/versions/README.md#L1). That behavior is still active inside the old framework docs builder in [DocsSiteBuilder.php](/Users/lo_fye/code/foundry-website/framework/src/Documentation/DocsSiteBuilder.php#L28) and explicitly tested in [DocsSiteBuilderTest.php](/Users/lo_fye/code/foundry-website/framework/tests/Unit/DocsSiteBuilderTest.php#L25). Status: actively used by framework-local docs code/tests, but legacy relative to the current architecture; redundant with the website repo’s versioned docs publishing; potentially dangerous/confusing because it creates a second snapshot source and another publishing path.

- `scripts/build-docs.php`: Original purpose was to be the framework repo’s standalone docs build entrypoint. It compiles the root app graph and invokes `DocsSiteBuilder` in [build-docs.php](/Users/lo_fye/code/foundry-website/framework/scripts/build-docs.php#L1), and the framework README still presents it as the docs build command in [README.md](/Users/lo_fye/code/foundry-website/framework/README.md#L249). Status: still runnable and still referenced, so not dead code, but effectively legacy for the overall product; redundant with the website repo’s docs rendering/publishing pipeline; potentially dangerous/confusing because it encourages contributors to build docs from the wrong repo and produce a competing `public/docs` tree.

### Section 3 — Recommended Actions

- `public/`: keep as-is. It is part of the framework’s runnable app/scaffolding model, not part of the docs publishing conflict.

- `public/index.php`: keep as-is. It is the canonical front controller for serving a Foundry app locally and in generated app skeletons.

- `docs/versions/`: replace with clearer structure. The safest direction is to stop treating it as the authoritative published snapshot source and make the website repo the only place that renders/publishes versioned docs. Do not remove it until the framework-local builder/tests/docs references are cleaned up.

- `scripts/build-docs.php`: replace with clearer structure. It should no longer be presented as the canonical docs pipeline. Safest path is to deprecate it first, update framework docs to point at the website repo for rendering/publishing, and only then remove or narrow it to an explicitly internal preview helper if still needed.

------------------------------------------------------------------------------------------

PASTE THE ANSWER TO CHATGPT
