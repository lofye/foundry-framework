Spec 28-WS - Framework Internals Reference from framework/src/

Purpose

Add generated docs pages to the website that explain the framework’s src/ directory structure and major classes.

Do NOT create hand-written pages for every file.
Generate a structured reference from the framework codebase.

⸻

Goals
	1.	Give developers and LLMs a trustworthy map of the framework internals
	2.	Document major subsystems under framework/src/
	3.	Provide concise per-class summaries where useful
	4.	Keep the output deterministic and regenerable
	5.	Avoid overwhelming first-time users

⸻

Non-Goals
	•	Do not manually author one page per file
	•	Do not turn the docs into raw API dumps
	•	Do not duplicate canonical philosophy/execution/graph docs
	•	Do not make internals reference the first thing new users see

⸻

Required Output

Generate a new docs section under something like:

public/docs/framework-internals/

and corresponding machine-readable outputs under:

public/docs/generated/

Use the existing website docs pipeline. Do not replace it.

⸻

Required Pages

Create generated pages for at least:
	1.	Framework Internals Index
	•	overview of major src/ subsystems
	•	links to subsystem pages
	2.	One page per major top-level subsystem under framework/src/
Examples may include:
	•	Compiler
	•	CLI
	•	Feature
	•	Config
	•	Auth
	•	Cache
	•	Queue
	•	Events
	•	Scheduler
	•	Storage
	•	Webhook
	•	AI
	•	Observability
	•	Verification
	•	Generation
	•	Support
	3.	Optional class detail pages or expandable sections for important classes

⸻

Per-Subsystem Page Requirements

Each subsystem page should include:
	•	subsystem name
	•	directory path
	•	purpose summary
	•	major classes
	•	short description of each major class
	•	notable relationships to other subsystems
	•	notable CLI/docs links where relevant

Do not dump raw source code.

Keep each class description concise and factual.

⸻

Class Inclusion Rules

Include classes that are:
	•	public-facing within the framework internals
	•	architecturally important
	•	entry points
	•	registries
	•	drivers
	•	compilers
	•	verifiers
	•	runtime coordinators

Lower-priority helper/value classes may be grouped rather than given full individual treatment.

⸻

Source of Truth

Generate from:

framework/src/

Use:
	•	namespace
	•	class names
	•	file paths
	•	docblocks if useful
	•	lightweight static analysis of constructor/method names if helpful

Do not invent behavior not supported by code.

⸻

Generated Metadata

Also emit machine-readable JSON, for example:

content/docs/generated/framework-internals.json
content/docs/generated/framework-internals/<subsystem>.json

and publish them under:

public/docs/generated/

Include:
	•	subsystem
	•	class
	•	path
	•	summary
	•	relationships
	•	tags/categories

This should be useful for LLM context and search.

⸻

Docs Positioning

This new internals section must be discoverable, but not the first experience.

Recommended placement:
	•	linked from architecture docs
	•	linked from reference docs
	•	linked from architecture explorer
	•	linked from contributor docs

Do not make it replace “Getting Started.”

⸻

Cross-Linking

Add links where useful to:
	•	philosophy
	•	execution model
	•	graph spec
	•	architecture overview
	•	CLI reference
	•	architecture reference
	•	architecture explorer

Internals docs should support the higher-level docs, not compete with them.

⸻

Rendering Style

Optimize for:
	•	clarity
	•	scanability
	•	stable headings
	•	deterministic structure
	•	LLM readability

Avoid:
	•	giant undifferentiated lists
	•	excessive prose
	•	duplicated explanations across pages

⸻

Acceptance Criteria
	•	website generates a framework internals reference from framework/src/
	•	there is an index page plus subsystem pages
	•	output is deterministic
	•	machine-readable JSON is emitted
	•	pages are linked into the docs site
	•	docs remain navigable and not overwhelming for new users
	•	no hand-maintained per-file docs burden is introduced

⸻

Implementation Bias

Prefer:
	•	subsystem-level clarity first
	•	important-class summaries second
	•	exhaustive per-file detail only where clearly valuable

The docs should help someone answer:

“What is in the framework, where does it live, and what role does it play?”

not:

“Show me an unreadable dump of every class.”

Link to these docs from:
	•	architecture-reference
	•	architecture-explorer
	•	contributing
	•	and a short note in the root README/docs index.
	


------------------------------------------------------------------------------------------

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

------------------------------------------------------------------------------------------



------------------------------------------------------------------------------------------
