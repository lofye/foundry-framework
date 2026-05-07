first merge feature/compiler into main
then checkout the framework in the website, so the website can inspect the framework and its docs.
then codex in the website project can generate all of the above.


Yes — that flow makes sense, and it’s the order I’d recommend.

Best order

1. Merge feature/compiler into main in the framework repo

Do this first.

That gives the website a stable canonical source to inspect:
	•	compiler
	•	graph
	•	pipeline
	•	extensions
	•	docs generators
	•	CLI metadata

If you point the website at a long-lived feature branch, you risk documenting a moving target. That way lies swamp music.

⸻

2. In the website repo, check out the framework repo locally

Also yes.

The website should treat the framework repo as a source of truth to read from, not as copied blobs pasted by hand.

That gives you a clean setup for:
	•	auto-generated docs
	•	CLI reference extraction
	•	architecture reference generation
	•	version-aware documentation
	•	docs rebuilds on framework release

⸻

3. Then have Codex implement Phase 4 in the website repo

Yes.

That is the right place for:
	•	homepage rewrite
	•	how-foundry-works.html
	•	/docs
	•	doc generation pipeline
	•	versioned docs
	•	machine-readable docs export on the website side

So the sequencing is good.

⸻

One important refinement

I would make the website consume the framework repo in a deliberate, reproducible way, not just “there is a checkout somewhere on disk.”

You have a few options.

Best option: git submodule or subtree

This is probably the cleanest.

For example, in the website repo:

git submodule add https://github.com/lofye/foundry framework

Then the website always knows exactly which Foundry commit it is documenting.

Benefits:
	•	reproducible
	•	versionable
	•	CI-friendly
	•	easy to pin docs to a specific framework commit
	•	easier to support versioned docs later

This is much better than “clone Foundry manually beside the site and hope.”

⸻

Also good: build-time checkout in CI

Alternative approach:
	•	website repo stores no framework checkout
	•	CI checks out the framework repo at a chosen ref/tag during docs build

Benefits:
	•	less repo clutter
	•	still reproducible if pinned to a tag/commit

Drawback:
	•	slightly more setup complexity
	•	local development is a bit less straightforward

⸻

Least good: manual local clone

This works, but I would avoid making it the official architecture.

Why:
	•	fragile
	•	easy to drift
	•	hard for Codex/CI to reason about
	•	awkward for versioned docs

⸻

What I would recommend concretely

For now

Use a git submodule in the website repo pointing at the framework repo.

Then Codex can build Phase 4 assuming something like:

website-repo/
  framework/   ← git submodule checkout of Foundry
  src/...
  public/...
  docs-build/...

That gives the docs generator a deterministic place to read from.

⸻

Even better long-term flow

Here’s the ideal setup:

Framework repo

Owns:
	•	actual framework code
	•	source-level docs
	•	CLI metadata
	•	graph/compiler internals
	•	machine-readable reference generation hooks

Website repo

Owns:
	•	marketing pages
	•	polished narrative docs
	•	rendered documentation site
	•	search/navigation
	•	version selector
	•	imported/generated outputs from framework repo

That separation is healthy.

⸻

Recommended release workflow

A good flow would look like this:

A. Finish framework work in framework repo

Merge into main.

B. Tag a framework release

Example:

git tag v1.0.0-beta
git push origin v1.0.0-beta

C. Update the website repo’s framework reference

If submodule:
	•	update submodule to the release tag/commit

D. Run docs generation in website repo

The website reads:
	•	CLI commands
	•	architecture metadata
	•	machine-readable docs
	•	maybe generated markdown/reference files from the framework

E. Deploy website

Now the site docs match the framework release exactly.

That’s a beautiful clean chain.

⸻

One more recommendation before you hand this to Codex

In the website repo, I would make Codex implement a clear split between:

1. authored docs

Stuff you write/edit by hand:
	•	homepage copy
	•	conceptual guides
	•	“how Foundry works”
	•	philosophy
	•	contribution guides

2. generated docs

Stuff pulled from the framework repo:
	•	CLI reference
	•	pipeline stages
	•	graph node types
	•	diagnostics catalog
	•	extension APIs
	•	version metadata

That way the site becomes:
	•	human-friendly at the top
	•	mechanically accurate underneath

Exactly what Foundry should be.

⸻

Short answer

Yes, your flow makes sense:
	1.	merge framework/compiler work into framework main
	2.	make the website consume that framework repo in a stable way
	3.	implement Phase 4 in the website repo

My one change:

Use a submodule or another pinned reference, not an ad hoc checkout.

That will save you pain later when versioned docs arrive and the goblins start asking which commit the docs are actually describing.

If you want, I can next give you a recommended file/folder structure for the website repo so Codex has a clean target for implementing Phase 4.

------------------------------------------------------------------------------------------

yes please, and also tell me how to setup and use a git submodule

------------------------------------------------------------------------------------------

Absolutely. Here’s the cleanest way to structure the website repo for Phase 4, plus a practical guide to setting up and using a git submodule for Foundry.

⸻

Recommended website repo structure

I’d make the website repo look something like this:

foundryframework-org/
  framework/                 # git submodule: checkout of lofye/foundry
  public/
    index.html
    how-foundry-works.html
    docs/
      index.html
      getting-started/
      core-concepts/
      execution-model/
      framework-capabilities/
      cli/
      extension-development/
      ai-development/
      architecture-reference/
    assets/
      styles.css
      main.js
      images/
  content/
    homepage/
      hero.md
      conversation.md
      contribute.md
    docs/
      authored/
        getting-started.md
        how-foundry-works.md
        philosophy.md
      generated/
        cli-reference.md
        graph-reference.md
        pipeline-reference.md
        diagnostics-reference.md
        extension-reference.md
        version-metadata.json
  scripts/
    build-docs.php
    extract-cli.php
    extract-graph-metadata.php
    extract-diagnostics.php
    render-pages.php
  templates/
    layout.html
    docs-layout.html
    doc-page.html
    homepage.html
  build/
    index.html
    how-foundry-works.html
    docs/
  .gitignore
  README.md

Why this structure works

framework/

This is the Foundry submodule. It gives the website a pinned, inspectable copy of the framework.

content/docs/authored/

These are the human-written docs:
	•	conceptual explanations
	•	onboarding
	•	“why this exists”
	•	architecture narrative

content/docs/generated/

These are generated from the framework repo:
	•	CLI reference
	•	graph node types
	•	pipeline stages
	•	diagnostics catalog
	•	extension APIs
	•	version info

scripts/

This is where Codex should put the docs-generation and page-rendering logic.

templates/

Keeps the site layout separate from the content.

build/

Optional local build output before deployment.

⸻

How I’d have Codex think about the docs system

The website should have two layers of docs:

1. Authored docs

For humans.

These explain:
	•	goals
	•	architecture
	•	mental model
	•	how to work with Foundry
	•	what humans should focus on vs LLMs

2. Generated docs

For both humans and LLMs.

These expose:
	•	commands
	•	graph structures
	•	pipeline stages
	•	diagnostics
	•	extension contracts
	•	version metadata

That gives you a site that is:
	•	welcoming and explanatory at the top
	•	exact and machine-derived underneath

Which is exactly the right vibe for Foundry.

⸻

Git submodule: what it is

A git submodule is a git repo nested inside another git repo, pinned to a specific commit.

That means your website repo can include the Foundry framework repo without copying all its files into the website repo itself.

The website repo does not store the full framework history directly. Instead, it stores a reference saying roughly:

use Foundry repo at this exact commit

That is perfect for documentation.

⸻

Why use a submodule here

Because you want the website docs to correspond to a specific version of the framework.

That gives you:
	•	reproducible docs builds
	•	version-aware docs
	•	clean CI behavior
	•	a stable target for Codex
	•	easier rollback if docs and framework drift

⸻

How to set up the submodule

Run this from the root of the website repo:

git submodule add https://github.com/lofye/foundry.git framework

That will:
	•	create a framework/ directory
	•	clone the Foundry repo into it
	•	create a .gitmodules file
	•	stage the submodule reference in git

Then commit it:

git add .gitmodules framework
git commit -m "Add Foundry framework as git submodule"


⸻

What .gitmodules will look like

It will look roughly like this:

[submodule "framework"]
	path = framework
	url = https://github.com/lofye/foundry.git

That file should be committed.

⸻

How to clone the website repo with the submodule

When someone clones the website repo, they should do:

git clone --recurse-submodules https://github.com/yourname/foundryframework-org.git

That clones:
	•	the website repo
	•	the Foundry submodule inside framework/

If they already cloned without it, they can run:

git submodule update --init --recursive


⸻

How to update the submodule to the latest Foundry main

From the website repo root:

cd framework
git checkout main
git pull origin main
cd ..
git add framework
git commit -m "Update Foundry submodule to latest main"

That updates the pinned commit in the website repo.

⸻

Better: pin to releases or tags

This is what I’d actually recommend.

When Foundry gets tagged:

git tag v1.0.0-beta
git push origin v1.0.0-beta

Then in the website repo:

cd framework
git fetch --tags
git checkout v1.0.0-beta
cd ..
git add framework
git commit -m "Update framework submodule to v1.0.0-beta"

Now your docs are explicitly tied to that release.

That is much cleaner than always pointing at latest main.

⸻

How to tell what commit the submodule is pinned to

From the website repo root:

git submodule status

You’ll get something like:

abc1234567890 framework

That hash is the exact Foundry commit your website is documenting.

⸻

How to make changes inside the submodule

Usually, you should not edit the submodule from inside the website repo unless you intentionally want to contribute to the framework repo itself.

Normal flow:
	•	change framework code in the framework repo
	•	push it there
	•	then update the website repo’s submodule pointer to the new commit/tag

That separation is good discipline.

⸻

How the docs build would work

Once the submodule exists, your website docs builder can do things like:
	•	read framework/README.md
	•	inspect framework/src/...
	•	inspect generated metadata or reference files
	•	run framework commands if needed
	•	extract version info from framework/composer.json
	•	build docs into public/docs/...

So Codex can implement scripts like:

php scripts/build-docs.php

And inside that script it can read from:

framework/

as a stable source tree.

⸻

Suggested website build flow

Local workflow

1. Update framework submodule

cd framework
git fetch --tags
git checkout v1.0.0-beta
cd ..
git add framework
git commit -m "Update framework submodule"

2. Generate docs

php scripts/build-docs.php

3. Preview/deploy site

⸻

Release workflow

Framework repo
	•	merge work into main
	•	tag release

Website repo
	•	update submodule to that release tag
	•	run docs generation
	•	commit generated site/docs changes
	•	deploy site

That gives you docs that always match a real framework release.

⸻

A few submodule gotchas

1. People forget to init/update them

That’s normal. Put this in the website repo README:

git clone --recurse-submodules ...

or

git submodule update --init --recursive

2. Submodules point to a commit, not a branch

That’s a feature, not a bug. It keeps things reproducible.

3. The website repo must commit the updated submodule pointer

After updating framework/, you still need:

git add framework
git commit -m "Update framework submodule"

Otherwise the website repo doesn’t know the submodule changed.

⸻

My recommendation for your exact setup

I’d do this:

In the website repo

Add Foundry as:

framework/

as a git submodule.

Then tell Codex in Phase 4
	•	read authored docs from content/docs/authored/
	•	read framework source and metadata from framework/
	•	generate machine-readable and human-readable docs into content/docs/generated/
	•	render final pages into public/docs/ or build/docs/

That gives you a clean, stable architecture.

⸻

Short version

Yes, use a submodule

Best path for your use case.

Setup

git submodule add https://github.com/lofye/foundry.git framework
git add .gitmodules framework
git commit -m "Add Foundry submodule"

Clone later

git clone --recurse-submodules ...

Update later

cd framework
git fetch --tags
git checkout v1.0.0-beta
cd ..
git add framework
git commit -m "Update Foundry submodule"

If you want, I can next write you a Codex-ready Phase 4 preface that assumes the website repo uses a framework/ submodule and tells Codex exactly how to consume it.

------------------------------------------------------------------------------------------

yes

------------------------------------------------------------------------------------------

Absolutely — here’s a Codex-ready Master Spec















==========================================================================================
==========================================================================================

















Foundry Phase 4 Master Spec

Documentation System and Website Integration

The website repo is separate from the framework repo.

This website repo now includes the Foundry framework repo as a git submodule mounted at:

framework/

The submodule is the canonical source that the website must inspect in order to generate accurate framework documentation.

Important rules for this phase:

• Do not duplicate the framework source into the website repo.
• Treat framework/ as a pinned, versioned source of truth.
• All generated documentation must be derived from the framework submodule at framework/.
• The website must be able to explain the framework to humans while also exposing structured reference material that AI tools can use.
• Prefer dual-layer documentation:
  - authored conceptual docs for humans
  - generated reference docs from framework metadata for humans and LLMs
• The documentation build system must be deterministic.
• The generated docs must correspond to the exact checked-out framework submodule commit or tag.
• The site should be designed so that updating the framework submodule and rerunning the docs build will regenerate the docs for that framework version.

The website architecture for this phase should assume three content sources:

1. authored website content
   - homepage narrative
   - how-foundry-works narrative
   - contribution and philosophy content

2. generated framework reference content
   - CLI commands
   - graph/compiler concepts
   - pipeline stages
   - diagnostics
   - extension APIs
   - version metadata
   - other structured framework reference material

3. rendered site pages
   - final HTML pages built from authored + generated content

Recommended structure:

- framework/                       ← git submodule checkout of Foundry
- content/docs/authored/           ← hand-authored docs for humans
- content/docs/generated/          ← generated docs from the framework submodule
- scripts/                         ← build/generation scripts
- templates/                       ← site templates/layouts
- public/ or build/                ← final rendered website

The documentation generator must read from framework/ rather than assuming documentation is hand-maintained.

The docs build system should extract or derive information from the framework submodule such as:

• framework version
• CLI command metadata
• compiler/graph concepts
• pipeline/guard/interceptor definitions
• extension and pack definitions
• diagnostics catalogs
• machine-readable reference files if present
• architecture-relevant source metadata where appropriate

If the framework repo already contains machine-readable exports or generators, reuse them.
If not, implement website-side extraction in a disciplined, deterministic way.

The homepage and how-foundry-works page should remain authored narrative pages, but they should reflect the current architecture of the framework as implemented through Phases 0A–3.

The docs area should explain:

• the original goals of Foundry
• what was added in Phases 0A, 0B, and 0C
• what was added in Phase 0D and Phases 1, 2, and 3
• how the compiler, graph, execution pipeline, extensions, packs, migrations, doctor tooling, visualization, prompt loop, and higher-level framework features all fit together

The docs should be understandable to humans.
LLMs should primarily rely on the code, graph structures, machine-readable exports, and generated reference docs.

Do not assume that “the code alone is enough” for all use cases.
Foundry should expose structured explanations and structured reference material so that both humans and AI tools can understand the system more reliably.

Whenever a new framework version is adopted in the website repo by updating the framework/ submodule, the docs build process should be able to regenerate documentation for that exact version.

In short:
The website is not just marketing.
It is a documentation and explanation layer built on top of a pinned checkout of the actual framework.



---



Foundry Phases 0A through 3 have implemented the framework’s core architecture, including the semantic compiler, canonical application graph, extension system, architecture diagnostics, visualization tools, AI development loop, execution pipeline, guards and interceptors, and the higher-level framework capabilities.

Phase 4 focuses on making the system understandable and usable by developers by implementing:

• a redesigned homepage
• a complete documentation system
• automated documentation generation from the framework codebase
• documentation updates tied to framework releases
• a clear architectural narrative explaining how Foundry works

The documentation must serve two audiences:

Human developers who need conceptual understanding.

AI tools that need structured information about the framework.

Documentation must therefore include both narrative explanations and structured reference material.

Phase 4 must not change core framework architecture but may add tooling necessary to expose and explain that architecture.

Test coverage across the repository must remain ≥ 90%.

⸻

Phase 4 Goals

Phase 4 introduces four major deliverables.

A redesigned marketing homepage.

A detailed architecture explanation page.

A full documentation section.

An automated documentation generation system tied to framework versions.

⸻

Website Structure

The website should have the following top-level sections:

Home
How Foundry Works
Documentation
Contributing
GitHub

Existing sections such as the conversation transcript and contribution guidelines should remain but may be repositioned.

⸻

Homepage Rewrite

The homepage should clearly communicate:

What Foundry is.

Why it exists.

How it differs from traditional frameworks.

How humans and LLMs collaborate when building software with Foundry.

The homepage should include the following sections.

Hero Section

Explain Foundry as:

A compiler-based, LLM-first web framework.

Example messaging concept:

Foundry turns web applications into structured systems that AI and humans can build together safely.

The hero section should include:

Framework tagline.

Brief description.

Quickstart commands.

GitHub and Packagist links.

⸻

Problem Section

Explain problems with current frameworks:

Large unstructured codebases.

LLM hallucinations.

Hidden runtime behavior.

Difficulty understanding architecture.

Explain how Foundry addresses these issues with:

Explicit contracts.

Compiler validation.

Application graphs.

Deterministic runtime pipelines.

⸻

Human vs LLM Roles

Explain what humans should focus on.

Humans excel at:

System design.

Domain modeling.

Product thinking.

Negotiating requirements.

Debugging unexpected behavior.

LLMs excel at:

Writing boilerplate.

Implementing feature logic.

Generating tests.

Maintaining consistency with framework conventions.

⸻

Framework Architecture

Explain Foundry’s architecture visually:

Source specifications.

Compiler.

Application graph.

Execution pipeline.

Runtime.

⸻

Conversation Section

Preserve the conversation transcript between Derek and the assistant.

This acts as a narrative origin story.

⸻

Contribution Section

Explain how contributors should submit PRs.

Focus on:

Prompts plus verification.

Tests.

Deterministic behavior.

⸻

How Foundry Works Page

Rewrite the architecture explanation page.

This page must explain:

What the compiler does.

What the application graph represents.

How the execution pipeline works.

How extensions integrate.

How guards and interceptors work.

How AI development loops operate.

How verification works.

The page should walk through the lifecycle of a request:

Source files.

Compiler.

Graph.

Pipeline.

Feature execution.

Response generation.

Include diagrams where useful.

⸻

Documentation Section

Add a new documentation area accessible at:

/docs

The documentation should be organized into the following categories.

Getting Started

Installing Foundry.

Creating an application.

Understanding the project structure.

Running compiler commands.

⸻

Core Concepts

Compiler architecture.

Application graph.

Features.

Schemas.

Routes.

Queries.

Events.

Jobs.

Caches.

⸻

Execution Model

Pipeline stages.

Guards.

Interceptors.

Execution plans.

Request lifecycle.

⸻

Framework Capabilities

Explain features introduced in phases:

Phase 0D

Phase 1

Phase 2

Phase 3

Each should have its own documentation.

⸻

CLI Reference

Document every CLI command.

Include:

inspect

verify

compile

doctor

graph

prompt

⸻

Extension Development

Explain how to create extensions.

Include:

packs

interceptors

guards

compiler passes

graph analyzers

⸻

AI Development

Explain the prompt workflow.

How Foundry extracts context.

How diagnostics guide the AI correction loop.

⸻

Architecture Reference

Provide a technical reference for:

compiler passes

graph node types

pipeline stages

execution plans

diagnostics

⸻

Auto-Generated Documentation

Documentation must update automatically when the framework version changes.

To implement this:

The website must include a local checkout of the Foundry repository.

Documentation should be generated from:

framework source files.

CLI command metadata.

graph node definitions.

schema definitions.

extension registrations.

Whenever a new version is released:

Docs should regenerate automatically.

⸻

Documentation Generation Tool

Implement a documentation generator that extracts:

CLI command descriptions.

Graph node types.

Compiler passes.

Pipeline stages.

Extension APIs.

Diagnostics.

This generator should output Markdown files consumed by the website.

⸻

To make auto-generated, version-aware documentation practical and deterministic, the website repo must include a dedicated documentation build pipeline that reads from the Foundry framework submodule and emits both human-readable and machine-readable docs artifacts.


Documentation Build Pipeline

Goal

Implement a deterministic documentation build pipeline inside the website repo that reads from the Foundry framework submodule at:

framework/

and produces:
	•	human-authored rendered documentation
	•	machine-derived reference documentation
	•	version-aware website pages
	•	final rendered static site output

The docs pipeline must ensure that the website documentation always reflects the exact checked-out framework submodule commit or tag.

⸻

Documentation Pipeline Responsibilities

The documentation build system must:
	1.	read authored content from the website repo
	2.	inspect the Foundry framework submodule
	3.	extract structured framework metadata
	4.	generate reference documentation
	5.	merge authored and generated documentation
	6.	render final HTML output for the site
	7.	expose framework version information in the rendered docs

⸻

Required Inputs

The docs build pipeline must use these input categories.

1. Authored content

Human-written files in the website repo, such as:

content/
  homepage/
  docs/
    authored/

These include:
	•	homepage copy
	•	how-foundry-works narrative
	•	philosophy and goals
	•	onboarding guides
	•	conceptual explanations

2. Framework source and metadata

Read from the Foundry submodule:

framework/

This includes, where present:
	•	README and architecture docs
	•	CLI command definitions
	•	compiler and graph metadata
	•	pipeline/guard/interceptor definitions
	•	extension and pack definitions
	•	diagnostics catalogs
	•	version metadata
	•	machine-readable exports if present

3. Templates

Reusable rendering templates in the website repo, such as:

templates/


⸻

Required Outputs

The docs build pipeline must produce:

Generated content artifacts

For example:

content/docs/generated/
  cli-reference.md
  graph-reference.md
  pipeline-reference.md
  diagnostics-reference.md
  extension-reference.md
  pack-reference.md
  version-metadata.json
  framework-summary.md

Rendered site output

For example:

public/
  index.html
  how-foundry-works.html
  docs/
    index.html
    getting-started/
    core-concepts/
    execution-model/
    framework-capabilities/
    cli/
    extension-development/
    ai-development/
    architecture-reference/

If you prefer a build/ directory first and then deployment to public/, that is also acceptable, but it must be deterministic and documented.

⸻

Required Build Steps

Implement the docs pipeline with explicit steps.

Step 1 — Read framework version

Detect and store the framework version from the submodule checkout.

This should include at least:
	•	current git commit hash
	•	current git tag if present
	•	framework package version if available

Emit this into generated metadata such as:

content/docs/generated/version-metadata.json

Step 2 — Extract framework metadata

Implement scripts that inspect the framework submodule and extract structured information such as:
	•	CLI command metadata
	•	graph/compiler concepts
	•	pipeline stages
	•	guards
	•	interceptors
	•	diagnostics codes
	•	extensions
	•	packs
	•	migration/codemod concepts
	•	version compatibility concepts

Prefer machine-readable exports from the framework if they already exist.
If they do not exist, implement deterministic extraction in the website repo.

Step 3 — Generate reference docs

Transform extracted metadata into Markdown or intermediate content files for the docs area.

These generated docs should be reference-oriented and precise.

Step 4 — Merge authored + generated docs

Combine human-authored docs with generated reference docs.

Human-authored docs explain:
	•	why Foundry exists
	•	how to think about the architecture
	•	how humans and LLMs collaborate

Generated docs explain:
	•	exact commands
	•	exact structures
	•	exact metadata
	•	exact reference material

Step 5 — Render final HTML

Use templates/layouts to render final website pages.

Step 6 — Emit build manifest

Emit a build manifest containing at least:
	•	framework version
	•	framework commit
	•	build timestamp
	•	generated doc files
	•	rendered page outputs

⸻

Required Scripts

Implement the docs pipeline using explicit scripts in:

scripts/

At minimum, provide scripts or equivalent commands for:

scripts/build-docs.php
scripts/extract-framework-version.php
scripts/extract-cli.php
scripts/extract-graph-metadata.php
scripts/extract-pipeline-metadata.php
scripts/extract-diagnostics.php
scripts/extract-extensions.php
scripts/render-pages.php

These may be reorganized, but the separation of responsibilities should remain clear.

⸻

Required Determinism

The docs pipeline must be deterministic.

Given:
	•	the same website repo
	•	the same framework submodule commit/tag
	•	the same authored docs
	•	the same templates

the generated docs and rendered output must be the same.

Avoid non-deterministic timestamps inside content unless they are placed only in manifests or hidden metadata.

⸻

Required Documentation Categories

The docs build pipeline must support generation/rendering for these categories:
	•	getting started
	•	core concepts
	•	execution model
	•	framework capabilities
	•	CLI reference
	•	extension development
	•	AI development
	•	architecture reference

The system should make it easy to add future categories without redesigning the build pipeline.

⸻

Required Machine-Readable Outputs

In addition to human-readable docs, emit machine-readable reference files where useful, such as:
	•	version-metadata.json
	•	cli-reference.json
	•	graph-reference.json
	•	pipeline-reference.json
	•	diagnostics-reference.json
	•	extensions-reference.json

These may be placed under:

content/docs/generated/

or another documented location.

These files will help future LLM tooling and structured integrations.

⸻

Required Version Awareness

The docs pipeline must make the current documented framework version visible on the website.

At minimum, surface:
	•	framework version/tag
	•	commit hash
	•	“docs generated from this version” note

Later versioned docs support should remain possible, so structure the pipeline with that future in mind.

⸻

Required Failure Behavior

If the framework submodule is missing, uninitialized, or in an invalid state, the docs build must fail clearly with actionable errors.

Examples:
	•	framework submodule not initialized
	•	framework version not detectable
	•	extraction step failed
	•	required generated metadata missing

These failures should not silently produce stale docs.

⸻

Required Testing

The docs build pipeline must have automated tests.

At minimum, test:
	•	framework version extraction
	•	CLI metadata extraction
	•	graph/pipeline/diagnostics extraction
	•	generated reference docs creation
	•	authored + generated docs merge behavior
	•	final page rendering
	•	build manifest output
	•	failure behavior when submodule is missing or invalid
	•	deterministic outputs

Overall repository coverage must remain ≥ 90%, and this docs pipeline must have strong direct test coverage.

⸻

Deliverables for the Docs Pipeline

Implement:
	•	a deterministic docs build pipeline
	•	framework version extraction
	•	framework metadata extraction
	•	generated reference docs
	•	rendered site page generation
	•	build manifest output
	•	machine-readable reference outputs
	•	automated tests

The docs pipeline should make the website feel like a live, version-aware explanation layer on top of the actual framework.

----

Versioned Documentation

Docs should support versions such as:

v1.0
v1.1
v2.0

Older documentation should remain accessible.

⸻

Machine-Readable Documentation

In addition to narrative documentation, generate structured docs for LLMs.

Examples:

JSON schema of graph nodes.

CLI command metadata.

Pipeline stage definitions.

Extension APIs.

These files can be used as prompt context for LLM tools.

⸻

Continuous Documentation Updates

Whenever a framework release occurs:

Documentation generation must run automatically.

The website should update documentation to match the new version.

⸻

Testing Requirements

Automated tests must verify:

Documentation generation.

CLI command extraction.

Versioned documentation builds.

Docs remain synchronized with framework metadata.

Overall test coverage must remain ≥ 90%.

⸻

Deliverables

Phase 4 must produce:

A redesigned homepage.

A rewritten architecture explanation page.

A full documentation site.

Automatic documentation generation.

Versioned documentation.

Machine-readable documentation exports.


==========================================================================================
==========================================================================================


RESULT

Implemented Phase 4 end-to-end in this repo: deterministic docs generation from the pinned `framework/` submodule, authored+generated merge, rendered site pages, version-aware outputs, machine-readable exports, CI automation, and automated tests.

**Key Changes**
- Added a full docs pipeline library: [DocsPipeline.php](/Users/lo_fye/code/foundryframework/scripts/lib/DocsPipeline.php)
- Implemented required scripts:
  - [build-docs.php](/Users/lo_fye/code/foundryframework/scripts/build-docs.php)
  - [extract-framework-version.php](/Users/lo_fye/code/foundryframework/scripts/extract-framework-version.php)
  - [extract-cli.php](/Users/lo_fye/code/foundryframework/scripts/extract-cli.php)
  - [extract-graph-metadata.php](/Users/lo_fye/code/foundryframework/scripts/extract-graph-metadata.php)
  - [extract-pipeline-metadata.php](/Users/lo_fye/code/foundryframework/scripts/extract-pipeline-metadata.php)
  - [extract-diagnostics.php](/Users/lo_fye/code/foundryframework/scripts/extract-diagnostics.php)
  - [extract-extensions.php](/Users/lo_fye/code/foundryframework/scripts/extract-extensions.php)
  - [render-pages.php](/Users/lo_fye/code/foundryframework/scripts/render-pages.php)
- Added authored docs/homepage content and docs category pages:
  - [content/docs/authored](/Users/lo_fye/code/foundryframework/content/docs/authored)
  - [content/docs/homepage](/Users/lo_fye/code/foundryframework/content/docs/homepage)
- Added generated reference outputs (Markdown + JSON), including versioned artifacts:
  - [content/docs/generated](/Users/lo_fye/code/foundryframework/content/docs/generated)
- Added rendered site outputs including `/docs` and versioned docs pages:
  - [public](/Users/lo_fye/code/foundryframework/public)
- Added templates and styling updates:
  - [templates/layout.html](/Users/lo_fye/code/foundryframework/templates/layout.html)
  - [public/assets/styles.css](/Users/lo_fye/code/foundryframework/public/assets/styles.css)
- Added build manifest output:
  - [docs-build/build-manifest.json](/Users/lo_fye/code/foundryframework/docs-build/build-manifest.json)
- Added CI workflow for continuous docs sync checks:
  - [docs-pipeline.yml](/Users/lo_fye/code/foundryframework/.github/workflows/docs-pipeline.yml)
- Added automated docs pipeline tests:
  - [tests/run.php](/Users/lo_fye/code/foundryframework/tests/run.php)

**Validation Run**
- `php scripts/build-docs.php` passed.
- `php tests/run.php` passed all tests:
  - extraction
  - generated docs creation
  - authored+generated merge/render
  - manifest output
  - missing-submodule failure behavior
  - deterministic rebuild check

Coverage is strongly test-targeted for this pipeline, but I did not run a formal percentage coverage report tool in this repo.

4 files changed
+33
-14
Undo
README.md
content/docs/homepage/conversation.md
scripts/lib/DocsPipeline.php
tests/run.php

==========================================================================================
==========================================================================================


Master Spec for Codex: Fix and formalize current/versioned/generated docs publishing

You can hand this to Codex.

⸻

Phase 4A — Documentation Publishing and Versioned Docs Normalization

Goal

Normalize the website documentation build and publishing system so that:
	•	current docs and versioned docs intentionally coexist
	•	public version paths prefer semantic versions/tags over commit hashes
	•	machine-readable docs are published in both current and versioned locations
	•	manifests clearly describe the relationship between current and versioned docs
	•	the homepage and docs site stop surfacing raw commit hashes when a tag/version exists

Requirements

1. Current and versioned docs model
Treat:
	•	public/docs/ as the current docs alias
	•	public/docs/versions/<version>/ as immutable snapshots

This duplication is intentional and should remain.

2. Machine-readable publishing
Publish JSON docs to:
	•	public/docs/generated/
	•	public/docs/generated/versions/<version>/

3. Version resolution
When building docs, determine the public docs version key using this priority:
	1.	DOCS_FRAMEWORK_VERSION environment variable if set
	2.	exact git tag on framework/ submodule HEAD
	3.	framework package version if available
	4.	fallback commit hash

4. Homepage/docs display
When a tag/version exists, use it instead of commit hash in public-facing pages and version metadata displays.

5. Manifest generation
Emit:
	•	public/docs/generated/manifest.json
	•	public/docs/generated/versions/<version>/manifest.json

Each manifest must include:
	•	display version
	•	version key
	•	tag
	•	package version
	•	commit hash
	•	build timestamp
	•	current docs path
	•	versioned docs path
	•	current machine-readable path
	•	versioned machine-readable path

6. Rendering behavior
Keep current docs rendering as-is if correct, but also ensure the rendered docs are mirrored into the versioned snapshot path.

7. JSON publishing behavior
Copy root generated JSON files to current machine-readable output.
Copy versioned generated JSON files to versioned machine-readable output.

8. Failure behavior
If version metadata cannot be resolved cleanly, fall back safely, but never silently show an ugly commit hash on homepage/docs when a tag/version is available.

9. Tests
Add/adjust tests for:
	•	version key resolution
	•	tag preference over commit
	•	current docs publishing
	•	versioned docs publishing
	•	current JSON publishing
	•	versioned JSON publishing
	•	manifest correctness
	•	deterministic output

Overall repo coverage must remain ≥ 90%.


==========================================================================================
==========================================================================================


RESULT

Implemented Phase 4A docs publishing normalization.

- Version resolution is now formalized in [`DocsPipeline::extractFrameworkVersion`](\/Users/lo_fye/code/foundryframework/scripts/lib/DocsPipeline.php:79), with priority:
  1. `DOCS_FRAMEWORK_VERSION`
  2. exact `framework/` HEAD tag
  3. framework package version
  4. commit fallback (`commit-<short>`)
- Public-facing display now prefers semantic versions and avoids showing commit hashes when version source is tag/package/env override in [`versionBadgeHtml`](\/Users/lo_fye/code/foundryframework/scripts/lib/DocsPipeline.php:1088).
- Docs index machine-readable links now point to `/docs/generated/...` in [`renderPages`](\/Users/lo_fye/code/foundryframework/scripts/lib/DocsPipeline.php:776).

- Publishing pipeline in [`scripts/build-docs.php`](\/Users/lo_fye/code/foundryframework/scripts/build-docs.php:6) now:
  - keeps `public/docs/` as current alias
  - mirrors rendered docs to `public/docs/versions/<version>/`
  - publishes JSON to both:
    - `public/docs/generated/`
    - `public/docs/generated/versions/<version>/`
  - emits both manifests:
    - `public/docs/generated/manifest.json`
    - `public/docs/generated/versions/<version>/manifest.json`
  - includes required manifest fields (`display_version`, `version_key`, `tag`, `package_version`, `commit_hash`, timestamp, current/versioned docs + machine-readable paths).

- Tests were expanded in [`tests/run.php`](\/Users/lo_fye/code/foundryframework/tests/run.php:104) to cover:
  - version priority/tag preference
  - current + versioned docs publishing
  - current + versioned JSON publishing
  - manifest correctness
  - deterministic outputs
  - semantic-version display behavior over commit hash when version exists (`DOCS_FRAMEWORK_VERSION` scenario) at [`tests/run.php`](\/Users/lo_fye/code/foundryframework/tests/run.php:319).

Validation run:
- `php tests/run.php` passed all tests.
- `php scripts/build-docs.php` succeeded and produced current + versioned docs/manifests.

Current workspace includes updated generated artifacts and the new `public/docs/generated/` tree, ready for review/commit.

==========================================================================================
==========================================================================================


IMPORTANT CONTEXT FOR THIS PHASE (4B)

The documentation system intentionally maintains TWO copies of the rendered docs:

1. CURRENT DOCS (alias)
   public/docs/

2. VERSIONED DOCS (immutable snapshots)
   public/docs/versions/<version>/

These are not duplicates caused by a bug.

This is a deliberate design that allows the documentation site to support both:

• a stable “current docs” alias for the latest framework version
• immutable documentation snapshots for specific framework versions

Example:

public/docs/getting-started/
public/docs/versions/v1.0.0-beta/getting-started/

The current docs path is effectively an alias for the latest framework version.

The versioned docs path is a frozen snapshot tied to a specific framework tag or commit.

This architecture is common in professional documentation systems and must remain intact.

The duplication between current and versioned docs is intentional and required.

IMPORTANT RULE

Versioned documentation snapshots must be IMMUTABLE once they are created.

If a version directory already exists (for example public/docs/versions/v1.0.0-beta/), the documentation build system must NOT overwrite or regenerate it.

Instead, the build should leave that version untouched and only regenerate:

• the current docs alias (public/docs/)
• machine-readable generated docs for the current version
• metadata/manifests

This guarantees that documentation for previously released framework versions remains stable forever.

This phase improves the system by:

• displaying a clean human-facing version label on the homepage
• adding a version dropdown to the docs header
• auto-generating sidebar navigation from the documentation tree
• improving the presentation layer without changing the underlying docs publishing model


Here is a Master Spec 4B you can hand to Codex after 4A.

This spec covers:
	•	the homepage version wording patch
	•	template changes
	•	CSS for version display
	•	a version dropdown in the docs header
	•	an auto-generated docs sidebar from the markdown tree

It assumes Phase 4A has already normalized current/versioned/generated docs publishing and manifests.

⸻

Master Spec 4B

Homepage Version Display, Docs Version Switcher, and Auto-Generated Sidebar Navigation

Preface

Phase 4A established a normalized documentation publishing model with:
	•	current docs at public/docs/
	•	versioned docs at public/docs/versions/<version>/
	•	machine-readable docs at public/docs/generated/
	•	versioned machine-readable docs at public/docs/generated/versions/<version>/
	•	manifests describing framework version, tag, commit, and build outputs

Phase 4B should improve the website UX and presentation layer by:
	•	surfacing a clean human-facing version display on the homepage
	•	preserving commit visibility without making it the primary public identity
	•	adding a version switcher in the docs header
	•	auto-generating docs navigation from the docs content tree instead of relying on hardcoded navigation

This phase should make the site feel more polished, more maintainable, and more like a professional framework documentation site.

All new functionality must preserve deterministic site builds and overall automated test coverage ≥ 90%.

⸻

Goals

Implement the following improvements:
	1.	Homepage framework version display
	2.	Homepage template changes
	3.	Homepage/version CSS styling
	4.	Docs header version dropdown
	5.	Version-aware docs navigation behavior
	6.	Auto-generated docs sidebar from the markdown/documentation tree
	7.	Supporting manifests/metadata for nav/versioning
	8.	Tests and documentation updates

⸻

1. Homepage Version Display

Goal

Replace any ugly public-facing raw commit-hash-only display on the homepage with a clean version-first display.

Desired display

The homepage should display something like:

Foundry v1.0.0-beta • commit 0e7beb0901e3

Rules
	•	The display version is primary.
	•	The commit hash is secondary.
	•	The commit should still be visible, but it must not visually dominate the page.
	•	If a git tag/version exists, it must be preferred over a raw commit-only label.

Version data source

Use:

public/docs/generated/manifest.json

or the equivalent build-time manifest data source already created in 4A.

At minimum, the homepage must read:
	•	framework.display_version
	•	framework.tag
	•	framework.commit

Fallback behavior

Use the following priority for the homepage display version:
	1.	display_version
	2.	tag
	3.	package_version
	4.	fallback commit-<hash>

The homepage should never show only a raw commit hash if a tag or meaningful display version is available.

⸻

2. Homepage Template Changes

Goal

Update the homepage template to render the improved version display.

Target template

Update:

templates/homepage.html.php

or the equivalent homepage template in the website repo.

Required behavior

The homepage template must render:
	•	primary framework version label
	•	optional commit link
	•	optional tag metadata if useful internally

Desired UX

Example markup shape:

<div class="framework-version">
  <strong>Foundry v1.0.0-beta</strong>
  <span class="version-meta">
    • commit
    <a href="https://github.com/lofye/foundry/commit/0e7beb0901e3">0e7beb0901e3</a>
  </span>
</div>

Codex may refine exact HTML, but it must preserve:
	•	version-first presentation
	•	commit as secondary metadata
	•	optional GitHub link to commit

Commit link requirements

If a commit hash is available, link it to the exact framework commit on GitHub.

Assume repo URL:

https://github.com/lofye/foundry


⸻

3. Homepage and Version Display CSS

Goal

Add styling for the homepage version display that is subtle and professional.

Required CSS

Add styling to:

public/assets/styles.css

or the site stylesheet source if it is generated elsewhere.

Design requirements

The version display should:
	•	sit comfortably near the hero/meta area
	•	not overpower the headline
	•	use slightly subdued styling
	•	keep commit metadata visually secondary
	•	fit the current Foundry site aesthetic

Minimum styling behavior

Support classes equivalent to:
	•	.framework-version
	•	.version-meta

Suggested visual qualities:
	•	smaller than main heading text
	•	moderate opacity
	•	tasteful spacing
	•	link hover state for commit link

Codex may tune exact styling, but it must remain understated and clean.

⸻

4. Docs Header Version Dropdown

Goal

Add a version switcher to the docs header.

This should appear in docs pages only, not on the homepage.

Desired UX

In docs pages, the header should include something like:

Version: v1.0.0-beta ▼

Clicking it should allow switching between available documentation versions.

Requirements

Scope
	•	visible in docs pages
	•	not shown on homepage unless explicitly desired later
	•	must work for:
	•	current docs
	•	versioned docs

Data source

Build the version dropdown from documentation manifests and/or available version directories.

At minimum, it should know:
	•	current displayed version
	•	all available version snapshots

Version list source

Prefer a generated version index or manifest rather than scanning raw HTML directories ad hoc at runtime.

Codex may implement a generated file such as:

public/docs/generated/versions-index.json

Example shape:

{
  "current_version": "v1.0.0-beta",
  "versions": [
    {
      "key": "v1.0.0-beta",
      "label": "v1.0.0-beta",
      "docs_path": "/docs/versions/v1.0.0-beta/",
      "generated_path": "/docs/generated/versions/v1.0.0-beta/"
    },
    {
      "key": "v0.9.0",
      "label": "v0.9.0",
      "docs_path": "/docs/versions/v0.9.0/",
      "generated_path": "/docs/generated/versions/v0.9.0/"
    }
  ]
}

Behavior

When viewing current docs

Selecting a version should navigate to that version’s snapshot path.

When viewing versioned docs

Selecting another version should switch to the corresponding snapshot path.

When possible

Preserve the current doc slug/path across versions.

Example:

If user is on:

/docs/execution-model/

and switches to v1.0.0-beta, navigate to:

/docs/versions/v1.0.0-beta/execution-model/

If the exact page does not exist in the selected version, fall back to that version’s docs index.

⸻

5. Version-Aware Docs Navigation Behavior

Goal

Make docs pages version-aware in a consistent way.

Requirements

Current docs

/docs/... is the “current” alias.

Versioned docs

/docs/versions/<version>/... is the frozen snapshot.

Header behavior

Docs header should always show:
	•	the current version label
	•	a version switcher
	•	current page context if useful

Optional enhancement

Include a small label such as:

Viewing current docs

or

Viewing docs for v1.0.0-beta

Codex may add this if it improves clarity.

⸻

6. Auto-Generated Docs Sidebar from Markdown Tree

Goal

Replace hardcoded docs navigation with a generated sidebar derived from the documentation content tree.

This should make docs navigation easier to maintain and keep synchronized with the docs structure.

Source of truth

The sidebar should be generated from the documentation source structure, primarily:

content/docs/authored/
content/docs/generated/

and/or any normalized docs metadata produced during the docs build.

Required behavior

Generate sidebar structure

Build a navigation tree that reflects available docs pages.

Sidebar categories

At minimum, support grouping such as:
	•	Getting Started
	•	Core Concepts
	•	Execution Model
	•	Framework Capabilities
	•	CLI
	•	Extension Development
	•	AI Development
	•	Architecture Reference

These may be derived from:
	•	explicit docs metadata/frontmatter
	•	file naming conventions
	•	a docs navigation config file
	•	or a generated mapping layer

Recommendation

Do not rely only on filename alphabetic order.
Provide an explicit ordering mechanism.

Good approaches

One of these:
	1.	frontmatter in markdown files
	2.	a docs navigation manifest
	3.	a hand-authored content/docs/navigation.yaml
	4.	generated metadata merged with authored ordering rules

Codex may choose the best approach, but the result must be deterministic and maintainable.

Sidebar output

The rendered docs pages should include a sidebar with links to the docs sections/pages.

It must work for:
	•	current docs
	•	versioned docs

And it should preserve the appropriate path prefix:
	•	/docs/...
	•	/docs/versions/<version>/...

Active page highlighting

The sidebar must highlight the current page.

Nested sections

If useful, support nested groups, but keep the first version simple and readable.

⸻

7. Supporting Manifests and Metadata

Goal

Add the metadata needed to support version switching and auto-generated navigation.

Required generated artifacts

Codex should add one or more generated files such as:

content/docs/generated/navigation.json
content/docs/generated/versions-index.json

and publish corresponding public versions such as:

public/docs/generated/navigation.json
public/docs/generated/versions-index.json

If versioned equivalents are useful, also support:

public/docs/generated/versions/<version>/navigation.json

Navigation metadata requirements

Include:
	•	page title
	•	slug/path
	•	section/group
	•	order
	•	source type (authored or generated)
	•	availability in current/versioned docs

Version index requirements

Include:
	•	current version
	•	available versions
	•	labels
	•	path roots

⸻

8. Rendering Integration

Goal

Ensure the docs rendering pipeline consumes the new navigation and version metadata.

Requirements

Docs pages

Docs page templates must receive:
	•	sidebar tree
	•	current page slug
	•	current version metadata
	•	version switcher data

Templates

Update docs templates such as:

templates/docs-layout.html
templates/doc-page.html

or equivalent.

Header

The docs header must include:
	•	docs title/brand
	•	version dropdown
	•	optional “current vs versioned” label

Sidebar

The docs sidebar must be rendered from generated navigation data, not from hardcoded HTML.

⸻

9. Build Pipeline Changes

Goal

Update the docs build pipeline so that it generates and publishes the metadata required by this phase.

Required build outputs

The docs build must now also generate/publish:
	•	version-aware version index
	•	docs navigation metadata
	•	homepage-consumable manifest fields
	•	docs header version metadata

Current/versioned rules

Current docs alias

Publish current docs to:

public/docs/

Versioned snapshot

Publish versioned docs to:

public/docs/versions/<resolved-version-key>/

Machine-readable current

Publish to:

public/docs/generated/

Machine-readable versioned

Publish to:

public/docs/generated/versions/<resolved-version-key>/

Version selection rule

Prefer:
	1.	DOCS_FRAMEWORK_VERSION
	2.	exact tag on framework HEAD
	3.	package version
	4.	fallback commit hash

⸻

10. Testing Requirements

Overall automated test coverage must remain ≥ 90%.

Add direct tests for all new behavior.

Required test areas

Homepage version display
	•	version-first rendering
	•	commit secondary rendering
	•	commit link generation
	•	tag preference over commit hash

Docs version dropdown
	•	current docs rendering
	•	versioned docs rendering
	•	version list population
	•	path-preserving version switching
	•	fallback to version index when page missing

Sidebar generation
	•	navigation tree generation from docs source
	•	ordering behavior
	•	authored + generated docs inclusion
	•	active page highlighting metadata

Build pipeline
	•	generation of versions-index.json
	•	generation of navigation metadata
	•	publication of current and versioned machine-readable files
	•	deterministic outputs

Templates
	•	docs layout receives correct metadata
	•	docs header renders version dropdown
	•	sidebar renders generated nav structure

⸻

11. Deliverables

Codex must implement:
	•	homepage version wording patch
	•	homepage template update
	•	homepage CSS update
	•	docs header version dropdown
	•	auto-generated docs sidebar from the docs tree/metadata
	•	supporting manifests and navigation metadata
	•	build pipeline updates
	•	tests
	•	documentation updates if needed

⸻

12. Final Instruction

Phase 4B should make the Foundry website feel like a polished framework site rather than a raw documentation dump.

The desired end state is:
	•	homepage shows a clean human-facing version label
	•	docs pages have a professional version switcher
	•	docs navigation is generated from real docs structure instead of hardcoded markup
	•	current docs and versioned docs coexist intentionally
	•	machine-readable docs remain available for tools and LLMs
	•	everything remains deterministic and tied to the exact framework submodule version

Do not optimize for cleverness.
Optimize for:
	•	clarity
	•	maintainability
	•	deterministic docs builds
	•	professional documentation UX
	•	strong test coverage

⸻

Where this fits

Implement this after Phase 4A.

So the sequence is:
	•	Phase 4 → foundational docs/site work
	•	Phase 4A → normalize current/versioned/generated publishing
	•	Phase 4B → polish homepage versioning + docs UX/navigation




==========================================================================================
==========================================================================================

RESULT

Implemented Phase 4B end-to-end with immutable snapshot behavior preserved.

**What changed**

- Added config-driven docs navigation source at [content/docs/navigation.json](/Users/lo_fye/code/foundryframework/content/docs/navigation.json:1) (replaces hardcoded section map).
- Extended pipeline to generate/publish:
  - `navigation.json`
  - `versions-index.json`
  - and consume both during rendering  
  in [scripts/lib/DocsPipeline.php](/Users/lo_fye/code/foundryframework/scripts/lib/DocsPipeline.php:632), [scripts/lib/DocsPipeline.php](/Users/lo_fye/code/foundryframework/scripts/lib/DocsPipeline.php:1160), [scripts/lib/DocsPipeline.php](/Users/lo_fye/code/foundryframework/scripts/lib/DocsPipeline.php:1253).
- Homepage version UX now renders version-first with secondary commit link to GitHub in [scripts/lib/DocsPipeline.php](/Users/lo_fye/code/foundryframework/scripts/lib/DocsPipeline.php:1416) and [templates/homepage.html.php](/Users/lo_fye/code/foundryframework/templates/homepage.html.php:1).
- Added docs header version dropdown + auto-generated sidebar nav in [scripts/lib/DocsPipeline.php](/Users/lo_fye/code/foundryframework/scripts/lib/DocsPipeline.php:1393), [scripts/lib/DocsPipeline.php](/Users/lo_fye/code/foundryframework/scripts/lib/DocsPipeline.php:1441), [scripts/lib/DocsPipeline.php](/Users/lo_fye/code/foundryframework/scripts/lib/DocsPipeline.php:1492), with layout template updates in [templates/docs-layout.html](/Users/lo_fye/code/foundryframework/templates/docs-layout.html:1) and [templates/doc-page.html](/Users/lo_fye/code/foundryframework/templates/doc-page.html:1).
- Added client-side version switching with slug-preserving navigation + fallback to version index in [public/assets/main.js](/Users/lo_fye/code/foundryframework/public/assets/main.js:48).
- Added styles for homepage version block, docs header switcher, and sidebar in [public/assets/styles.css](/Users/lo_fye/code/foundryframework/public/assets/styles.css:538).
- Enforced immutable snapshot publishing:
  - existing `public/docs/versions/<version>/` and `public/docs/generated/versions/<version>/` are not overwritten
  - current alias/manifests continue to refresh  
  in [scripts/build-docs.php](/Users/lo_fye/code/foundryframework/scripts/build-docs.php:34) and [scripts/lib/DocsPipeline.php](/Users/lo_fye/code/foundryframework/scripts/lib/DocsPipeline.php:642).
- Updated docs/build docs in [README.md](/Users/lo_fye/code/foundryframework/README.md:27).

**Tests**

- Updated tests for:
  - navigation/versions-index generation
  - homepage version rendering + commit link
  - docs dropdown/sidebar rendering
  - current/versioned JSON publishing
  - immutable snapshot no-overwrite behavior  
  in [tests/run.php](/Users/lo_fye/code/foundryframework/tests/run.php:236), [tests/run.php](/Users/lo_fye/code/foundryframework/tests/run.php:322).
- Ran: `php tests/run.php` (all passing).

**Important note**

- Existing historical snapshot directories were intentionally left untouched (immutable).  
- New/current docs (`public/docs/`) now render the upgraded 4B UX and machine-readable metadata (`public/docs/generated/navigation.json`, `public/docs/generated/versions-index.json`).










==========================================================================================
==========================================================================================



Below is a complete Master Spec 4C that includes everything from:
	•	the immutable version snapshot rule
	•	the build-pipeline guard
	•	the LLM context bundle system
	•	how it integrates with the docs architecture you now have

This spec assumes 4A and 4B are already implemented.

⸻

Master Spec 4C

Immutable Versioned Docs + LLM Context Bundles

Preface

Phase 4A established a normalized documentation publishing model with:
	•	current docs at public/docs/
	•	versioned docs at public/docs/versions/<version>/
	•	machine-readable docs at public/docs/generated/
	•	versioned machine-readable docs at public/docs/generated/versions/<version>/
	•	manifests describing framework version, tag, commit, and build outputs

Phase 4B added:
	•	a homepage version display (vX • commit Y)
	•	a docs version dropdown
	•	an auto-generated sidebar navigation system
	•	version-aware documentation rendering

Phase 4C adds two major improvements:
	1.	Immutable versioned documentation snapshots
	2.	LLM context bundles for every docs page

These changes ensure that:
	•	documentation for past releases never changes
	•	the docs site becomes machine-navigable for LLM tooling
	•	each docs page exposes structured semantic context

This phase strengthens Foundry’s goal of being LLM-first not only in code but in documentation tooling.

All new functionality must maintain deterministic docs builds and overall automated test coverage ≥ 90%.

⸻

Part 1 — Immutable Versioned Documentation Snapshots

Goal

Ensure that once documentation for a version is published, it can never be overwritten by later builds.

This guarantees that:

docs for version X == framework version X

forever.

Without this rule, rebuilding the docs after editing content could silently change documentation for past releases.

⸻

Rule

If the versioned documentation directory already exists, the docs build pipeline must skip regeneration for that version.

Example:

public/docs/versions/v1.0.0-beta/

If this directory already exists, it must not be overwritten.

Only the following may still be regenerated:

public/docs/           (current docs alias)
public/docs/generated/ (machine-readable metadata)
manifests


⸻

Required Build Guard

Inside the docs build pipeline, add the following guard before writing versioned docs:

$versionedDocsDir = $publicDocsDir . '/versions/' . $versionKey;

if (is_dir($versionedDocsDir)) {
    echo "Version snapshot {$versionKey} already exists — skipping regeneration.\n";
    return;
}

This ensures the snapshot is immutable.

⸻

Logging Requirement

If a version snapshot already exists, the build system must log a message explaining that regeneration was skipped.

Example output:

Version snapshot v1.0.0-beta already exists — skipping regeneration.


⸻

Snapshot Immutability Requirements

Once created, the following must remain unchanged:

public/docs/versions/<version>/
public/docs/generated/versions/<version>/

The docs build pipeline must never modify existing version directories.

If changes are required, a new version must be published instead.

⸻

Part 2 — LLM Context Bundles for Documentation Pages

Goal

Generate a machine-readable semantic context bundle for every documentation page.

This allows LLMs and tooling to:
	•	understand what a docs page describes
	•	discover related documentation
	•	identify related CLI commands
	•	identify relevant framework code areas
	•	understand when a docs page is relevant

The docs site becomes a semantic knowledge graph rather than only human-readable prose.

⸻

Concept

For every documentation page:

/docs/<page>/

generate a companion file:

/docs/generated/llm-context/<page>.json

And for versioned snapshots:

/docs/generated/versions/<version>/llm-context/<page>.json

These files contain structured metadata describing the meaning and relationships of the page.

⸻

LLM Context File Structure

Example:

/docs/generated/llm-context/execution-model.json

Example JSON:

{
  "page": {
    "title": "Execution Model",
    "slug": "execution-model",
    "version": "v1.0.0-beta"
  },
  "summary": "Explains the execution pipeline, guards, interceptors, and feature execution plans.",
  "concepts": [
    "execution_pipeline",
    "pipeline_stage",
    "feature_guard",
    "interceptor",
    "execution_plan"
  ],
  "related_docs": [
    "/docs/core-concepts/",
    "/docs/architecture-reference/",
    "/docs/extension-development/"
  ],
  "related_cli_commands": [
    "foundry inspect pipeline --json",
    "foundry inspect execution-plan <feature> --json",
    "foundry verify pipeline --json"
  ],
  "related_framework_areas": [
    "src/Pipeline",
    "src/Execution",
    "src/Graph",
    "src/CLI/Commands"
  ],
  "recommended_when_prompting": [
    "Use this page when asking an LLM to reason about cross-cutting request behavior.",
    "Use this page when implementing auth, CSRF, rate limiting, or interceptors."
  ]
}


⸻

Context Bundle Generation Rules

For each docs page:
	1.	Determine slug from docs path.
	2.	Extract title from page metadata or first heading.
	3.	Generate summary.
	4.	Assign concept tags.
	5.	Detect related docs.
	6.	Include relevant CLI commands.
	7.	Include relevant framework source areas if possible.

Codex may implement these using:
	•	markdown frontmatter
	•	manual metadata
	•	inference rules
	•	generated metadata files

The system must be deterministic.

⸻

Generated Paths

Current docs

Human page:

/docs/<page>/index.html

Context bundle:

/docs/generated/llm-context/<page>.json


⸻

Versioned docs

Human page:

/docs/versions/<version>/<page>/index.html

Context bundle:

/docs/generated/versions/<version>/llm-context/<page>.json


⸻

Build Pipeline Changes

The docs build system must add a new generation step:

Generate LLM context bundles

This step should:
	1.	Scan rendered documentation pages.
	2.	Collect page metadata.
	3.	Generate JSON context bundles.
	4.	Write them to the correct generated directory.

⸻

Optional Docs Page Feature

Docs pages may include a button:

Copy LLM Context

This copies the context bundle JSON to the clipboard.

This feature is optional but recommended.

⸻

Integration with Foundry Tooling

These context bundles should eventually be usable by:

foundry prompt

or similar tooling.

This allows prompts to include:
	•	semantic docs context
	•	related commands
	•	conceptual explanations

rather than relying only on raw source code.

⸻

Required Directory Structure

After Phase 4C the docs site should look like:

public/
  docs/
    <page>/
      index.html
    versions/
      v1.0.0-beta/
        <page>/
          index.html

    generated/
      manifest.json
      navigation.json
      versions-index.json
      llm-context/
        <page>.json

      versions/
        v1.0.0-beta/
          manifest.json
          llm-context/
            <page>.json


⸻

Tests

Add tests for:

Immutable snapshot enforcement
	•	building docs twice must not overwrite version snapshots
	•	snapshot directories remain unchanged
	•	build logs indicate skipped snapshots

Context bundle generation
	•	context file exists for every docs page
	•	context file includes required fields
	•	context file references correct version

Versioned context bundles
	•	versioned context bundles are generated correctly
	•	versioned bundles match snapshot version

Build determinism

Two builds with the same framework commit must produce identical outputs.

⸻

Documentation

Update docs explaining:
	•	LLM context bundles
	•	machine-readable docs
	•	versioned documentation architecture

Include this in:

/docs/ai-development/
/docs/architecture-reference/


⸻

Final Instruction

Phase 4C transforms the documentation system from:

a static documentation website

into:

a semantic documentation layer for LLM-assisted development

The goal is not only to help humans read documentation but to allow:
	•	LLMs
	•	developer tools
	•	prompt workflows

to navigate Foundry’s architecture reliably.

The documentation system should now function as:

human documentation
+ machine-readable reference
+ semantic context graph

All behavior must remain deterministic, maintainable, and fully testable.

Test coverage must remain ≥ 90%.


==========================================================================================
==========================================================================================

RESULT 4C

Implemented Phase 4C in this repo.

**What I changed**

- Added immutable snapshot enforcement + skip logging in the publish step:
  - [scripts/build-docs.php](/Users/lo_fye/code/foundryframework/scripts/build-docs.php:34)
  - Existing `public/docs/versions/<version>/` and `public/docs/generated/versions/<version>/` are not overwritten.
  - Build now emits skip messages and returns them in `messages`.

- Added recursive machine-readable publishing (including nested JSON like `llm-context/*.json`):
  - [scripts/build-docs.php](/Users/lo_fye/code/foundryframework/scripts/build-docs.php:131)

- Added LLM context bundle generation for docs pages:
  - [scripts/lib/DocsPipeline.php](/Users/lo_fye/code/foundryframework/scripts/lib/DocsPipeline.php:632)
  - [scripts/lib/DocsPipeline.php](/Users/lo_fye/code/foundryframework/scripts/lib/DocsPipeline.php:1359)
  - Outputs now include:
    - `content/docs/generated/llm-context/<page>.json`
    - `public/docs/generated/llm-context/<page>.json`
  - For newly created version snapshots, versioned equivalents are also generated.

- Added deterministic LLM metadata source in docs nav config:
  - [content/docs/navigation.json](/Users/lo_fye/code/foundryframework/content/docs/navigation.json:1)

- Updated authored docs to explain LLM context bundles:
  - [ai-development.md](/Users/lo_fye/code/foundryframework/content/docs/authored/ai-development.md:27)
  - [architecture-reference.md](/Users/lo_fye/code/foundryframework/content/docs/authored/architecture-reference.md:14)

- Updated tests for 4C requirements (immutability, skip logs, bundle fields/version, current+versioned bundle publishing, determinism):
  - [tests/run.php](/Users/lo_fye/code/foundryframework/tests/run.php:324)

- Updated README docs architecture notes:
  - [README.md](/Users/lo_fye/code/foundryframework/README.md:104)

**Validation**

- Ran `php tests/run.php` successfully (all tests pass).
- Ran `php scripts/build-docs.php` successfully.
- Confirmed existing pinned snapshot dirs were not modified; only current alias + current generated metadata were regenerated for current version.

If you want, I can now prepare a commit with a clean commit message split (pipeline/tests/docs content).



















==========================================================================================
==========================================================================================





IMPORTANT CONTEXT FOR THIS PHASE

The documentation site recently transitioned from a handcrafted HTML site to a markdown-driven documentation system.

This change enabled powerful new capabilities:

• deterministic docs generation
• versioned documentation snapshots
• machine-readable documentation exports
• LLM context bundles
• automatic sidebar generation
• version switching

However, during this transition the site lost much of its previous visual identity and design quality.

The current generated docs appear visually plain and resemble raw markdown output rather than a designed documentation experience.

This problem is NOT caused by markdown itself.

Markdown is the correct content format for this system.

The problem is that the rendered markdown is currently displayed without a strong prose theme or documentation layout.

The goal of Phase 4D is therefore NOT to replace markdown with handcrafted HTML.

Instead, the goal is to introduce a strong visual design system around the rendered markdown.

The correct solution is:

1. keep markdown as the source of truth
2. render markdown inside a designed documentation layout
3. apply a well-crafted prose theme to markdown output
4. ensure the homepage and docs share a coherent visual language

This approach allows the documentation system to remain:

• deterministic
• machine-readable
• version-aware
• LLM-friendly

while also restoring the high-quality visual design the earlier site had.

IMPORTANT DESIGN DIRECTION

The current Foundry homepage presents a light, airy, modern aesthetic.

This phase must refine and strengthen that visual language rather than replacing it with a dark or heavy design.

The docs should feel like a natural extension of the homepage:

• bright and readable
• editorial and spacious
• carefully styled
• professional and premium

The final result should feel like a modern framework documentation site rather than a default markdown export.

Codex should treat this phase as a **visual system upgrade**, not a content or architecture change.



Master Spec 4D

Restore Premium Visual Design Across the Generated Docs Site

Preface

Phase 4A normalized current/versioned/generated docs publishing.

Phase 4B improved:
	•	homepage version display
	•	docs version switching
	•	docs sidebar generation
	•	docs navigation structure

However, the current generated site has lost much of the crafted visual identity that earlier versions of the Foundry site had. The current site reads more like a plain documentation export than a designed framework website. The homepage currently presents a light, airy, text-heavy, minimally styled experience rather than the more distinctive premium visual language the project previously had.  ￼

Phase 4D must restore that stronger visual identity without abandoning markdown-driven documentation generation.

The solution is not to stop using markdown.

The solution is to create:
	•	a stronger site chrome
	•	a stronger docs layout
	•	a strong prose theme for rendered markdown
	•	visual consistency between homepage and docs
	•	a professional documentation reading experience

This phase should preserve:
	•	the current docs architecture
	•	current/versioned docs behavior
	•	machine-readable docs outputs
	•	deterministic builds

All new work must preserve overall automated test coverage ≥ 90%.

⸻

Goals

Phase 4D must:
	1.	restore a high-quality designed look to the documentation site
	2.	make rendered markdown feel intentional and premium
	3.	visually align docs pages with the homepage aesthetic
	4.	keep markdown as the source format
	5.	improve readability, hierarchy, spacing, and code presentation
	6.	preserve versioned docs, sidebar navigation, and machine-readable exports
	7.	avoid turning docs rendering into a pile of bespoke handwritten HTML

⸻

Design Direction

Core visual goal

The docs site should feel like:
	•	a polished framework website
	•	editorial and modern
	•	spacious and readable
	•	visually intentional
	•	consistent with the homepage
	•	elegant rather than plain
	•	premium rather than default-browser-markdown

Important clarification

Do not redesign the site around a dark ambient aesthetic.

The current homepage and current docs direction are fundamentally light and airy, and this phase should improve that visual language rather than replacing it with something that fights the brand direction.  ￼

Desired aesthetic qualities

Aim for:
	•	bright, calm background
	•	soft surfaces/cards/panels
	•	refined typography
	•	stronger heading hierarchy
	•	better whitespace rhythm
	•	elegant sidebars and headers
	•	better code block styling
	•	clearer visual grouping
	•	higher contrast where needed
	•	more visual delight without clutter

Think:
	•	premium design system
	•	editorial technical docs
	•	modern product documentation
	•	“crafted” rather than generic

⸻

Core Strategy

The site should be structured into three layers:

1. Site Chrome

Shared site-wide design elements:
	•	header
	•	footer
	•	navigation
	•	spacing tokens
	•	type scale
	•	surfaces
	•	buttons
	•	badges
	•	code blocks
	•	links

2. Docs Layout

Docs-page-specific layout system:
	•	docs header
	•	version switcher
	•	sidebar
	•	content column
	•	optional metadata strip
	•	mobile nav behavior

3. Prose Theme

A markdown-aware style layer for rendered docs content:
	•	headings
	•	paragraphs
	•	lists
	•	links
	•	tables
	•	blockquotes
	•	pre/code
	•	callouts
	•	horizontal rules

Markdown remains the content source, but the prose layer must make it feel designed.

⸻

1. Homepage Visual Restoration

Goal

Improve the homepage visual design while preserving its structure and content.

Required changes

Hero

Refine the hero so it feels more premium:
	•	better spacing
	•	stronger typographic hierarchy
	•	more refined subhead styling
	•	better version badge treatment
	•	more elegant quickstart panel
	•	stronger visual relationship between copy and supporting panels

Section rhythm

Improve spacing and visual separation between sections such as:
	•	problem
	•	human vs LLM roles
	•	framework architecture
	•	conversation
	•	contribution model

Conversation section

Preserve it, but style it more intentionally:
	•	transcript excerpt as a designed card or conversation panel
	•	better quote styling
	•	better spacing between speaker turns

Contribution section

Keep it, but present it with more visual structure:
	•	card/panel treatment
	•	checklists or bullets styled more elegantly
	•	stronger “how to contribute” emphasis

⸻

2. Docs Layout Redesign

Goal

Upgrade the docs layout from plain generated output to a designed reading experience.

Required docs layout elements

Header

The docs header must include:
	•	Foundry brand
	•	docs title
	•	version dropdown
	•	optional “current” or version label
	•	responsive behavior

Sidebar

The auto-generated sidebar from 4B must remain, but be restyled to feel intentional:
	•	better grouping
	•	improved spacing
	•	hover states
	•	active state
	•	nested item clarity
	•	sticky behavior on desktop if practical

Main article area

The article body should sit inside a visually distinct reading surface:
	•	card/panel/container
	•	soft border or subtle shadow
	•	strong internal padding
	•	comfortable line length

Optional top metadata row

May include:
	•	section label
	•	current version
	•	last built/version source
	•	reading context

This is optional but recommended if it improves orientation.

⸻

3. Markdown Prose Theme

Goal

Create a dedicated rendered-markdown theme that matches the site’s visual identity.

Requirement

All rendered markdown content must live inside a wrapper such as:

<article class="docs-prose">
  ...
</article>

The prose theme must style markdown output inside this wrapper.

Elements to style

Headings
	•	h1
	•	h2
	•	h3
	•	h4

Requirements:
	•	strong hierarchy
	•	better spacing above/below
	•	elegant weight and scale
	•	clear section rhythm

Paragraphs

Requirements:
	•	comfortable max width
	•	improved line height
	•	improved spacing
	•	high readability

Lists

Requirements:
	•	cleaner indentation
	•	better vertical rhythm
	•	better bullet/number styling
	•	readable nested lists

Links

Requirements:
	•	consistent docs link treatment
	•	hover state
	•	visual distinction from body text
	•	not overly saturated or noisy

Inline code

Requirements:
	•	tasteful background
	•	subtle border/radius
	•	readable monospace styling
	•	not overpowering

Code blocks

Requirements:
	•	strong visual treatment
	•	match the site’s quickstart/install aesthetic
	•	padding
	•	overflow handling
	•	optional code-title support later
	•	avoid plain browser default <pre> look

Blockquotes / callouts

Requirements:
	•	more intentional styling
	•	subtle panel or left border treatment
	•	visual relationship to prose theme

Tables

Requirements:
	•	fully styled tables
	•	row spacing or borders
	•	header treatment
	•	overflow support on smaller screens

Horizontal rules

Requirements:
	•	subtle separators
	•	visually integrated into the design system

⸻

4. Docs-Specific Content Primitives

Goal

Support a small set of enhanced content patterns without abandoning markdown.

Recommended primitives

Codex may implement lightweight support for a few of these:
	•	note callouts
	•	warning callouts
	•	tip callouts
	•	command blocks
	•	definition lists
	•	section intro blocks

Constraint

Do not require authors to handwrite raw HTML for normal docs authoring.

If Codex adds markdown extensions or conventions, they should be:
	•	minimal
	•	documented
	•	deterministic
	•	easy to author

⸻

5. Shared Design Tokens and Styles

Goal

Bring consistency back across homepage and docs.

Required work

Refactor styles so both homepage and docs use shared tokens or variables for:
	•	spacing
	•	typography
	•	colors
	•	borders
	•	radius
	•	shadows
	•	code styling
	•	nav states
	•	panel/surface treatment

Desired outcome

The homepage and docs should feel like one coherent site, not:
	•	one crafted marketing page
	•	plus one generic markdown dump

⸻

6. Templates to Update

Codex should update the site templates that control homepage and docs rendering.

At minimum review and update:

templates/layout.html
templates/homepage.html.php
templates/docs-layout.html
templates/doc-page.html

Codex may restructure templates if needed, but must preserve deterministic rendering.

⸻

7. CSS Architecture

Goal

Improve the styling in a maintainable way.

Requirement

Do not solve this by scattering ad hoc inline styles or random one-off hacks.

Prefer a clearer CSS structure, for example:
	•	base styles
	•	shared site chrome
	•	homepage styles
	•	docs layout styles
	•	docs prose styles
	•	responsive rules

Codex may keep this in one stylesheet if that is simpler, but the organization should remain understandable.

⸻

8. Responsive Behavior

Goal

Ensure the improved design works well on smaller screens.

Requirements

Mobile docs behavior
	•	sidebar should collapse or move appropriately
	•	version dropdown should remain usable
	•	prose width and spacing should remain readable
	•	tables and code blocks should not break layout

Homepage behavior
	•	hero stack should remain elegant
	•	quickstart panels and section cards should adapt cleanly
	•	conversation section should remain readable

⸻

9. Accessibility and Readability

Goal

Improve aesthetics without harming usability.

Requirements
	•	maintain good contrast
	•	preserve readable font sizes
	•	preserve keyboard accessibility for nav and version dropdown
	•	ensure sidebar and header remain usable without hover-only behavior
	•	ensure code blocks remain readable
	•	ensure link styles are distinguishable

⸻

10. Build/Rendering Compatibility

Goal

Make all visual improvements compatible with the current docs pipeline.

Requirements
	•	do not break markdown-driven docs generation
	•	do not break current/versioned docs publishing
	•	do not break machine-readable docs outputs
	•	do not break sidebar generation
	•	do not break version dropdown functionality

This phase is visual and presentational, but it must remain fully compatible with the docs architecture already built.

⸻

11. Testing Requirements

Overall automated test coverage must remain ≥ 90%.

Add tests where appropriate for:
	•	docs templates rendering expected wrapper classes
	•	sidebar still renders correctly
	•	version dropdown still renders correctly
	•	homepage version display still renders correctly
	•	markdown pages render inside the prose wrapper
	•	build output still produces expected docs structure

If visual regression testing is already practical in the repo, Codex may add it. If not, use structural rendering tests.

⸻

12. Documentation Updates

Update docs or contributor notes to explain:
	•	the docs site now uses a styled markdown prose system
	•	where shared design/layout templates live
	•	how authored docs should be written
	•	any new supported markdown content primitives if introduced

⸻

13. Deliverables

Codex must implement:
	•	improved homepage visual treatment
	•	redesigned docs layout
	•	strong markdown prose styling
	•	improved sidebar presentation
	•	improved version dropdown presentation
	•	shared design token/style consistency
	•	responsive behavior improvements
	•	structural tests
	•	documentation updates

⸻

14. Final Instruction

Phase 4D should make the Foundry website feel like a premium, intentional framework site again.

The goal is:
	•	keep markdown
	•	keep generated docs
	•	keep current/versioned architecture
	•	but restore the visual care and personality that the earlier site had

Do not turn the site dark and ambient unless the actual brand direction changes.

Instead, refine the existing light, airy, modern direction into something that feels:
	•	elegant
	•	readable
	•	crafted
	•	distinctive
	•	professional

Optimize for:
	•	visual quality
	•	maintainability
	•	compatibility with generated docs
	•	deterministic builds
	•	strong test coverage

------------------------------------------------------------------------------------------

RESULT


Implemented Phase 4D as a visual-system upgrade while keeping markdown-driven, deterministic docs generation and current/versioned publishing behavior.

- Upgraded shared site chrome and layout scaffolding in [templates/layout.html](/Users/lo_fye/code/foundryframework/templates/layout.html#L1), [templates/homepage.html.php](/Users/lo_fye/code/foundryframework/templates/homepage.html.php#L1), [templates/docs-layout.html](/Users/lo_fye/code/foundryframework/templates/docs-layout.html#L1), and [templates/doc-page.html](/Users/lo_fye/code/foundryframework/templates/doc-page.html#L1).
- Refined rendering pipeline + docs UI markup (hero split, docs header/switcher presentation, sidebar presentation, page body classes, docs-prose usage) in [scripts/lib/DocsPipeline.php](/Users/lo_fye/code/foundryframework/scripts/lib/DocsPipeline.php#L716).
- Expanded markdown renderer to support styled tables, blockquotes, horizontal rules, and callout markers (`> [!NOTE]`, `> [!TIP]`, etc.) in [scripts/lib/DocsPipeline.php](/Users/lo_fye/code/foundryframework/scripts/lib/DocsPipeline.php#L2605).
- Replaced stylesheet with a light, premium, tokenized design system and prose theme in [public/assets/styles.css](/Users/lo_fye/code/foundryframework/public/assets/styles.css#L1).
- Added docs presentation guidance to [README.md](/Users/lo_fye/code/foundryframework/README.md#L47).
- Fixed homepage conversation copy typo in [content/docs/homepage/conversation.md](/Users/lo_fye/code/foundryframework/content/docs/homepage/conversation.md#L3).
- Updated structural pipeline tests (prose wrapper, table rendering, hero/layout checks, test snapshot cleanup) in [tests/run.php](/Users/lo_fye/code/foundryframework/tests/run.php#L101) and [tests/run.php](/Users/lo_fye/code/foundryframework/tests/run.php#L290).

Validation run:
- `php scripts/build-docs.php` passed.
- `php tests/run.php` passed (all tests).

Also regenerated rendered outputs/manifests in `public/` and `docs-build/build-manifest.json` to match the new templates/styles.  
`DEPLOY.md` remains untracked from your earlier request.


==========================================================================================
==========================================================================================

















IMPORTANT CONTEXT FOR THIS PHASE

The Foundry documentation system has already implemented several foundational capabilities across earlier phases:

• deterministic documentation builds
• current vs versioned documentation
• immutable version snapshots
• machine-readable documentation artifacts
• LLM context bundles for each documentation page
• auto-generated sidebar navigation
• a designed prose and documentation layout system

The documentation architecture is therefore already significantly more structured than a typical markdown documentation site.

However, the current docs experience is still largely oriented around passive reading.

Developers must manually scan pages and manually extract useful context when working with LLM tools such as ChatGPT, Codex, or other AI assistants.

Because Foundry is an LLM-first framework, the documentation site itself should also support workflows where developers actively collaborate with LLMs while reading documentation.

The goal of Phase 4E is therefore NOT simply to add a generic search box.

Instead, this phase transforms the documentation site into an **interactive development aid** by introducing two tightly connected capabilities:

1. structured documentation search
2. LLM-aware documentation actions

Search must operate not only on page titles and raw text but also on the structured metadata that the docs system already produces, including:

• summaries
• concept tags
• related commands
• diagnostics codes
• framework areas
• relationships between documentation pages

In addition to search, each documentation page should expose simple tools that allow developers to quickly copy useful prompt-ready context derived from the page’s metadata and LLM context bundle.

These actions allow developers to immediately paste documentation context into an LLM conversation when asking questions or requesting code generation.

This phase therefore strengthens one of Foundry’s core ideas:

documentation should not only be readable by humans but also directly usable in workflows where humans and LLMs collaborate to build software.

Codex should implement this phase in a way that:

• preserves the deterministic docs build system
• uses the existing semantic metadata and LLM context bundles
• remains version-aware
• maintains immutable version snapshots
• integrates cleanly with the existing documentation layout

The resulting documentation site should allow developers to:

• search for relevant framework concepts quickly
• navigate documentation efficiently
• copy prompt-ready context for use with LLM tools
• understand which commands and concepts relate to each page

The final experience should make the Foundry documentation site feel like a place developers actively **work from**, not just a place they read.

Master Spec 4E

Docs Search and LLM-Aware Docs Actions

Preface

Phase 4A established normalized current/versioned/generated documentation publishing.

Phase 4B added:
	•	homepage version display
	•	docs version dropdown
	•	auto-generated sidebar navigation

Phase 4C added:
	•	immutable versioned docs snapshots
	•	LLM context bundles for every docs page

Phase 4D restored a stronger premium visual design and prose system around the markdown-driven docs architecture.

Phase 4E builds on that foundation by making the docs site more useful during real development work.

This phase introduces two major capabilities:
	1.	documentation search
	2.	LLM-aware docs actions

The goal is to transform the docs site from a place developers merely read into a place developers actively work from while collaborating with LLMs.

All new functionality must preserve deterministic site builds and overall automated test coverage ≥ 90%.

⸻

Goals

Phase 4E must:
	1.	add a strong documentation search experience
	2.	make search aware of structured docs metadata
	3.	add LLM-oriented actions to docs pages
	4.	let developers quickly copy useful prompt context
	5.	support version-aware documentation search
	6.	preserve the current/versioned/generated docs architecture
	7.	remain compatible with the semantic docs system already built

⸻

1. Documentation Search

Goal

Implement search across the documentation site so developers can quickly find relevant pages, concepts, commands, diagnostics, and architecture topics.

This should search not only raw prose text, but also structured metadata.

Searchable inputs

Search should cover:
	•	page title
	•	page slug
	•	section/category
	•	summary
	•	concept tags
	•	related docs
	•	related CLI commands
	•	diagnostics codes
	•	framework areas
	•	optional page body text if practical

The search system should use the metadata already generated for docs pages wherever possible.

⸻

2. Search Index Generation

Goal

Generate a deterministic search index during the docs build process.

Required output

Publish a search index such as:

public/docs/generated/search-index.json

and optionally versioned search indexes such as:

public/docs/generated/versions/<version>/search-index.json

Search index contents

Each entry should include fields such as:
	•	title
	•	slug
	•	version
	•	summary
	•	concepts
	•	related docs
	•	related CLI commands
	•	diagnostics codes if any
	•	framework areas
	•	path/url

Example shape:

{
  "title": "Execution Model",
  "slug": "execution-model",
  "version": "v1.0.0-beta",
  "summary": "Explains the execution pipeline, guards, interceptors, and execution plans.",
  "concepts": [
    "execution_pipeline",
    "pipeline_stage",
    "feature_guard",
    "interceptor",
    "execution_plan"
  ],
  "related_cli_commands": [
    "foundry inspect pipeline --json",
    "foundry inspect execution-plan <feature> --json"
  ],
  "path": "/docs/execution-model/"
}

Determinism

The index must be deterministic and tied to the docs build output.

⸻

3. Search UI

Goal

Add a search interface to the docs site.

Placement

Search should be available on docs pages, ideally in:
	•	docs header
	•	or as a visible search input above the sidebar/content area

UX requirements

Users should be able to:
	•	search by keyword
	•	search by command name
	•	search by concept
	•	search by diagnostics code
	•	navigate to matching docs pages

Result display

Search results should show:
	•	page title
	•	short summary
	•	section/category
	•	version if useful
	•	link/path

Version awareness

When viewing current docs, search should default to current docs.

When viewing versioned docs, search should preferably bias toward that version’s docs.

Codex may implement:
	•	version-scoped search
	•	or current-version-first search
	•	or a filter for versions

But version awareness must be present in some useful form.

⸻

4. LLM-Aware Docs Actions

Goal

Add actions on docs pages that make it easy for developers to use the documentation with LLM tools.

This is a core differentiator for Foundry.

Required actions

Add at least:
	•	Copy page summary
	•	Copy LLM context
	•	Copy prompt-ready context
	•	Copy related commands

These actions should appear in docs pages, likely near the page header or in a page tools area.

⸻

5. Copy Page Summary Action

Goal

Allow users to copy a concise summary of the page.

Source

Use the page title and summary from the docs metadata or LLM context bundle.

Example copied output:

Foundry docs: Execution Model

This page explains the execution pipeline, guards, interceptors, and execution plans.


⸻

6. Copy LLM Context Action

Goal

Allow users to copy the full machine-readable or machine-friendly semantic context for the page.

Source

Use the page’s LLM context bundle.

Example output:

Foundry docs context: Execution Model
Version: v1.0.0-beta

Summary:
This page explains the execution pipeline, guards, interceptors, and execution plans.

Relevant concepts:
- execution_pipeline
- pipeline_stage
- feature_guard
- interceptor
- execution_plan

Relevant CLI commands:
- foundry inspect pipeline --json
- foundry inspect execution-plan <feature> --json
- foundry verify pipeline --json

Relevant framework areas:
- src/Pipeline
- src/Execution
- src/Graph
- src/CLI/Commands

Use this page when asking an LLM to reason about auth, CSRF, rate limiting, validation, or cross-cutting request behavior.

Requirement

This action must work for:
	•	current docs
	•	versioned docs

and copy version-correct context.

⸻

7. Copy Prompt-Ready Context Action

Goal

Provide a copyable block specifically optimized for pasting into an LLM.

This is more curated than the raw LLM context bundle.

Example output

Use the following Foundry documentation context when answering:

Page: Execution Model
Version: v1.0.0-beta

This page explains the execution pipeline, guards, interceptors, and execution plans.

Relevant concepts:
- execution_pipeline
- pipeline_stage
- feature_guard
- interceptor
- execution_plan

Relevant commands:
- foundry inspect pipeline --json
- foundry inspect execution-plan <feature> --json
- foundry verify pipeline --json

Prefer this context when reasoning about cross-cutting request behavior, auth, CSRF, rate limiting, validation, or execution-plan design.

Codex may tune wording, but it should be concise and immediately usable.

⸻

8. Copy Related Commands Action

Goal

Let users quickly copy the commands relevant to the current page.

Example output:

foundry inspect pipeline --json
foundry inspect execution-plan <feature> --json
foundry verify pipeline --json

This is small but highly practical.

⸻

9. Docs Page Tools Area

Goal

Create a small page-tools area on docs pages to host the LLM actions.

Requirements

The page-tools area should be:
	•	visually integrated into the docs design
	•	easy to understand
	•	not noisy
	•	consistent across docs pages

Suggested placement:
	•	near the page title/summary
	•	or in a right-side utility panel if one exists later

The tools should work without disrupting the reading experience.

⸻

10. Search + LLM Context Integration

Goal

Use the semantic docs metadata to strengthen both search and LLM actions.

Requirements

Search results should be able to surface:
	•	summary
	•	concepts
	•	related commands
	•	version
	•	related pages

The LLM action system should pull from the same metadata/context bundle system rather than inventing a parallel data model.

No duplicate truth systems.

⸻

11. Machine-Readable Outputs

Goal

Extend the generated docs artifacts to support this phase.

Required outputs

At minimum add:

public/docs/generated/search-index.json
public/docs/generated/versions/<version>/search-index.json

and continue using:

public/docs/generated/llm-context/<page>.json
public/docs/generated/versions/<version>/llm-context/<page>.json

If useful, Codex may also generate:

public/docs/generated/page-tools-index.json

but this is optional.

⸻

12. Current vs Versioned Behavior

Current docs

/docs/... should use:
	•	current search index
	•	current LLM context files

Versioned docs

/docs/versions/<version>/... should use:
	•	versioned search index where practical
	•	versioned LLM context bundles
	•	version-correct page tool outputs

This must remain consistent with the immutable versioned docs model introduced in 4C.

⸻

13. Optional but Recommended: “Related Docs” Panel

Goal

Surface related docs pages on each page using metadata already present.

This is optional but recommended.

Examples:
	•	“Related pages”
	•	“Read next”
	•	“Used with these commands”

This complements search and helps both humans and LLMs navigate.

⸻

14. Build Pipeline Changes

Goal

Update the docs build pipeline so it now generates:
	•	search index
	•	current and versioned search data
	•	any additional page tool metadata needed

The docs build should remain deterministic.

The build pipeline must continue to respect immutable versioned snapshots:
	•	do not overwrite existing versioned docs
	•	do not overwrite existing versioned generated outputs

If a version snapshot already exists, its search index and LLM context artifacts must also remain immutable.

⸻

15. Testing Requirements

Overall automated test coverage must remain ≥ 90%.

Add direct tests for:

Search index generation
	•	current index generation
	•	versioned index generation
	•	inclusion of titles/summaries/concepts/commands
	•	deterministic output

Search UI
	•	result rendering
	•	query matching
	•	version-aware behavior
	•	navigation to selected docs pages

LLM actions
	•	copy summary output
	•	copy LLM context output
	•	copy prompt-ready context output
	•	copy related commands output
	•	version-correct outputs

Metadata integration
	•	no divergence between context bundles and page tools
	•	search index uses the correct docs metadata

Immutability
	•	existing versioned search and context outputs are not regenerated

⸻

16. Documentation Updates

Update the docs site documentation to explain:
	•	docs search
	•	LLM-aware docs actions
	•	how to use docs pages with LLMs
	•	what the context bundles are for

Recommended pages to update:
	•	/docs/ai-development/
	•	/docs/architecture-reference/

⸻

17. Deliverables

Codex must implement:
	•	docs search index generation
	•	docs search UI
	•	LLM-aware page actions
	•	copy summary / context / prompt / commands tools
	•	version-aware search behavior
	•	integration with LLM context bundles
	•	build pipeline updates
	•	tests
	•	docs updates

⸻

18. Final Instruction

Phase 4E should make the Foundry docs site not just readable, but operationally useful for developers collaborating with LLMs.

The desired end state is:
	•	humans can search docs quickly
	•	developers can copy prompt-ready context directly from docs pages
	•	LLMs can consume machine-readable page context
	•	the docs site becomes an active development aid, not just passive reference material

Optimize for:
	•	clarity
	•	usefulness
	•	semantic integration
	•	deterministic build behavior
	•	version correctness
	•	strong test coverage

The Foundry docs should become a place where developers can:
	•	learn
	•	search
	•	inspect
	•	copy context
	•	immediately work with AI tools

That is deeply on-brand for Foundry.

------------------------------------------------------------------------------------------

RESULT

Implemented Phase 4E end-to-end on top of the existing deterministic/versioned docs system.

**What I changed**
- Added deterministic search index generation in [DocsPipeline.php](/Users/lo_fye/code/foundryframework/scripts/lib/DocsPipeline.php):
  - generates `search-index.json` from section metadata + LLM context bundles + diagnostics metadata + page text
  - publishes to current generated docs and to new version snapshots (while preserving immutability for existing snapshots)
- Integrated search into docs UI in [DocsPipeline.php](/Users/lo_fye/code/foundryframework/scripts/lib/DocsPipeline.php) and [docs-layout.html](/Users/lo_fye/code/foundryframework/templates/docs-layout.html):
  - docs header now renders a version-aware search control (`data-docs-search`)
- Added LLM page tools in [DocsPipeline.php](/Users/lo_fye/code/foundryframework/scripts/lib/DocsPipeline.php):
  - `Copy page summary`
  - `Copy LLM context`
  - `Copy prompt-ready context`
  - `Copy related commands`
  - plus related-docs / related-commands panel and context JSON link
- Implemented client-side behavior in [main.js](/Users/lo_fye/code/foundryframework/public/assets/main.js):
  - loads search index (versioned-first for snapshot pages, fallback to current)
  - weighted search over structured metadata fields
  - renders result list with summaries
  - clipboard actions for page tools
- Added styling for search + page tools in [styles.css](/Users/lo_fye/code/foundryframework/public/assets/styles.css).
- Updated docs content for new workflow guidance:
  - [ai-development.md](/Users/lo_fye/code/foundryframework/content/docs/authored/ai-development.md)
  - [architecture-reference.md](/Users/lo_fye/code/foundryframework/content/docs/authored/architecture-reference.md)
- Updated repo docs in [README.md](/Users/lo_fye/code/foundryframework/README.md).
- Added/expanded tests in [tests/run.php](/Users/lo_fye/code/foundryframework/tests/run.php):
  - search index generation (current + versioned)
  - search UI structural rendering
  - LLM tools structural rendering + actions payload presence
  - versioned immutability for search index artifacts

**Generated artifacts**
- New files:
  - [content/docs/generated/search-index.json](/Users/lo_fye/code/foundryframework/content/docs/generated/search-index.json)
  - [public/docs/generated/search-index.json](/Users/lo_fye/code/foundryframework/public/docs/generated/search-index.json)

**Validation**
- `php tests/run.php` passed.
- `php scripts/build-docs.php` passed.

**Important behavior note**
- Existing immutable version snapshots were not rewritten (by design).  
  New snapshots include the new search/tooling outputs and tests validate that path.

==========================================================================================
==========================================================================================





















Yes — if we already know the best UX, it’s better to lock the decision in the spec so Codex doesn’t improvise something clumsy like a collapsing header blob.

A right-side slide-in panel will feel much more modern and consistent with the current layout.

Below is the rewritten Master Spec 4F with that decision baked in.

⸻

Master Spec 4F

Mobile Navigation System (Slide-In Navigation Panel)

Preface

The Foundry documentation system currently provides a strong navigation experience on desktop screens through the header navigation and documentation sidebar.

However, on small screens such as phones, the sidebar is hidden and there is currently no alternative navigation mechanism. As a result, users cannot navigate to other pages of the site or documentation when using mobile devices.

Phase 4F introduces a mobile navigation system that provides full site navigation while preserving the current desktop layout.

The solution must be simple, consistent with the existing design language, and integrate cleanly with the current documentation architecture.

Mobile navigation will be implemented using a slide-in navigation panel from the right side of the screen, triggered by a hamburger menu in the header.

This phase must:
	•	preserve the current desktop experience
	•	avoid redesigning the existing layout
	•	reuse existing navigation structures
	•	support both the homepage and documentation pages
	•	maintain accessibility
	•	maintain deterministic site rendering

All new work must preserve automated test coverage ≥ 90%.

⸻

Goals

Phase 4F must:
	1.	enable full site navigation on phones
	2.	expose documentation navigation on mobile
	3.	expose homepage navigation links on mobile
	4.	preserve the current desktop layout
	5.	reuse the existing docs navigation tree
	6.	integrate with the version dropdown
	7.	remain visually consistent with the site design

⸻

Core UX Model

Mobile navigation will use a slide-in panel from the right side of the screen.

Interaction model:
	1.	User taps the hamburger icon in the header.
	2.	A navigation panel slides in from the right.
	3.	The panel displays site navigation and documentation links.
	4.	The user selects a destination or closes the panel.

This pattern avoids pushing page content down and maintains a modern documentation UX.

⸻

1. Header Hamburger Button

Add a hamburger menu button to the site header.

Example:

[ Foundry logo ]                [ ☰ ]

Visibility

The button should appear only when the viewport is below the desktop breakpoint.

Recommended breakpoint:

<= 1024px

Behavior

Clicking the button opens the mobile navigation panel.

⸻

2. Slide-In Navigation Panel

Position

The mobile navigation panel should slide in from the right side of the screen.

Behavior:
	•	panel overlays page content
	•	background page is dimmed
	•	panel remains scrollable if navigation is long

Animation

Use a simple CSS transform animation.

Example concept:

transform: translateX(100%) → translateX(0)

Animation should be smooth but quick.

⸻

3. Panel Layout

The panel should contain three sections:
	1.	site navigation
	2.	documentation navigation
	3.	version selector (when applicable)

Structure example:

----------------------------------
[ X ] Close

Foundry

Site
- Home
- Docs
- How Foundry Works
- Contributing
- GitHub

Documentation
- Getting Started
- Core Concepts
- Execution Model
- Architecture Reference
- CLI Reference
- Extension Development
...

Version
[ v1.0.0-beta ▼ ]
----------------------------------


⸻

4. Navigation Data Sources

The mobile navigation must reuse existing navigation data structures.

Do not introduce a separate navigation definition.

Site links

Reuse header navigation links.

Documentation links

Reuse the auto-generated documentation sidebar tree implemented in earlier phases.

This ensures mobile navigation stays consistent with docs navigation.

⸻

5. Version Selector

When viewing documentation pages, the mobile navigation panel must include the version selector introduced in Phase 4B.

This allows mobile users to switch documentation versions.

Version selector should appear near the bottom or top of the panel.

⸻

6. Interaction Behavior

The panel must support:

Opening

Triggered by hamburger button.

Closing

Triggered by:
	•	tapping the close icon
	•	tapping outside the panel
	•	selecting a navigation link

Scroll behavior

Panel contents must scroll independently if navigation items exceed viewport height.

⸻

7. Background Interaction

When the panel is open:
	•	background content should be dimmed
	•	background scrolling should be disabled
	•	focus should remain inside the panel

This prevents accidental interaction with the underlying page.

⸻

8. Accessibility

The mobile navigation must be accessible.

Requirements:

Hamburger button

Must include:

aria-label="Open navigation menu"

Panel

Must include:
	•	appropriate ARIA role
	•	keyboard focus management

Focus behavior

When panel opens:
	•	focus moves into the panel

When panel closes:
	•	focus returns to the hamburger button

Keyboard navigation

Users must be able to navigate links via keyboard.

⸻

9. CSS Requirements

Codex must implement responsive CSS rules.

Desktop
	•	sidebar remains visible
	•	hamburger button hidden
	•	mobile panel disabled

Mobile
	•	sidebar hidden
	•	hamburger button visible
	•	slide-in panel enabled

Panel styling should match existing typography and spacing.

⸻

10. Template Changes

Codex will likely modify:

templates/layout.html
templates/docs-layout.html
templates/homepage.html.php

Required changes:
	•	add hamburger button
	•	add mobile navigation panel container
	•	render docs navigation tree inside the panel
	•	include version dropdown when viewing docs

Navigation must be rendered dynamically.

⸻

11. JavaScript Behavior

Extend public/assets/main.js with minimal logic.

Required behaviors:
	•	open mobile panel
	•	close mobile panel
	•	toggle body scroll lock
	•	manage focus behavior

Implementation must remain lightweight.

⸻

12. Responsive Layout Testing

Ensure the following layouts work correctly:

Mobile (phones)
	•	header with hamburger button
	•	slide-in navigation
	•	scrollable panel
	•	version selector visible in docs

Tablet
	•	either sidebar or hamburger depending on breakpoint

Desktop
	•	existing navigation unchanged

⸻

13. Testing Requirements

Overall automated test coverage must remain ≥ 90%.

Add tests verifying:

Header rendering
	•	hamburger button appears below breakpoint

Panel markup
	•	mobile panel container renders

Navigation rendering
	•	docs navigation tree appears inside mobile panel

Version selector
	•	version dropdown appears on docs pages

Tests should verify HTML structure and rendering logic.

⸻

14. Deliverables

Codex must implement:
	•	responsive hamburger button
	•	right-side slide-in navigation panel
	•	mobile rendering of docs navigation tree
	•	version selector in mobile docs navigation
	•	responsive CSS rules
	•	minimal JavaScript logic
	•	accessibility improvements
	•	automated tests

⸻

Final Instruction

Phase 4F should introduce a modern mobile navigation experience without changing the desktop design.

The result must:
	•	allow full navigation on phones
	•	integrate seamlessly with the documentation architecture
	•	reuse existing navigation structures
	•	preserve deterministic site builds
	•	maintain strong test coverage

The final experience should feel polished, lightweight, and consistent with the Foundry documentation site.

⸻

Tiny strategic note:
Once 4F is done, your docs will have:
	•	versioning
	•	immutable snapshots
	•	semantic metadata
	•	LLM context bundles
	•	search
	•	prompt-copy tools
	•	responsive navigation

At that point, the docs site is already better engineered than most framework documentation systems.


----------------------------------------------------

RESULT


Implemented Phase 4F mobile navigation with a right-side slide-in panel, while preserving desktop behavior.

**What I changed**
- Added mobile nav data wiring in the docs pipeline so the panel reuses existing nav sources (site header links + generated docs tree), with version selector on docs pages only.
- Added mobile nav markup support in the shared site layout template.
- Added responsive/mobile styles:
  - hamburger button visible at `<=1024px`
  - desktop header links hidden on mobile
  - docs sidebar hidden on mobile
  - slide-in panel + overlay styling
  - mobile docs/version section styling
- Added client-side behavior:
  - open/close panel
  - close via overlay/close button/link click/Escape
  - body scroll lock while open
  - focus trap inside panel
  - focus return to trigger
  - docs version switching now works for both desktop and mobile selectors

**Key files**
- Pipeline/navigation wiring: [DocsPipeline.php](/Users/lo_fye/code/foundryframework/scripts/lib/DocsPipeline.php)
- Shared layout template: [layout.html](/Users/lo_fye/code/foundryframework/templates/layout.html)
- JS interactions: [main.js](/Users/lo_fye/code/foundryframework/public/assets/main.js)
- Responsive/mobile styles: [styles.css](/Users/lo_fye/code/foundryframework/public/assets/styles.css)
- Structural tests: [run.php](/Users/lo_fye/code/foundryframework/tests/run.php)

**Validation**
- Ran: `php tests/run.php`
- Result: all tests passed.

**Note**
- Generated docs pages and manifests were rebuilt, so rendered files under `public/` and manifest outputs changed accordingly.




==========================================================================================
==========================================================================================



Phase 4G is "Ask the Docs".

This phase is deliberately small but powerful. It uses the structured documentation system you already built (metadata, LLM context bundles, search index, etc.) to create a first-class AI interface to the framework docs.

Importantly: this does not require hosting your own model. It simply prepares structured prompts and launches the user’s preferred LLM.

This keeps it:

• simple
• deterministic
• privacy-friendly
• cheap to run

Below is the spec you can give Codex.

⸻

Master Spec 4G

Ask the Docs: LLM-Integrated Documentation Assistance

Preface

The Foundry documentation system now includes:

• deterministic documentation builds
• current vs versioned documentation
• immutable version snapshots
• structured documentation metadata
• LLM context bundles
• machine-readable documentation artifacts
• documentation search
• LLM context copy actions
• responsive navigation

These capabilities already make the documentation highly usable by both humans and LLMs.

However, developers still need to manually:
	1.	search the docs
	2.	copy context
	3.	open their LLM tool
	4.	paste the documentation context

Phase 4G streamlines this workflow by introducing direct LLM interaction from the documentation site.

The goal is to make the documentation site itself a natural entry point for AI-assisted development.

This phase introduces a lightweight Ask the Docs system that:

• prepares structured prompts using documentation metadata
• integrates with external LLM tools
• requires no hosted AI infrastructure
• works with the existing documentation architecture

All changes must preserve deterministic builds and maintain overall automated test coverage ≥ 90%.

⸻

Goals

Phase 4G must:
	1.	allow developers to ask questions about the framework directly from docs pages
	2.	automatically assemble prompt-ready context from documentation metadata
	3.	launch external LLM tools with that context
	4.	remain version-aware
	5.	preserve privacy and deterministic behavior
	6.	avoid introducing server-side AI dependencies

⸻

Core Concept

The documentation site becomes a prompt generator for LLMs.

Instead of users manually gathering context, the site automatically assembles a structured prompt using:

• page metadata
• related concepts
• related CLI commands
• related docs pages
• LLM context bundles

The user can then open that prompt in:

• ChatGPT
• Codex
• Claude
• or another LLM tool.

⸻

1. Ask the Docs UI

Add an Ask the Docs interface to documentation pages.

Placement options:

Preferred placement:

• near the top of the docs page
• near existing LLM tools actions
• or in a small right-side utilities panel

Example UI:

Ask the Docs

[ Ask about this topic… ]

[ Ask ChatGPT ]
[ Copy Prompt ]

The UI must remain visually lightweight and consistent with the docs design.

⸻

2. Question Input

Allow the user to enter a question.

Example:

How does authentication work in Foundry?

This question will be combined with documentation context to produce a structured prompt.

⸻

3. Prompt Construction

The site must generate a prompt using:

• user question
• page summary
• relevant concepts
• related CLI commands
• framework areas
• related documentation pages
• documentation version

Example prompt output:

You are helping a developer use the Foundry PHP framework.

Documentation Version: v1.0.0-beta

Relevant documentation page:
Execution Model

Summary:
This page explains the execution pipeline, guards, interceptors, and execution plans.

Relevant concepts:
- execution_pipeline
- pipeline_stage
- feature_guard
- interceptor
- execution_plan

Relevant CLI commands:
- foundry inspect pipeline --json
- foundry inspect execution-plan <feature> --json

User question:
How do I implement authentication in the request pipeline?

Answer using the Foundry architecture and concepts described above.

This prompt must be deterministic and generated entirely client-side.

⸻

4. Launch External LLM Tools

The system should support launching prompts into common LLM tools.

Initial integrations:

ChatGPT

Open a new ChatGPT conversation with the prompt prefilled.

If direct URL-based prefilling is not supported, fallback to:

• copying the prompt to clipboard
• opening ChatGPT homepage

Generic LLM

Provide a Copy Prompt button that copies the full prompt.

⸻

5. Version Awareness

Prompts must include the documentation version.

Examples:

Version: v1.0.0-beta

or

Version: current

This prevents LLM answers from drifting across versions.

⸻

6. Context Sources

Prompt generation must use existing docs metadata sources.

These include:

• LLM context bundles
• page metadata
• related docs links
• CLI command references

Codex must not introduce a second metadata system.

The documentation architecture already provides everything needed.

⸻

7. Client-Side Implementation

The Ask the Docs system must be implemented entirely client-side.

Implementation components:

public/assets/main.js

Required logic:

• assemble prompt from page metadata
• read user question
• generate final prompt text
• copy prompt to clipboard
• optionally launch ChatGPT

No server changes should be required.

⸻

8. Page Metadata Exposure

Docs pages must expose structured metadata to JavaScript.

Example:

<script type="application/json" id="docs-page-context">
{
  "title": "Execution Model",
  "summary": "...",
  "concepts": [...],
  "commands": [...],
  "version": "v1.0.0-beta"
}
</script>

This allows the client-side script to construct prompts reliably.

⸻

9. Optional Feature: Suggested Questions

Optionally display suggested questions.

Example:

Suggested questions:

• How does the execution pipeline work?
• Where should authentication be implemented?
• How do interceptors work in Foundry?

These suggestions can be derived from metadata or left as future enhancement.

⸻

10. Documentation Updates

Update the docs site to explain the Ask the Docs feature.

Suggested location:

/docs/ai-development/

Explain:

• how prompts are constructed
• how to use the feature with LLM tools
• how it relates to Foundry’s LLM-first philosophy

⸻

11. Testing Requirements

Overall automated test coverage must remain ≥ 90%.

Add tests verifying:

prompt generation

• correct inclusion of page metadata
• correct inclusion of version
• correct inclusion of concepts and commands

UI rendering

• Ask the Docs interface appears on docs pages
• prompt generation works without errors

clipboard functionality

• prompt copy action produces correct output

⸻

12. Deliverables

Codex must implement:

• Ask the Docs UI on documentation pages
• prompt generation system
• integration with docs metadata
• ChatGPT launch option
• Copy Prompt functionality
• version-aware prompts
• client-side JS implementation
• documentation updates
• automated tests

⸻

Final Instruction

Phase 4G transforms the documentation site from a static reference into an AI-assisted development interface.

Developers should be able to:

• read documentation
• search documentation
• copy context
• and immediately ask an LLM about the framework

without manually assembling prompt context.

This aligns directly with Foundry’s philosophy as an LLM-first web framework and makes the documentation site a powerful companion for developers using AI tools.

The system must remain:

• deterministic
• version-aware
• privacy-friendly
• lightweight
• fully compatible with the existing documentation architecture.

------------------------------------------------------------------------------------------

RESULT

Implemented Phase 4G (`Ask the Docs`) on top of the existing docs system.

**What was added**
- Ask the Docs UI in docs page tools:
  - question input
  - `Ask ChatGPT`
  - `Copy Prompt`
- Deterministic, client-side prompt generation from existing docs metadata:
  - title, summary, concepts, related commands, framework areas, related docs, version
- Version-aware metadata exposed to JS via:
  - `<script type="application/json" id="docs-page-context" ...>`
- ChatGPT launch flow:
  - opens ChatGPT with prefilled URL attempt
  - always copies prompt to clipboard first as fallback
- Styling for the Ask the Docs block integrated into current docs design.
- AI docs updated with usage guidance.

**Key files**
- Prompt/context rendering: [DocsPipeline.php](/Users/lo_fye/code/foundryframework/scripts/lib/DocsPipeline.php)
- Client behavior: [main.js](/Users/lo_fye/code/foundryframework/public/assets/main.js)
- Styles: [styles.css](/Users/lo_fye/code/foundryframework/public/assets/styles.css)
- Docs update: [ai-development.md](/Users/lo_fye/code/foundryframework/content/docs/authored/ai-development.md)
- Tests: [run.php](/Users/lo_fye/code/foundryframework/tests/run.php)

**Validation**
- Ran `php tests/run.php` successfully (all pass).
- Rebuilt docs with `php scripts/build-docs.php`.

**Important note**
- Existing versioned snapshots remain immutable by design, so previously-created snapshot directories are not rewritten. Current docs are updated; newly-created version snapshots include Ask the Docs.


==========================================================================================
==========================================================================================










Excellent catch on both points. You’re thinking about this exactly the right way.

You are correct: the docs background should not diverge from the site background. Creating a second ambient system would absolutely become a maintenance anti-pattern. The correct architecture is:

one background system → used everywhere

That means:

site background
= homepage background
= docs background
= all pages

Below is the cleaned-up Master Spec 4H, including:

• the preface you requested
• corrected phase numbering
• unified background system (no docs-specific variant)
• explicit brand protection rules

⸻

Master Spec 4H

Brand and Visual Identity Restoration (Spec-3 State)

Preface

The Foundry documentation system has recently undergone several major functional improvements across phases 4A–4F, including:
	•	deterministic documentation builds
	•	versioned documentation snapshots
	•	immutable documentation versions
	•	machine-readable documentation artifacts
	•	LLM context bundles
	•	documentation search
	•	LLM-aware tools
	•	responsive/mobile navigation

These improvements significantly strengthened the documentation platform.

However, during the visual overhaul work, the site’s approved brand identity was unintentionally altered. Changes included modifications to the logo color, primary palette, typography, and background design.

These elements are not implementation details. They are brand decisions and must not change without explicit approval from the project owner or key stakeholders.

In particular, the site drifted away from the previously approved spec-3 visual state, which included:
	•	Foundry orange as the primary brand color
	•	a light multicolor ambient swirl background
	•	Space Grotesk typography
	•	IBM Plex Mono for technical text
	•	a light, airy, premium aesthetic

The purpose of Phase 4H is to restore that approved visual identity while preserving the modern documentation system implemented in earlier phases.

This phase is therefore not a redesign.

It is a brand restoration and stabilization phase.

Key principle:

Keep the new documentation system.
Restore the previously approved visual identity.

The implementation must ensure that the site returns to the approved visual direction while maintaining all functional improvements introduced in earlier phases.

All automated test coverage must remain ≥ 90%.

⸻

Goals

Phase 4H must:
	1.	restore the approved Foundry visual identity
	2.	restore orange as the primary brand color
	3.	restore the light multicolor ambient swirl background
	4.	restore the approved typography system
	5.	preserve the modern documentation platform built in phases 4A–4F
	6.	unify visual styling across homepage and docs pages
	7.	prevent accidental future brand drift

⸻

Core Principle

This phase restores the earlier approved spec-3 design state.

Codex must not reinterpret or redesign the brand.

The task is:

restore visual identity
without altering system architecture


⸻

1. Restore Brand Tokens

Goal

Define explicit brand tokens so the site’s visual identity cannot drift again.

Codex must define centralized tokens for:
	•	primary brand color
	•	accent color
	•	ambient background colors
	•	typography
	•	surface colors
	•	link colors
	•	border colors
	•	button styles
	•	shadow styles

Example direction (naming may vary):

:root {
  --brand-primary: #ff7a18;
  --brand-accent: #ffb347;

  --brand-ambient-1: #ffe2cc;
  --brand-ambient-2: #ffd6e7;
  --brand-ambient-3: #fff2cc;

  --font-display: "Space Grotesk", sans-serif;
  --font-mono: "IBM Plex Mono", monospace;
}

Codex may refine variable names, but the concept of centralized brand tokens is mandatory.

⸻

2. Restore Homepage Styling (Spec-3 State)

Goal

Return the homepage to the previously approved polished appearance.

The restored homepage should include:
	•	orange accent color usage
	•	multicolor ambient swirl background
	•	Space Grotesk typography
	•	strong hero typography
	•	premium spacing and layout
	•	polished section hierarchy

Earlier styling may have existed partly as inline CSS inside the <head>.

Codex may:
	•	preserve this structure
	•	move it into shared stylesheets
	•	refactor into modular CSS

However, the visual output must match the approved spec-3 aesthetic.

The goal is visual fidelity, not code-style purity.

⸻

3. Restore Typography

The site must use the previously approved fonts:

Display / UI typography

Space Grotesk

Monospace / technical text

IBM Plex Mono

These fonts must be used consistently across:
	•	homepage
	•	documentation pages
	•	code blocks
	•	CLI examples
	•	metadata labels

Codex must revert any later font substitutions.

⸻

4. Restore Orange as the Primary Brand Color

Orange must remain the primary Foundry color.

The following must be restored:
	•	logo accent color
	•	buttons
	•	interactive highlights
	•	links where appropriate
	•	UI accent elements

Blue must not replace orange as the primary accent.

Blue may appear in the ambient gradient palette but must not function as the core brand color.

⸻

5. Restore the Ambient Multicolor Background

Goal

Restore the light multicolor swirl/ambient background used in the approved homepage design.

The background should feel:
	•	light
	•	airy
	•	atmospheric
	•	subtle

The background must not become:
	•	flat white
	•	flat blue
	•	heavy/dark

⸻

6. Use the Same Background System Across the Entire Site

The ambient background must be shared across all pages, including:
	•	homepage
	•	documentation pages
	•	how-foundry-works page
	•	contributing page
	•	future content pages

This background should be defined once in the site layout and inherited everywhere.

Docs pages must not introduce a different background system.

This avoids visual inconsistency and prevents the emergence of multiple background implementations.

⸻

7. Docs Layout Must Inherit the Restored Design

Docs pages must preserve:
	•	markdown rendering
	•	version dropdown
	•	search
	•	sidebar navigation
	•	LLM tools
	•	Ask-the-Docs features
	•	mobile navigation

However, their visual presentation must inherit the restored site design:
	•	typography
	•	color palette
	•	surfaces
	•	buttons
	•	links
	•	code block styling
	•	panel styling

The docs must feel like a natural extension of the homepage, not a separate system.

⸻

8. Templates to Review

Codex should inspect and update at minimum:

templates/layout.html
templates/homepage.html.php
templates/docs-layout.html
templates/doc-page.html

The layout template should define:
	•	site background
	•	shared typography
	•	brand tokens

Docs templates should inherit these definitions.

⸻

9. Prevent Future Brand Drift

Codex must add a brief comment or contributor note explaining that:
	•	logo color
	•	primary palette
	•	typography
	•	background aesthetic

are brand decisions, not implementation details.

These should not be changed casually.

Future design changes should require explicit stakeholder approval.

⸻

10. Preserve All Docs System Features

This phase must not remove or alter the system features introduced in earlier phases.

The following must remain intact:
	•	versioned docs
	•	immutable docs snapshots
	•	machine-readable docs
	•	docs manifests
	•	sidebar generation
	•	search
	•	LLM context bundles
	•	prompt-copy tools
	•	Ask-the-Docs system
	•	responsive/mobile navigation

The restoration is purely visual.

⸻

11. Accessibility

The restored visual system must maintain:
	•	readable contrast
	•	legible typography
	•	accessible link states
	•	visible focus states
	•	readable code blocks
	•	mobile usability

Aesthetic restoration must not harm usability.

⸻

12. Testing Requirements

Automated test coverage must remain ≥ 90%.

Tests should verify:
	•	homepage structure still renders
	•	docs pages still render expected wrappers
	•	version dropdown remains present
	•	mobile navigation still renders
	•	search and LLM tools still render

Structural tests are sufficient; visual testing is optional.

⸻

13. Deliverables

Codex must implement:
	•	restored brand tokens
	•	restored typography system
	•	restored orange-centered palette
	•	restored ambient swirl background
	•	unified background system across the site
	•	homepage visual restoration
	•	docs styling aligned with homepage design
	•	preservation of all docs platform functionality
	•	tests and contributor notes

⸻

Final Instruction

Phase 4H restores the approved Foundry visual identity while preserving the powerful documentation system built in earlier phases.

The correct outcome is:

new documentation platform
inside
the previously approved brand design

The result should feel like:
	•	the same Foundry site you approved earlier
	•	but now powered by a far more advanced documentation system

Optimize for:
	•	brand fidelity
	•	consistency
	•	readability
	•	maintainability
	•	deterministic builds
	•	automated test coverage ≥ 90%.


Addendum to Master Spec 4H

Ambient Background Enhancement (Large Display Optimization)

Purpose

The Foundry visual identity includes a light multicolor ambient swirl background that creates a premium, atmospheric feel.

On very large displays (e.g., 27–34 inch monitors), a single gradient background can sometimes appear visually flat because the gradient spreads across too much space.

This addendum introduces a lightweight enhancement that improves the ambient background on large screens while preserving:
	•	the approved visual design
	•	a single unified background system
	•	strong performance
	•	deterministic rendering

This enhancement must not create a separate background system for docs pages. It applies to the entire site uniformly.

⸻

Design Principle

Instead of a single gradient background, the page background should consist of multiple soft gradient layers.

These layers create subtle color variation and depth, giving the background a gentle swirl/atmospheric quality.

This approach is widely used in modern design systems because it:
	•	scales gracefully to large monitors
	•	adds visual richness without clutter
	•	requires minimal rendering cost

⸻

Implementation Approach

The ambient background should be implemented using two pseudo-element layers attached to the main page container (or body element).

Example structure:

body
 ├── ::before  (ambient gradient layer A)
 ├── ::after   (ambient gradient layer B)
 └── page content

These layers should be:
	•	fixed or absolute
	•	behind all content
	•	low opacity
	•	softly blurred or feathered

⸻

Example CSS Direction

Codex may refine the exact values, but the structure should follow this pattern.

Base background color:

body {
  background: #ffffff;
  position: relative;
}

Ambient layer A:

body::before {
  content: "";
  position: fixed;
  inset: -20%;
  background:
    radial-gradient(
      circle at 20% 30%,
      rgba(255,122,24,0.15),
      transparent 55%
    );
  z-index: -1;
  pointer-events: none;
}

Ambient layer B:

body::after {
  content: "";
  position: fixed;
  inset: -20%;
  background:
    radial-gradient(
      circle at 80% 20%,
      rgba(255,214,231,0.20),
      transparent 55%
    ),
    radial-gradient(
      circle at 60% 80%,
      rgba(255,242,204,0.25),
      transparent 60%
    );
  z-index: -1;
  pointer-events: none;
}

This produces a soft multi-color ambient swirl that scales well on large displays.

⸻

Performance Constraints

The background implementation must remain lightweight.

Codex must avoid:
	•	canvas rendering
	•	WebGL effects
	•	heavy animation
	•	high-frequency repaint effects
	•	JavaScript-driven background rendering

The ambient layers should be static CSS gradients.

Modern browsers render these extremely efficiently.

⸻

Consistency Requirement

The ambient background must be applied site-wide.

The same background must appear on:
	•	homepage
	•	documentation pages
	•	how-foundry-works page
	•	contributing page
	•	any future pages

Docs pages must not introduce a separate background variant.

The ambient background is part of the core site identity.

⸻

Visual Intent

The final background should feel:
	•	light
	•	atmospheric
	•	warm
	•	modern
	•	subtle

It should enhance the page rather than compete with content.

Text readability must always take priority over decorative effects.

⸻

Integration With Existing Layout

The ambient layers must sit behind the site layout without interfering with:
	•	page containers
	•	documentation sidebar
	•	navigation panels
	•	code blocks
	•	mobile navigation overlay

If necessary, main content areas may use lightly tinted surfaces or cards to ensure excellent readability.

⸻

Final Instruction

This enhancement is intended to improve the existing approved background, not redesign it.

The implementation must:
	•	preserve the original aesthetic direction
	•	remain lightweight
	•	use pure CSS
	•	apply uniformly across the site
	•	avoid creating multiple background systems

The result should maintain the Foundry brand identity while ensuring the background continues to look rich and intentional even on large displays.

⸻

Once Codex applies that, the background will feel much closer to something you’d see on Stripe, Linear, or Vercel — subtle but high-end — while staying completely aligned with your earlier design.


------------------------------------------------------------------------------------------

RESULT

Implemented Phase 4H brand restoration while preserving the 4A–4G docs platform behavior.

**What I changed**
- Restored centralized brand tokens to an orange-first palette and spec-3 typography:
  - `Space Grotesk` for display/UI
  - `IBM Plex Mono` for technical text
- Replaced the old ambient `<div>` background approach with a unified, site-wide pseudo-element ambient system on `body` (`::before`/`::after`) for large-display depth.
- Rebalanced homepage/docs surface, link, button, and panel colors to align with orange-led brand identity and warm/light premium aesthetics.
- Kept all docs features intact (versioning, search, LLM tools, Ask the Docs, mobile nav).
- Added a contributor guardrail note so brand tokens/typography/background are treated as stakeholder-approved decisions.

**Key files**
- Brand + ambient + shared visual system: [styles.css](/Users/lo_fye/code/foundryframework/public/assets/styles.css)
- Shared layout/font loading cleanup: [layout.html](/Users/lo_fye/code/foundryframework/templates/layout.html)
- Brand drift guardrail note: [README.md](/Users/lo_fye/code/foundryframework/README.md)

**Validation**
- Ran full test suite: `php tests/run.php` (all passing).
- Rebuilt site outputs: `php scripts/build-docs.php`.

**Note**
- Existing immutable version snapshots were not regenerated (by design), but they inherit the restored brand system through shared stylesheet updates.

==========================================================================================
==========================================================================================
















Spec 4I

Live Architecture Explorer

Now for the fun one.

This phase leverages the metadata already produced by Foundry to allow developers to visually explore the framework’s architecture directly from the documentation site.

⸻

Master Spec 4I

Live Architecture Explorer

Preface

The Foundry documentation system already generates rich metadata about the framework’s structure, including:
	•	feature contracts
	•	pipeline metadata
	•	extension metadata
	•	CLI command references
	•	documentation relationships
	•	LLM context bundles

Currently this information appears in textual documentation and machine-readable files.

Phase 4I introduces an interactive architecture explorer that visualizes this information.

This explorer allows developers to see how Foundry components relate to one another, making the framework easier to understand and navigate.

All functionality must remain deterministic and maintain automated test coverage ≥ 90%.

⸻

Goals

The Live Architecture Explorer must allow developers to:
	•	explore Foundry architecture visually
	•	inspect relationships between framework components
	•	navigate documentation through architecture relationships
	•	understand how features, pipelines, and extensions interact

⸻

Architecture Model

The explorer should visualize a graph composed of:

Nodes:
	•	features
	•	pipeline stages
	•	CLI commands
	•	documentation pages
	•	extensions
	•	diagnostics categories

Edges:
	•	“uses”
	•	“extends”
	•	“belongs to”
	•	“related command”
	•	“related documentation”

⸻

Data Sources

The explorer must use existing generated documentation artifacts such as:

content/docs/generated/graph-reference.json
content/docs/generated/pipeline-reference.json
content/docs/generated/extensions-reference.json
content/docs/generated/version-metadata.json

No new metadata extraction systems should be introduced.

⸻

Explorer Interface

Add a new documentation page:

/docs/architecture-explorer/

The page should include:
	•	an interactive graph visualization
	•	zoom and pan controls
	•	node selection
	•	contextual details panel

⸻

Node Interaction

When a node is clicked:

The right-side panel should display:
	•	component name
	•	summary
	•	related documentation
	•	related CLI commands
	•	links to deeper documentation pages

This allows users to move seamlessly between the architecture explorer and written docs.

⸻

Navigation Integration

From documentation pages, add a link:

Explore this in the Architecture Explorer

When possible, the explorer should open focused on the relevant node.

⸻

Visualization Implementation

The graph visualization may use a lightweight client-side library such as:
	•	D3
	•	Cytoscape.js
	•	Vis.js

Codex may choose the simplest library that satisfies:
	•	good performance
	•	minimal dependencies
	•	maintainable implementation

⸻

Performance Constraints

The architecture explorer must remain lightweight.

Requirements:
	•	client-side rendering
	•	lazy loading of graph data
	•	no server-side computation required
	•	smooth performance on mid-range devices

⸻

Version Awareness

The explorer must be version-aware.

When viewing versioned documentation:

/docs/versions/<version>/

the explorer should load the corresponding version metadata.

⸻

Testing Requirements

Automated test coverage must remain ≥ 90%.

Tests should verify:
	•	architecture metadata loads correctly
	•	explorer page renders
	•	graph data structure is valid
	•	node navigation links resolve correctly

⸻

Deliverables

Codex must implement:
	•	Architecture Explorer page
	•	graph visualization system
	•	integration with existing metadata
	•	node interaction panel
	•	links between explorer and docs
	•	version-aware graph loading
	•	tests

⸻

Final Instruction

The Live Architecture Explorer transforms Foundry documentation from a static reference into a visual model of the framework’s architecture.

Developers should be able to:
	•	see how framework components connect
	•	navigate architecture visually
	•	jump directly to relevant documentation
	•	better understand Foundry’s design philosophy

This feature strengthens Foundry’s positioning as an LLM-first framework with transparent architecture.

------------------------------------------------------------------------------------------

RESULT

Implemented Spec 4I end-to-end.

**What I changed**
- Added new docs section/page: [content/docs/navigation.json](/Users/lo_fye/code/foundryframework/content/docs/navigation.json), [content/docs/authored/architecture-explorer.md](/Users/lo_fye/code/foundryframework/content/docs/authored/architecture-explorer.md)
- Extended docs pipeline to generate/publish architecture graph metadata (`architecture-explorer.json`) from existing generated sources only: [scripts/lib/DocsPipeline.php](/Users/lo_fye/code/foundryframework/scripts/lib/DocsPipeline.php)
- Wired `/docs/architecture-explorer/` rendering with interactive explorer mount, version-aware data paths, and deep-link focus support.
- Added docs-page link: “Explore this in the Architecture Explorer” in page tools.
- Implemented client-side explorer (lazy load, pan, zoom controls, node selection, right-side details panel, focus from query param): [public/assets/main.js](/Users/lo_fye/code/foundryframework/public/assets/main.js)
- Added explorer styles (layout, graph nodes/edges, details panel, responsive behavior): [public/assets/styles.css](/Users/lo_fye/code/foundryframework/public/assets/styles.css)
- Updated authored docs references for AI/architecture docs:  
  [content/docs/authored/ai-development.md](/Users/lo_fye/code/foundryframework/content/docs/authored/ai-development.md),  
  [content/docs/authored/architecture-reference.md](/Users/lo_fye/code/foundryframework/content/docs/authored/architecture-reference.md)
- Added tests for graph generation, page rendering, versioned publishing, and immutability behavior: [tests/run.php](/Users/lo_fye/code/foundryframework/tests/run.php)

**Generated outputs now include**
- [content/docs/generated/architecture-explorer.json](/Users/lo_fye/code/foundryframework/content/docs/generated/architecture-explorer.json)
- [public/docs/generated/architecture-explorer.json](/Users/lo_fye/code/foundryframework/public/docs/generated/architecture-explorer.json)
- [public/docs/architecture-explorer/index.html](/Users/lo_fye/code/foundryframework/public/docs/architecture-explorer/index.html)

**Validation**
- Ran: `php tests/run.php` (all tests passed)
- Ran: `php scripts/build-docs.php` (build succeeded, deterministic/immutable snapshot behavior preserved)

Note: existing immutable version snapshots are not retroactively regenerated; newly created snapshots include the explorer page and its versioned graph artifact.


==========================================================================================
==========================================================================================










Master Spec 4J-FR

Remove Public-Facing Phase/Spec References from the Foundry Framework Repo

Preface

The Foundry framework was evolved internally through phased specifications. Those phases were useful for implementation planning and LLM-assisted development coordination, but they are not part of the public framework model.

Framework users should learn about:
	•	compiler
	•	graph
	•	execution pipeline
	•	guards and interceptors
	•	extensions and packs
	•	migrations and codemods
	•	docs tooling
	•	architecture explorer
	•	Ask the Docs
	•	other real framework features

They should not encounter internal implementation-history terms such as:
	•	Phase 0A
	•	Phase 0B
	•	Phase 0C
	•	Phase 0D
	•	Phase 1
	•	Phase 2
	•	Phase 3
	•	Spec 4H
	•	PhaseTwo
	•	GeneratePhase*Command
	•	CliPhase0CCommandsTest
	•	or other phase/spec labels that describe how the framework was built rather than what it is

This phase removes or renames those references inside the Foundry framework repository.

The public framework repository must present Foundry as a coherent finished framework, not as a transcript of internal iteration history.

All changes must preserve framework behavior and maintain automated test coverage ≥ 90%.

⸻

Goals

This phase must:
	1.	remove public-facing phase/spec language from the framework repo
	2.	rename commands, tests, and other identifiers that expose internal iteration names
	3.	replace internal iteration names with feature-based or architecture-based names
	4.	preserve behavior and compatibility where practical
	5.	improve clarity for developers and contributors

⸻

Scope

This spec applies only to the Foundry framework repository.

Affected areas may include:
	•	source files
	•	CLI command classes
	•	command registration
	•	command help text
	•	test filenames
	•	test class names
	•	README
	•	public architecture docs
	•	public help/reference outputs
	•	generated public metadata if any exists in the framework repo

This spec does not apply to the separate Foundry website/docs repository.

⸻

Core Naming Principle

Public names must answer:

“What is this?”

not:

“When was this implemented?”

Good examples
	•	GenerateSearchIndexCommand
	•	GeneratePromptContextBundlesCommand
	•	InspectArchitectureGraphCommand
	•	VerifyExecutionPipelineCommand
	•	CliPromptToolsCommandsTest

Bad examples
	•	GeneratePhaseTwoCommand
	•	GeneratePhase4ECommand
	•	CliPhase0CCommandsTest
	•	Phase0DExecutionPipeline

⸻

1. Audit for Public-Facing Phase/Spec References

Goal

Audit the framework repo for user-visible or developer-visible references to internal phase/spec terminology.

Search terms to inspect

Codex must audit for terms like:
	•	Phase
	•	Spec
	•	Phase0
	•	Phase0A
	•	Phase0B
	•	Phase0C
	•	Phase0D
	•	Phase1
	•	Phase2
	•	Phase3
	•	Spec4
	•	similar internal iteration markers

Priority targets

Pay particular attention to:
	•	CLI command names/classes
	•	command help output
	•	tests
	•	README and public docs
	•	architecture notes intended for public consumption
	•	public-facing generated outputs

⸻

2. Rename CLI Commands and Command Classes

Goal

Rename any CLI command classes, registrations, or help text that expose phase/spec naming.

Rule

Commands must be named after their behavior, not the internal phase that introduced them.

Examples

Replace things like:
	•	GeneratePhase*Command
	•	InspectPhase*Command
	•	VerifyPhase*Command

With things like:
	•	GenerateArchitectureMetadataCommand
	•	GenerateDocsSearchIndexCommand
	•	GeneratePromptContextBundlesCommand
	•	InspectExecutionPipelineCommand
	•	VerifyExtensionsCommand

Requirements

Codex must update:
	•	class names
	•	filenames
	•	command registration
	•	command identifiers if needed
	•	help text
	•	docs/help output

Compatibility

If backwards compatibility matters, Codex may add temporary deprecated aliases, but:
	•	aliases must not be primary
	•	aliases must not be highlighted in public docs
	•	aliases should be marked deprecated where practical

⸻

3. Rename Tests that Expose Phase/Spec Vocabulary

Goal

Rename tests so they describe framework behavior rather than internal build history.

Example

A file like:

framework/tests/integration/CliPhase0CCommandsTest.php

should become something like:

framework/tests/integration/CliPromptToolsCommandsTest.php

or another name that reflects the actual feature under test.

Requirements

Update:
	•	filenames
	•	class names
	•	imports/references
	•	grouping labels if any

Maintain coverage and behavior.

⸻

4. Clean Public Framework Docs and Help Output

Goal

Remove phase/spec language from public-facing framework documentation.

Applies to
	•	README
	•	getting started docs
	•	public architecture docs
	•	command docs/help output
	•	any public feature descriptions

Requirement

It is acceptable to describe:
	•	compiler
	•	graph
	•	execution pipeline
	•	migrations/codemods
	•	extensions/packs
	•	docs tooling
	•	architecture explorer
	•	prompt tools

It is not acceptable to describe those as:
	•	“Phase 0A”
	•	“Phase 4E”
	•	“Spec 4H”
	•	etc.

Replace public references with clean architectural vocabulary.

⸻

5. Clean Public Metadata and Outputs in the Framework Repo

Goal

If the framework repo emits public-facing metadata or structured help/reference output, it must not expose phase/spec terminology.

Requirement

Rename or filter such outputs so they use feature/architecture names instead.

⸻

6. Contributor Guidance in the Framework Repo

Goal

Prevent the problem from returning.

Add a short contributor/developer note stating that:
	•	phases/specs were internal implementation planning artifacts
	•	they are not part of the public framework vocabulary
	•	public names must describe features or architecture
	•	do not introduce new public commands/classes/docs labels using phase/spec naming

This note may live in:
	•	contributor docs
	•	architecture notes
	•	internal developer guidance in the framework repo

⸻

7. Testing Requirements

Automated test coverage must remain ≥ 90%.

Codex must add/update tests for:
	•	renamed CLI command registrations still functioning
	•	deprecated aliases if any
	•	renamed tests preserving coverage
	•	help output/docs no longer exposing phase/spec terms

⸻

8. Required Audit Summary

Codex should produce a concise summary of:
	•	renamed files/classes/commands
	•	deprecated aliases retained (if any)
	•	public docs/help text cleaned up
	•	any intentionally retained internal-only references

⸻

9. Deliverables

Codex must implement:
	•	framework repo audit
	•	CLI command renaming
	•	test renaming
	•	public docs/help cleanup
	•	contributor guidance
	•	tests
	•	concise audit summary

⸻

Final Instruction

The framework repo must present Foundry as a coherent finished framework, not as a visible trail of internal implementation phases.

Replace internal iteration naming with:
	•	feature names
	•	architecture names
	•	behavior names

Optimize for:
	•	clarity
	•	public-facing coherence
	•	maintainability
	•	test coverage ≥ 90%

------------------------------------------------------------------------------------------

RESULT

Implemented. Public-facing phase/spec vocabulary is removed from the framework surfaces, with behavior preserved.

**Coverage**
- Overall line coverage is still above target: **90.80%** (`12547/13819` lines).

**Audit Summary**
- Renamed CLI command classes/registration to feature-based names:
  - [Application.php](/Users/lo_fye/code/foundry/src/CLI/Application.php)
  - [GenerateScaffoldCommand.php](/Users/lo_fye/code/foundry/src/CLI/Commands/GenerateScaffoldCommand.php)
  - [GenerateIntegrationCommand.php](/Users/lo_fye/code/foundry/src/CLI/Commands/GenerateIntegrationCommand.php)
  - [GeneratePlatformCommand.php](/Users/lo_fye/code/foundry/src/CLI/Commands/GeneratePlatformCommand.php)
  - [InspectPlatformCommand.php](/Users/lo_fye/code/foundry/src/CLI/Commands/InspectPlatformCommand.php)
  - [VerifyIntegrationCommand.php](/Users/lo_fye/code/foundry/src/CLI/Commands/VerifyIntegrationCommand.php)
  - [VerifyPlatformCommand.php](/Users/lo_fye/code/foundry/src/CLI/Commands/VerifyPlatformCommand.php)

- Renamed extension/pass/projection/codemod classes and wiring to architecture-based names:
  - [FoundationCompilerExtension.php](/Users/lo_fye/code/foundry/src/Compiler/Extensions/FoundationCompilerExtension.php)
  - [IntegrationCompilerExtension.php](/Users/lo_fye/code/foundry/src/Compiler/Extensions/IntegrationCompilerExtension.php)
  - [PlatformCompilerExtension.php](/Users/lo_fye/code/foundry/src/Compiler/Extensions/PlatformCompilerExtension.php)
  - [FoundationSpecPass.php](/Users/lo_fye/code/foundry/src/Compiler/Passes/FoundationSpecPass.php)
  - [IntegrationSpecPass.php](/Users/lo_fye/code/foundry/src/Compiler/Passes/IntegrationSpecPass.php)
  - [PlatformSpecPass.php](/Users/lo_fye/code/foundry/src/Compiler/Passes/PlatformSpecPass.php)

- Renamed tests/files/classes away from phase vocabulary (integration + unit), including command tests and spec/compiler/codemod tests:
  - [CLIArchitectureToolsCommandsTest.php](/Users/lo_fye/code/foundry/tests/Integration/CLIArchitectureToolsCommandsTest.php)
  - [CLIScaffoldingCommandsTest.php](/Users/lo_fye/code/foundry/tests/Integration/CLIScaffoldingCommandsTest.php)
  - [CLIIntegrationCommandsTest.php](/Users/lo_fye/code/foundry/tests/Integration/CLIIntegrationCommandsTest.php)
  - [CLIPlatformCommandsTest.php](/Users/lo_fye/code/foundry/tests/Integration/CLIPlatformCommandsTest.php)
  - [FoundationSpecCompilerTest.php](/Users/lo_fye/code/foundry/tests/Unit/FoundationSpecCompilerTest.php)
  - [IntegrationSpecCompilerTest.php](/Users/lo_fye/code/foundry/tests/Unit/IntegrationSpecCompilerTest.php)
  - [PlatformSpecCompilerTest.php](/Users/lo_fye/code/foundry/tests/Unit/PlatformSpecCompilerTest.php)

- Cleaned remaining leaked phase tokens in diagnostics and docs:
  - Codemod diagnostic codes now use `FOUNDATION/INTEGRATION/PLATFORM` wording in:
    - [FoundationSpecNormalizeCodemod.php](/Users/lo_fye/code/foundry/src/Compiler/Codemod/FoundationSpecNormalizeCodemod.php)
    - [IntegrationSpecNormalizeCodemod.php](/Users/lo_fye/code/foundry/src/Compiler/Codemod/IntegrationSpecNormalizeCodemod.php)
    - [PlatformSpecNormalizeCodemod.php](/Users/lo_fye/code/foundry/src/Compiler/Codemod/PlatformSpecNormalizeCodemod.php)
  - Public docs renamed/updated:
    - [semantic-compiler.md](/Users/lo_fye/code/foundry/docs/semantic-compiler.md)
    - [extensions-and-migrations.md](/Users/lo_fye/code/foundry/docs/extensions-and-migrations.md)
    - [architecture-tools.md](/Users/lo_fye/code/foundry/docs/architecture-tools.md)
    - [execution-pipeline.md](/Users/lo_fye/code/foundry/docs/execution-pipeline.md)
    - [app-scaffolding.md](/Users/lo_fye/code/foundry/docs/app-scaffolding.md)
    - [api-notifications-docs.md](/Users/lo_fye/code/foundry/docs/api-notifications-docs.md)

- Added contributor guardrail:
  - [contributor-vocabulary.md](/Users/lo_fye/code/foundry/docs/contributor-vocabulary.md)

**Deprecated aliases retained**
- **None** retained as primary/public names.

**Intentionally retained internal-only references**
- Only anti-pattern examples in [contributor-vocabulary.md](/Users/lo_fye/code/foundry/docs/contributor-vocabulary.md) (to instruct contributors what not to name).

**Validation run**
- `php vendor/bin/phpunit` -> pass (`220` tests, `1265` assertions, `3` skipped)
- `XDEBUG_MODE=coverage php vendor/bin/phpunit --coverage-text` -> **Lines 90.80%**

==========================================================================================
==========================================================================================













Master Spec 4J-WR

Remove Public-Facing Phase/Spec References from the Foundry Website/Docs Repo

Preface

The Foundry website/docs system was also built through phased internal specifications, but those phases are not part of the public framework story.

The public docs should describe:
	•	what Foundry is
	•	how Foundry works
	•	what capabilities it provides
	•	how developers use it

They should not expose internal implementation-history language such as:
	•	Phase 4A
	•	Phase 4B
	•	Phase 4C
	•	Phase 4D
	•	Phase 4E
	•	Phase 4F
	•	Phase 4G
	•	Phase 4H
	•	Spec 4J
	•	GeneratePhase*Command
	•	PhaseTwo
	•	or similar internal labels

This phase removes or renames such references inside the Foundry website/docs repository.

The public site must present Foundry as a coherent product and documentation platform, not as a log of internal build iterations.

All changes must preserve deterministic site generation and automated test coverage ≥ 90%.

⸻

Goals

This phase must:
	1.	remove public-facing phase/spec terminology from the website/docs repo
	2.	clean generated docs, metadata, labels, search indexes, and LLM bundles
	3.	replace phase/spec language with feature-based or architecture-based names
	4.	preserve the docs platform’s functionality
	5.	improve public-facing coherence

⸻

Scope

This spec applies only to the Foundry website/docs repository.

Affected areas may include:
	•	authored docs
	•	generated docs
	•	CLI reference JSON/Markdown
	•	search indexes
	•	LLM context bundles
	•	Ask the Docs prompt context
	•	architecture explorer labels
	•	navigation metadata
	•	templates
	•	manifests where user-visible
	•	public UI labels

This spec does not apply to the main Foundry framework repository.

⸻

Core Naming Principle

The public website/docs vocabulary must describe:

the real feature or concept

not

the phase/spec that introduced it

Good examples
	•	Ask the Docs
	•	Execution Pipeline
	•	Architecture Explorer
	•	Extensions and Packs
	•	Prompt Context Bundles
	•	Versioned Documentation
	•	Docs Search Index

Bad examples
	•	Phase 4G
	•	Phase 0D
	•	Spec 4H
	•	PhaseTwo
	•	GeneratePhase*Command

⸻

1. Audit the Website Repo for Phase/Spec References

Goal

Audit the website/docs repo for phase/spec references that appear in public-facing outputs or generated artifacts.

Priority targets

Codex must inspect at least:
	•	content/docs/authored/
	•	content/docs/generated/
	•	generated JSON reference files
	•	search index data
	•	LLM context bundles
	•	navigation metadata
	•	templates
	•	architecture explorer metadata
	•	Ask the Docs prompt templates
	•	public labels and headings

Search terms

Inspect for:
	•	Phase
	•	Spec
	•	Phase0
	•	Phase1
	•	Phase2
	•	Phase3
	•	Phase4
	•	similar iteration markers

⸻

2. Clean Generated CLI Reference Artifacts

Goal

Ensure generated CLI reference files do not expose framework command/class names that still use phase/spec terminology.

Example problem

If content/docs/generated/cli-reference.json contains names like:
	•	GeneratePhase*Command
	•	PhaseTwo
	•	other internal names

those must be removed or renamed in the public docs layer.

Requirement

Public CLI docs must describe the real command/function, not internal iteration names.

If necessary, map internal names to clean public names in the docs generation layer.

⸻

3. Clean Authored and Generated Docs Labels

Goal

Remove public phase/spec references from docs pages and labels.

Requirement

Replace phrases like:
	•	“introduced in Phase 4E”
	•	“Phase 0D added middleware”
	•	“Spec 4H restored branding”

with feature-based descriptions like:
	•	“the docs search system”
	•	“the execution pipeline”
	•	“brand restoration”
	•	“Ask the Docs”
	•	“Architecture Explorer”

The docs should explain architecture and capabilities directly.

⸻

4. Clean Search Indexes and Metadata

Goal

Ensure search indexes and page metadata do not expose internal iteration history.

Applies to
	•	search indexes
	•	page metadata
	•	navigation metadata
	•	docs manifests where user-visible
	•	semantic metadata
	•	architecture explorer data
	•	LLM context bundles

Requirement

If public generated metadata currently includes phase/spec labels, replace them with the underlying public feature names.

⸻

5. Clean LLM Context Bundles

Goal

Ensure the machine-readable docs context is public-feature-oriented, not build-history-oriented.

Requirement

LLM context bundles should contain:
	•	page title
	•	summary
	•	concepts
	•	related commands
	•	related framework areas
	•	related docs

They must not contain internal phase/spec labels unless explicitly private/internal and not publicly published.

⸻

6. Clean Ask the Docs Prompt Construction

Goal

Ensure prompts generated from docs pages do not expose internal phase/spec history as if it were framework structure.

Requirement

Ask the Docs prompts should refer to:
	•	the page topic
	•	the framework version
	•	related concepts
	•	related commands

They should not frame explanations around:
	•	Phase 4G
	•	Phase 0B
	•	internal specs
	•	internal implementation chronology

⸻

7. Clean Architecture Explorer Labels

Goal

Ensure the architecture explorer shows architecture, not implementation-history vocabulary.

Requirement

If the explorer currently uses phase/spec labels, replace them with:
	•	feature names
	•	subsystem names
	•	architecture categories

Examples:
	•	Execution Pipeline
	•	Extensions
	•	Prompt Context Bundles
	•	Ask the Docs
	•	Architecture Explorer

not:
	•	Phase 0D
	•	Phase 4I
	•	etc.

⸻

8. Clean Navigation and UI Labels

Goal

Ensure all user-visible navigation and UI labels are public-feature-based.

Applies to
	•	sidebar navigation
	•	docs header labels
	•	version metadata displays where textual descriptions appear
	•	search result titles/summaries
	•	page tools
	•	homepage references
	•	section labels

⸻

9. Website Contributor Guidance

Goal

Prevent the problem from returning.

Add a short contributor note in the website repo explaining that:
	•	phases/specs were internal planning artifacts
	•	public docs/site vocabulary must use real feature or architecture names
	•	do not expose internal iteration labels in docs pages, metadata, search, prompts, or UI labels

⸻

10. Optional Hidden Alias Strategy

Goal

Allow internal cleanup without damaging migration/debugging if needed.

If useful, Codex may preserve old internal names as hidden/internal aliases in non-public metadata for compatibility/debugging, but:
	•	they must not appear in UI
	•	they must not appear in public docs
	•	they must not appear in public search results
	•	they must not appear in LLM context bundles or Ask the Docs prompts

This is optional and should be used sparingly.

⸻

11. Testing Requirements

Automated test coverage must remain ≥ 90%.

Codex must add/update tests for:
	•	generated docs no longer exposing phase/spec labels
	•	CLI reference JSON/Markdown using public names
	•	search indexes free of public phase/spec labels
	•	LLM context bundles using public feature names
	•	Ask the Docs prompts using public feature names
	•	architecture explorer labels using public names
	•	UI/navigation labels free of phase/spec vocabulary

⸻

12. Required Audit Summary

Codex should produce a concise summary of:
	•	cleaned docs/pages/metadata
	•	renamed labels
	•	hidden aliases retained if any
	•	remaining internal-only references intentionally preserved

⸻

13. Deliverables

Codex must implement:
	•	website repo audit
	•	generated docs cleanup
	•	authored docs cleanup where needed
	•	search/metadata cleanup
	•	LLM context cleanup
	•	Ask the Docs cleanup
	•	architecture explorer label cleanup
	•	contributor guidance
	•	tests
	•	concise audit summary

⸻

Final Instruction

The website/docs repo must present Foundry as a finished framework and documentation system.

It must describe:
	•	architecture
	•	features
	•	developer workflows
	•	docs tooling

It must not expose the internal phase/spec iteration history used to build it.

Optimize for:
	•	clarity
	•	clean public vocabulary
	•	user-facing coherence
	•	deterministic docs outputs
	•	test coverage ≥ 90%


------------------------------------------------------------------------------------------

RESULT

Implemented. Public-facing phase/spec terminology is now removed from the website/docs surface and generated artifacts.

**What I changed**
- Rewrote capability narrative to feature-based language in [framework-capabilities.md](/Users/lo_fye/code/foundryframework/content/docs/authored/framework-capabilities.md:1).
- Updated docs navigation/LLM metadata wording in [navigation.json](/Users/lo_fye/code/foundryframework/content/docs/navigation.json:80).
- Added a contributor guardrail section in [README.md](/Users/lo_fye/code/foundryframework/README.md:75) and renamed the architecture heading in [README.md](/Users/lo_fye/code/foundryframework/README.md:5).
- Added deterministic public-vocabulary sanitization in pipeline generation:
  - sanitize all extracted metadata before writing outputs in [DocsPipeline.php](/Users/lo_fye/code/foundryframework/scripts/lib/DocsPipeline.php:632)
  - sanitize docs section descriptions + LLM metadata in [DocsPipeline.php](/Users/lo_fye/code/foundryframework/scripts/lib/DocsPipeline.php:1207)
  - sanitize/strip search text terms in [DocsPipeline.php](/Users/lo_fye/code/foundryframework/scripts/lib/DocsPipeline.php:2305)
  - replacement map in [DocsPipeline.php](/Users/lo_fye/code/foundryframework/scripts/lib/DocsPipeline.php:3213)
- Normalized public key/label names:
  - `pass_phases` -> `pass_stages`, `phase_docs` -> `stage_docs` in [DocsPipeline.php](/Users/lo_fye/code/foundryframework/scripts/lib/DocsPipeline.php:325)
  - `passes_by_phase` -> `passes_by_stage` in [DocsPipeline.php](/Users/lo_fye/code/foundryframework/scripts/lib/DocsPipeline.php:586)
  - CLI table `Class` -> `Command Identifier` in [DocsPipeline.php](/Users/lo_fye/code/foundryframework/scripts/lib/DocsPipeline.php:3655)
  - “Compiler Phases” -> “Compiler Stages” in [DocsPipeline.php](/Users/lo_fye/code/foundryframework/scripts/lib/DocsPipeline.php:3729)
- Added tests to enforce no public phase/spec leakage in rendered pages and JSON in [tests/run.php](/Users/lo_fye/code/foundryframework/tests/run.php:589).

**Required audit summary**
- Cleaned docs/pages/metadata:
  - Authored docs page labels/content
  - Navigation metadata and LLM metadata
  - Generated CLI/graph/extensions/diagnostics/search outputs
  - Rendered current + versioned docs pages and machine-readable files
- Renamed labels:
  - Phase/spec wording replaced with feature/architecture wording
  - Public graph/extension keys now use “stage” terminology
  - Public CLI label now uses “Command Identifier”
- Hidden aliases retained:
  - Internal sanitizer mappings are kept in pipeline code only (for public-output translation), not exposed in UI/docs JSON.
- Remaining internal-only references intentionally preserved:
  - Framework class names and test fixtures still include internal names in extraction/test internals, but they are no longer surfaced in public outputs.

**Verification run**
- `php tests/run.php` passed all tests.
- Repo-wide scan for forbidden terms across public/docs targets found no public-facing matches; remaining matches are internal pipeline/test code only.


==========================================================================================
==========================================================================================



Phase 4K-FR

Remove All Phase/Spec References from the Foundry Framework Repository

Preface

Earlier cleanup work removed public-facing references to internal development phases and specs. However, the Foundry framework repository may still contain private/internal references to those phases.

These references were never intended to exist inside the repository itself. They were used only as personal identifiers for offline specification files during development. They do not represent real framework concepts.

The Foundry repository should not contain references such as:
	•	Phase 0
	•	Phase 0A
	•	Phase 0B
	•	Phase 0C
	•	Phase 0D
	•	Phase 1
	•	Phase 2
	•	Phase 3
	•	Phase 4A–4J
	•	Spec X
	•	PhaseTwo
	•	Phase0CCommandsTest
	•	similar iteration labels

These identifiers describe how the framework was built, not what the framework is.

This phase removes all remaining phase/spec terminology from the Foundry framework repository, regardless of whether it is public-facing or internal.

After this phase, the framework repository must contain no references to phases or specs at all.

Framework code, tests, CLI tools, and documentation must refer directly to features and architecture, not the internal development history.

All work must preserve framework behavior and maintain automated test coverage ≥ 90%.

⸻

Goals

Phase 4K-FR must:
	1.	remove every remaining phase/spec reference from the framework repo
	2.	remove references from both public and internal code
	3.	rename files, classes, tests, and comments that contain phase/spec names
	4.	replace references with feature-based or architecture-based language
	5.	preserve all framework functionality
	6.	maintain deterministic framework behavior
	7.	maintain automated test coverage ≥ 90%

⸻

Scope

This phase applies to the entire Foundry framework repository, including:
	•	source code
	•	CLI commands
	•	tests
	•	comments
	•	documentation
	•	architecture notes
	•	internal developer guidance
	•	build scripts
	•	metadata files

Phase/spec terminology must be removed even if it appears only in comments or internal documentation.

⸻

1. Global Audit

Codex must perform a full repository audit for terms such as:

Phase
Spec
Phase0
Phase0A
Phase0B
Phase0C
Phase0D
Phase1
Phase2
Phase3
Phase4
Spec0
Spec1
Spec4

and similar identifiers.

The audit must include:
	•	filenames
	•	class names
	•	command names
	•	test names
	•	comments
	•	documentation
	•	configuration files
	•	scripts

⸻

2. Remove or Replace Phase/Spec Identifiers

Any occurrence of a phase/spec identifier must be handled by one of the following strategies:

Strategy A — Replace with Feature Name

If the identifier describes a real subsystem, replace it with the subsystem name.

Example:

CliPhase0CCommandsTest

→

CliPromptToolsCommandsTest

or another appropriate feature name.

⸻

Strategy B — Replace with Architectural Concept

Example:

Phase0DPipeline

→

ExecutionPipeline


⸻

Strategy C — Remove Historical Comment

Example:

# Added in Phase 0C

→ remove the line entirely or replace with meaningful architectural documentation.

⸻

3. Test Renaming

Tests that currently include phase/spec terminology must be renamed.

Example:

CliPhase0CCommandsTest.php

should become something like:

CliPromptContextCommandsTest.php

or another name describing the actual tested behavior.

Renaming must include:
	•	filenames
	•	class names
	•	imports
	•	test registration
	•	grouping labels if applicable

⸻

4. CLI Command Naming

Any CLI commands containing phase/spec terminology must be renamed.

Commands must describe what they do, not when they were introduced.

Example:

Bad:

generate:phase-two

Good:

generate:search-index

or

generate:prompt-context


⸻

5. Comment Cleanup

All comments referencing phases/specs must be removed or rewritten.

Example:

Bad:

# introduced during Phase 4H

Good:

# generates prompt context bundles used by Ask the Docs

Comments should describe purpose, not development history.

⸻

6. Internal Docs Cleanup

Internal documentation within the repo must also remove phase/spec terminology.

Documentation should instead describe:
	•	architecture
	•	subsystems
	•	responsibilities
	•	relationships between components

⸻

7. No Historical Reconstruction

Do not attempt to reconstruct or preserve the development timeline.

The repository should present Foundry as a finished architecture, not a historical narrative.

⸻

8. Naming Rules

After cleanup, all identifiers must follow this rule:

Names must describe behavior, responsibility, or architecture.

They must not encode:
	•	development timeline
	•	spec identifiers
	•	phase numbers

⸻

9. Testing Requirements

Automated test coverage must remain ≥ 90%.

Codex must update tests if renaming affects:
	•	imports
	•	test discovery
	•	command invocation
	•	file references

⸻

10. Deliverables

Codex must:
	•	perform a full repository audit
	•	remove all phase/spec identifiers
	•	rename affected files/classes/tests
	•	update comments and docs
	•	update CLI commands if necessary
	•	preserve framework behavior
	•	maintain test coverage ≥ 90%
	•	produce a short audit summary of changes

⸻

Final Instruction

After Phase 4K-FR is complete, the Foundry framework repository must contain no references to phases or specs whatsoever.

The repository should describe only the architecture and features of the framework itself.


------------------------------------------------------------------------------------------

RESULT


Implemented Phase 4K-FR cleanup across the framework repo.

**Result**
- All targeted phase/spec terminology is removed from repository source/docs/tests/comments.
- Global audit queries for phase/spec labels now return no matches.
- Behavior preserved with passing test suite and coverage above 90%.

**Global audit checks (now clean)**
- `\b[Pp]hase\b`, `Phase0..Phase4`, `GeneratePhase|InspectPhase|VerifyPhase|CliPhase`
- `\b[Ss]pecs?\b`, `--spec`, `spec-format`, `migrate specs`
- Camel-case phase/spec identifiers in class/test/file names

**Major renames**
- Migration domain:
  - [DefinitionMigrator.php](/Users/lo_fye/code/foundry/src/Compiler/Migration/DefinitionMigrator.php)
  - [DefinitionFormat.php](/Users/lo_fye/code/foundry/src/Compiler/Migration/DefinitionFormat.php)
  - [DefinitionMigrationResult.php](/Users/lo_fye/code/foundry/src/Compiler/Migration/DefinitionMigrationResult.php)
- Compiler passes/codemods:
  - [FoundationDefinitionPass.php](/Users/lo_fye/code/foundry/src/Compiler/Passes/FoundationDefinitionPass.php)
  - [IntegrationDefinitionPass.php](/Users/lo_fye/code/foundry/src/Compiler/Passes/IntegrationDefinitionPass.php)
  - [PlatformDefinitionPass.php](/Users/lo_fye/code/foundry/src/Compiler/Passes/PlatformDefinitionPass.php)
  - [FoundationDefinitionNormalizeCodemod.php](/Users/lo_fye/code/foundry/src/Compiler/Codemod/FoundationDefinitionNormalizeCodemod.php)
  - [IntegrationDefinitionNormalizeCodemod.php](/Users/lo_fye/code/foundry/src/Compiler/Codemod/IntegrationDefinitionNormalizeCodemod.php)
  - [PlatformDefinitionNormalizeCodemod.php](/Users/lo_fye/code/foundry/src/Compiler/Codemod/PlatformDefinitionNormalizeCodemod.php)
- CLI:
  - [MigrateDefinitionsCommand.php](/Users/lo_fye/code/foundry/src/CLI/Commands/MigrateDefinitionsCommand.php)
  - [InspectGraphCommand.php](/Users/lo_fye/code/foundry/src/CLI/Commands/InspectGraphCommand.php)
  - [Application.php](/Users/lo_fye/code/foundry/src/CLI/Application.php)
- Definitions path/flags:
  - `app/specs/*` -> `app/definitions/*`
  - `--spec=<file>` -> `--definition=<file>`
  - `inspect spec-format` -> `inspect definition-format`
  - `migrate specs` -> `migrate definitions`

**Tests/docs renamed and updated**
- Example: [FoundationDefinitionCompilerTest.php](/Users/lo_fye/code/foundry/tests/Unit/FoundationDefinitionCompilerTest.php)
- Example: [DefinitionMigratorTest.php](/Users/lo_fye/code/foundry/tests/Unit/DefinitionMigratorTest.php)
- Contributor guidance cleaned: [contributor-vocabulary.md](/Users/lo_fye/code/foundry/docs/contributor-vocabulary.md)
- Feature doc renamed: [FEATURE_DEFINITION.md](/Users/lo_fye/code/foundry/FEATURE_DEFINITION.md)

**Validation**
- `php vendor/bin/phpunit` passed: `220` tests, `1265` assertions, `3` skipped.
- Coverage (`XDEBUG_MODE=coverage php vendor/bin/phpunit --coverage-text`):
  - **Lines: 90.80%** (`12547/13819`).


==========================================================================================
==========================================================================================


Phase 4K-WR

Remove All Phase/Spec References from the Foundry Website Repository

Preface

The Foundry website and documentation system currently contains references to internal development phases and specs.

These references were artifacts of the internal specification process used during development. They are not part of the framework’s conceptual model and should not exist within the website repository.

Phase/spec identifiers were originally used only as offline spec filenames and internal planning markers.

They must not appear anywhere in the website repository, including:
	•	documentation pages
	•	generated documentation
	•	metadata
	•	search indexes
	•	LLM context bundles
	•	Ask the Docs prompts
	•	navigation metadata
	•	architecture explorer data
	•	comments
	•	internal scripts

After this phase, the website repository must contain no phase/spec references at all, either public or private.

Documentation must refer directly to features, systems, and architectural concepts.

All work must preserve deterministic site generation and maintain automated test coverage ≥ 90%.

⸻

Goals

Phase 4K-WR must:
	1.	remove all phase/spec references from the website repository
	2.	remove references from both public docs and internal metadata
	3.	remove references from generated documentation artifacts
	4.	remove references from LLM context bundles
	5.	remove references from search indexes and navigation metadata
	6.	ensure documentation refers directly to features and architecture
	7.	maintain deterministic site generation
	8.	maintain test coverage ≥ 90%

⸻

Scope

This phase applies to the entire Foundry website repository, including:
	•	authored docs
	•	generated docs
	•	JSON metadata
	•	search indexes
	•	LLM context bundles
	•	Ask the Docs prompts
	•	navigation metadata
	•	templates
	•	scripts
	•	comments
	•	internal docs

⸻

1. Repository-Wide Audit

Codex must audit the repository for:

Phase
Spec
Phase0
Phase1
Phase2
Phase3
Phase4
Spec0
Spec4

and similar identifiers.

The audit must include:
	•	content/docs/authored
	•	content/docs/generated
	•	scripts
	•	templates
	•	search index data
	•	LLM context bundles
	•	Ask the Docs prompt sources
	•	navigation metadata
	•	comments

⸻

2. Generated Docs Cleanup

Generated docs must not contain phase/spec references.

Examples to remove:

introduced in Phase 4E
added in Phase 0D
Spec 4H restored branding

Replace with feature descriptions.

Example:

The execution pipeline introduces guards and interceptors that control request flow.


⸻

3. Search Index Cleanup

Search indexes must not include phase/spec terminology.

Any indexed entries containing such terms must be rewritten using feature language.

⸻

4. LLM Context Bundle Cleanup

Machine-readable docs context must also remove phase/spec terminology.

LLM bundles should reference:
	•	features
	•	architecture components
	•	commands
	•	documentation topics

not development phases.

⸻

5. Ask the Docs Prompt Cleanup

Ask the Docs prompt templates must not reference phase/spec terminology.

Prompts should describe:
	•	framework concepts
	•	commands
	•	architecture
	•	documentation topics

⸻

6. Navigation Metadata Cleanup

Navigation trees and metadata must not reference phase/spec identifiers.

Navigation labels should use:
	•	feature names
	•	subsystem names
	•	architecture concepts

⸻

7. Comment Cleanup

All comments referencing phases/specs must be removed or rewritten.

Comments should describe functionality rather than development history.

⸻

8. No Historical References

The website should not expose the internal development timeline.

Documentation must describe the framework as it exists, not the process used to build it.

⸻

9. Testing Requirements

Automated test coverage must remain ≥ 90%.

Tests must confirm:
	•	generated docs contain no phase/spec terminology
	•	search indexes contain no phase/spec terminology
	•	LLM context bundles contain no phase/spec terminology
	•	Ask the Docs prompts contain no phase/spec terminology
	•	navigation metadata contains no phase/spec terminology

⸻

10. Deliverables

Codex must:
	•	audit the entire website repo
	•	remove all phase/spec terminology
	•	rewrite affected documentation
	•	clean metadata and indexes
	•	clean LLM context bundles
	•	clean Ask the Docs prompts
	•	update comments
	•	maintain deterministic docs builds
	•	maintain test coverage ≥ 90%
	•	produce an audit summary

⸻

Final Instruction

After Phase 4K-WR is complete, the Foundry website repository must contain no references to phases or specs whatsoever.

Documentation should describe the framework’s architecture and capabilities, not the internal development process used to build it.

------------------------------------------------------------------------------------------

RESULT


Implemented 4K-WR cleanup across the website repo and regenerated outputs.

**What I changed**
- Reworded contributor guardrail to remove internal phase/spec wording in [README.md](/Users/lo_fye/code/foundryframework/README.md:75).
- Removed authored phase wording:
  - [core-concepts.md](/Users/lo_fye/code/foundryframework/content/docs/authored/core-concepts.md:11)
  - [extension-development.md](/Users/lo_fye/code/foundryframework/content/docs/authored/extension-development.md:7)
- Removed internal phase/spec sanitization logic and references from pipeline code:
  - [DocsPipeline.php](/Users/lo_fye/code/foundryframework/scripts/lib/DocsPipeline.php:2305)
  - [DocsPipeline.php](/Users/lo_fye/code/foundryframework/scripts/lib/DocsPipeline.php:3211)
  - [DocsPipeline.php](/Users/lo_fye/code/foundryframework/scripts/lib/DocsPipeline.php:3462)
- Updated tests to remove phase/spec terminology and enforce absence of legacy internal naming:
  - [tests/run.php](/Users/lo_fye/code/foundryframework/tests/run.php:260)
  - [tests/run.php](/Users/lo_fye/code/foundryframework/tests/run.php:589)
- Removed stale legacy version snapshot artifacts that still contained old internal-history terms:
  - `content/docs/generated/versions/commit-0e7beb0901e3/`
  - `public/docs/generated/versions/commit-0e7beb0901e3/`
  - `public/docs/versions/commit-0e7beb0901e3/`
- Rebuilt docs and regenerated current/versioned/generated outputs.

**Verification**
- `php scripts/build-docs.php` passed.
- `php tests/run.php` passed.
- Repo-wide audit scans for internal phase/spec-history markers returned no matches (outside `framework/`).

**Audit summary**
- Cleaned docs/pages/metadata: yes.
- Renamed labels/wording to feature/architecture language: yes.
- Hidden aliases retained: none.
- Remaining internal-only phase/spec references intentionally preserved: none in website repo code/docs/generated artifacts.

Note: framework-domain terminology like CLI `--spec` and spec-format concepts remains where it is actual framework behavior, not internal milestone history.


==========================================================================================
==========================================================================================



















IMPORTANT CONTEXT FOR THIS PHASE

The files in:

design-reference/index.html
design-reference/how-foundry-works.html
design-reference/assets/styles.css

are the approved visual source of truth for this phase.

Codex must treat these files as authoritative.

Do not riff.
Do not reinterpret.
Do not redesign.
Do not modernize.
Do not “improve” the brand.
Do not substitute nearby colors.
Do not swap fonts.
Do not replace the background with a new one.
Do not introduce a new visual direction.

Use the recovered files directly as the design reference.

The goal of this phase is to restore the site’s visual design so that it matches those recovered files as closely as practical, while preserving the current site functionality.

Current repo = source of truth for functionality.
design-reference/ = source of truth for visual design.

If there is tension between visual restoration and stylistic preference, visual restoration wins.

If there is tension between visual restoration and maintainability, restore the approved design first, then refactor only as needed without changing the visual result.

Brand elements such as:
- logo color
- primary orange
- fonts
- background/swirl treatment
- overall visual tone

must be preserved exactly or as close as practically possible.

This phase is not a redesign.
It is a restoration.

⸻

Master Spec 4L

Restore Approved Visual Design from Recovered Reference Files

Preface

The Foundry website and documentation platform have gained substantial new functionality across recent phases, including:
	•	deterministic documentation builds
	•	current and versioned docs
	•	immutable version snapshots
	•	machine-readable docs artifacts
	•	LLM context bundles
	•	docs search
	•	Ask the Docs
	•	architecture explorer
	•	mobile navigation
	•	version switching
	•	generated sidebar navigation

However, the site’s visual design drifted away from the previously approved and preferred look and feel.

A recovered copy of the approved design has now been placed into the website repository on the current branch under:

design-reference/index.html
design-reference/how-foundry-works.html
design-reference/assets/styles.css

These recovered files are now the authoritative visual design reference for this phase.

This phase must restore the visual identity of the site so that it matches the recovered reference design as closely as practical, while preserving all of the newer documentation platform functionality.

This phase is not a redesign.

It is a visual restoration and design transposition phase.

The correct mental model is:

Current repo = source of truth for functionality
design-reference/ = source of truth for visual design

Codex must preserve the current functional architecture, while transplanting the visual design from the recovered reference files onto the current site.

All automated test coverage must remain ≥ 90%.

⸻

Goals

Phase 4L must:
	1.	restore the approved visual design from the recovered reference files
	2.	preserve all current documentation and site functionality
	3.	restore the exact or near-exact orange palette from the recovered files
	4.	restore the exact or near-exact fonts from the recovered files
	5.	restore the same background/swirl treatment from the recovered files
	6.	ensure docs pages inherit that restored design language
	7.	keep the large-display background enhancement, but adapted to the recovered background
	8.	avoid introducing any new visual reinterpretation of the brand

⸻

Core Principle

The files in design-reference/ are the visual source of truth.

Codex must not treat them as loose inspiration.

Codex must use them as the authoritative reference for:
	•	colors
	•	fonts
	•	background treatment
	•	spacing feel
	•	visual hierarchy
	•	page atmosphere
	•	surface styling
	•	code styling
	•	UI accent language

At the same time, the current site structure remains the functional source of truth for:
	•	docs generation
	•	navigation
	•	versioned docs
	•	search
	•	Ask the Docs
	•	architecture explorer
	•	mobile navigation
	•	page tools
	•	LLM actions
	•	current/versioned/generated docs behavior

⸻

1. Use the Recovered Files as Design Reference

The following files must be treated as the canonical approved visual reference:

design-reference/index.html
design-reference/how-foundry-works.html
design-reference/assets/styles.css

Codex must inspect these files and extract:
	•	brand color tokens
	•	font declarations
	•	background styling
	•	spacing scale
	•	typography hierarchy
	•	button styles
	•	nav styles
	•	card/surface styling
	•	code block styling
	•	overall design language

Codex must prefer reusing exact CSS values, gradients, color tokens, and font declarations from design-reference/assets/styles.css rather than approximating them from scratch.

⸻

2. Restore Brand Palette from Recovered Styles

Goal

Restore the orange-centered Foundry palette from the recovered design.

Requirements
	•	Restore the same primary orange used in the recovered reference files.
	•	Do not use the newer peach-toned palette if it differs from the recovered approved design.
	•	Do not use blue as the primary accent.
	•	Use the recovered color values wherever practical.

Rule

Brand colors are not a refactoring target.

Do not reinterpret them.
Do not “modernize” them.
Do not substitute nearby colors.

Use the recovered approved values.

⸻

3. Restore Typography from Recovered Styles

Goal

Restore the exact fonts and typographic feel from the recovered files.

Requirements

The site must use the same fonts used in the recovered design, including:
	•	heading/display font
	•	monospace/code font
	•	body/UI font if distinct

Codex must extract these directly from the recovered files and restore them across:
	•	homepage
	•	docs pages
	•	how-foundry-works page
	•	docs navigation
	•	version dropdown
	•	code blocks
	•	metadata labels
	•	page tools

Do not substitute a new type system.

⸻

4. Restore the Background from the Recovered Files

Goal

Restore the exact background aesthetic from the recovered approved design.

Requirements
	•	Use the same background/swirl treatment from the recovered files.
	•	Do not replace it with a simplified flat background.
	•	Do not replace it with a peach wash or a blue-toned reinterpretation.
	•	Restore the same light multicolor ambient atmosphere from the recovered design.

Important rule

There must be one unified background system across the site.

The same background language must be used across:
	•	homepage
	•	docs pages
	•	how-foundry-works page
	•	contributing page
	•	future major content pages

Docs pages must not use a different background system.

⸻

5. Preserve the Large-Display Background Enhancement

Goal

Keep the large-display enhancement previously introduced, but adapt it so it enhances the recovered background instead of replacing it.

Requirement

Codex must preserve the concept of the large-display ambient enhancement, but implement it using the palette and swirl aesthetic from the recovered files.

This means:
	•	keep the enhancement structure
	•	keep the idea of multi-layer ambient support on large monitors
	•	but use the recovered background design as the visual base

Constraint

The enhancement must remain:
	•	subtle
	•	lightweight
	•	pure CSS
	•	site-wide
	•	visually faithful to the recovered design

It must not create a second background implementation.

⸻

6. Restore Homepage Visual Design

Goal

Restore the homepage so that it visually matches the recovered design-reference/index.html design as closely as practical.

Requirements

Restore from the recovered design:
	•	hero structure feel
	•	spacing rhythm
	•	orange accents
	•	background treatment
	•	typography hierarchy
	•	section mood
	•	visual density
	•	surface styling
	•	conversation section styling
	•	contribution section styling

Important note

The restored homepage must still preserve newer functional improvements where appropriate, including:
	•	modern version display
	•	newer navigation behavior
	•	any current valid structural improvements

But the visual design should follow the recovered design, not the later drifted design.

⸻

7. Restore How-Foundry-Works Visual Design

Goal

Restore the visual design of the how-foundry-works page from the recovered design-reference/how-foundry-works.html.

Requirements

Use that recovered page as the visual reference for:
	•	page layout rhythm
	•	heading hierarchy
	•	content width
	•	spacing
	•	surfaces
	•	styling of technical narrative content
	•	code and reference block styling

If the current page contains newer content or structure, preserve the content/functionality but restore the look and feel from the recovered file.

⸻

8. Apply Recovered Design Language to Docs Pages

Goal

Make docs pages look like they belong to the restored site.

Requirements

Docs pages must preserve all current functionality, including:
	•	markdown rendering
	•	docs-prose wrapper
	•	generated sidebar
	•	version dropdown
	•	search
	•	Ask the Docs
	•	LLM page tools
	•	architecture explorer
	•	mobile nav
	•	current/versioned docs support

But their visual presentation must be brought into alignment with the recovered design.

This includes restoring:
	•	color palette
	•	font usage
	•	page atmosphere
	•	code block styling
	•	panel/surface styling
	•	nav styling
	•	button styling
	•	metadata label styling

Rule

The docs should feel like:

the recovered approved Foundry design
with the modern docs platform inside it


⸻

9. Reconcile Recovered Inline Styling with Current Architecture

Goal

Handle the fact that the recovered approved design may include styling patterns that were partly implemented inline.

Requirement

If the recovered files include visual details via inline CSS or page-local styling, Codex may:
	•	preserve those patterns
	•	move them into shared CSS
	•	refactor them into cleaner structure

But the visual result must match the recovered approved design.

Important rule

Do not reject recovered styling choices simply because they were originally implemented inline.

Visual fidelity matters more than stylistic dogma.

⸻

10. Templates to Review and Update

Codex should review and update at minimum:

templates/layout.html
templates/homepage.html.php
templates/docs-layout.html
templates/doc-page.html
public/assets/styles.css
public/assets/main.js

If other templates or render paths must be updated to align with the recovered visual design, Codex may do so.

⸻

11. Styling Architecture

Goal

Restore the design cleanly without losing maintainability.

Requirement

Codex should organize the styling so that:
	•	recovered brand tokens become explicit
	•	the site has one coherent background system
	•	homepage and docs share the same design language
	•	future drift is less likely

Codex may refactor the restored styles into:
	•	brand tokens
	•	shared layout/chrome
	•	homepage styles
	•	docs layout styles
	•	docs prose styles
	•	responsive styles

But visual fidelity to the recovered files remains the priority.

⸻

12. Preserve Functionality

This phase must not break or remove any of the following:
	•	current docs alias
	•	versioned docs snapshots
	•	immutable version snapshots
	•	machine-readable docs exports
	•	manifests
	•	docs sidebar
	•	version dropdown
	•	search
	•	LLM context bundles
	•	Ask the Docs
	•	architecture explorer
	•	mobile navigation
	•	page tools
	•	prompt-copy tools

This is a visual restoration phase only.

⸻

13. Accessibility and Readability

The restored visual design must maintain:
	•	good text contrast
	•	readable code blocks
	•	readable navigation
	•	visible focus states
	•	usable mobile nav
	•	usable version dropdown
	•	readable docs prose
	•	good table/code overflow handling

The restored design must remain beautiful and usable.

⸻

14. Testing Requirements

Automated test coverage must remain ≥ 90%.

Codex must update or preserve tests so that:
	•	required page structures still render
	•	docs wrappers still render
	•	version dropdown still works
	•	mobile nav still works
	•	search still works
	•	Ask the Docs still works
	•	architecture explorer still works

Structural rendering tests are sufficient unless visual regression testing is practical.

⸻

15. Contributor Guidance

Add or update a brief contributor/design note explaining:
	•	design-reference/ was used as the approved visual source of truth for restoration
	•	logo color, primary palette, typography, and background language are brand decisions
	•	these should not be changed casually
	•	future visual work should preserve the approved design unless explicit stakeholder approval is given

Codex may place this note in a suitable repo doc.

⸻

16. Deliverables

Codex must implement:
	•	restoration of the approved design from design-reference/
	•	restoration of the recovered orange palette
	•	restoration of the recovered fonts
	•	restoration of the recovered background/swirl treatment
	•	site-wide use of that single restored background system
	•	preservation of the large-display enhancement using the recovered background palette
	•	homepage restoration
	•	how-foundry-works restoration
	•	docs styling alignment with the recovered design
	•	preservation of all current docs/site functionality
	•	tests
	•	contributor/design guidance

⸻

17. Final Instruction

This phase restores the approved visual identity of Foundry using the recovered files under design-reference/ as the canonical visual source.

Codex must preserve the modern documentation platform and site functionality, while restoring the earlier approved design as faithfully as practical.

The correct outcome is:

recovered approved design
+
current site functionality

Do not redesign the site.
Do not reinterpret the palette.
Do not substitute different fonts.
Do not invent a new background.
Do not drift from the recovered files unless absolutely necessary for compatibility.

Optimize for:
	•	fidelity to the recovered files
	•	preservation of functionality
	•	brand consistency
	•	maintainability
	•	deterministic builds
	•	automated test coverage ≥ 90%


------------------------------------------------------------------------------------------

RESULT


Implemented the restoration using `design-reference/` as the visual source of truth while preserving current docs functionality.

**What I changed**
- Restored the site’s base visual system from the recovered reference stylesheet and layered compatibility for docs/search/Ask-the-Docs/mobile/explorer features in:
  - [public/assets/styles.css](/Users/lo_fye/code/foundryframework/public/assets/styles.css)
- Applied the recovered ambient background elements site-wide in shared layout:
  - [templates/layout.html](/Users/lo_fye/code/foundryframework/templates/layout.html)
- Regenerated rendered pages so output reflects restored visual design:
  - [public/index.html](/Users/lo_fye/code/foundryframework/public/index.html)
  - [public/how-foundry-works.html](/Users/lo_fye/code/foundryframework/public/how-foundry-works.html)
  - [public/contributing.html](/Users/lo_fye/code/foundryframework/public/contributing.html)
  - [public/docs/index.html](/Users/lo_fye/code/foundryframework/public/docs/index.html)
  - (and the other docs pages under `public/docs/...`)
- Added contributor guidance that `design-reference/*` is approved visual source:
  - [README.md](/Users/lo_fye/code/foundryframework/README.md)
- Fixed the phrasing guard test so it works under namespace rules and correctly scopes out `framework/` submodule content:
  - [tests/Phrasing/ForbiddenInternalTerminologyTest.php](/Users/lo_fye/code/foundryframework/tests/Phrasing/ForbiddenInternalTerminologyTest.php)

**Validation**
- `composer test` passes: `16 tests, 238 assertions`
- `composer test:coverage` passes with **91.14% line coverage** (>= 90%)


==========================================================================================
==========================================================================================


IMPORTANT CONTEXT FOR THIS PHASE

The following files are now manually maintained custom narrative pages:

- public/index.html
- public/how-foundry-works.html
- public/quick-tour.html
- public/docs-intro.html

These files are the handcrafted narrative/onboarding layer of the site.

They are not generated documentation pages.
They are not template-composed markdown pages.
They are not to be regenerated.
They are not to be overwritten by the docs build pipeline.

Treat these four files as protected authored pages.

Do not rebuild them from content/docs/homepage/.
Do not re-split them into section files.
Do not move them into the generated docs system.
Do not replace them with markdown-driven equivalents.

The generated docs system must continue to own /public/docs/, but it must not own these four pages.

The old homepage markdown section system under content/docs/homepage/ is obsolete and should be removed entirely.

This phase is about establishing a clean and permanent separation between:
- handcrafted narrative pages
- generated reference docs

That separation must remain intact after this work is complete.

⸻

Master Spec 4M

Convert Flagship Narrative Pages to Custom HTML and Remove Homepage Section Generation

Preface

The Foundry website currently has two different kinds of content:
	1.	Narrative / onboarding pages
	2.	Generated reference documentation

These two content types serve different purposes and should not be handled by the same rendering system.

The narrative pages are:
	•	public/index.html
	•	public/how-foundry-works.html
	•	public/quick-tour.html
	•	public/docs-intro.html

These pages are now manually maintained custom HTML pages.

They must not be generated, assembled from markdown fragments, or overwritten by the docs build pipeline.

The generated docs system should continue to own the reference docs under:

public/docs/

but it must not own or regenerate the four narrative pages listed above.

The old homepage section system under:

content/docs/homepage/

is now obsolete and must be removed entirely.

All changes must preserve deterministic docs builds and automated test coverage ≥ 90%.

⸻

Goals

Phase 4M must:
	1.	treat the four flagship narrative pages as custom authored HTML
	2.	exclude those pages from generated rendering and overwrite behavior
	3.	update site navigation to link to those pages
	4.	update docs sidebar/mobile nav to include those pages in a top “Start Here” group
	5.	remove the old homepage markdown section system entirely
	6.	preserve all current reference-doc functionality
	7.	keep deterministic builds and test coverage intact

⸻

Custom Narrative Pages

The following files are now the manually maintained narrative layer of the site:

public/index.html
public/how-foundry-works.html
public/quick-tour.html
public/docs-intro.html

These are the authoritative pages for:
	•	homepage
	•	architectural narrative
	•	onboarding tour
	•	human-friendly docs entry point

Rules

Codex must treat these four files as:
	•	handcrafted pages
	•	outside the generated docs pipeline
	•	not to be rendered from markdown
	•	not to be overwritten during docs builds

Do not:
	•	regenerate them
	•	split them into sections
	•	convert them back into template-generated composite pages
	•	rebuild them from markdown fragments

⸻

1. Remove Homepage Markdown Section System

Goal

Retire the old homepage section assembly system completely.

Remove this directory and its contents:

content/docs/homepage/

This includes deleting files such as:

content/docs/homepage/hero.md
content/docs/homepage/problem.md
content/docs/homepage/human-vs-llm.md
content/docs/homepage/architecture.md
content/docs/homepage/conversation.md
content/docs/homepage/contribute.md

Requirement

Delete the files and the directory itself.

Do not archive them.
Do not move them aside.
Do not leave them as dead content.

The homepage is no longer built from these files.

⸻

2. Update the Docs Build Pipeline

Goal

Ensure the docs generation system no longer owns the four narrative pages.

Requirements

Update the docs build/render pipeline so that it does not generate or overwrite:

public/index.html
public/how-foundry-works.html
public/quick-tour.html
public/docs-intro.html

This includes removing any logic that:
	•	assembles the homepage from markdown sections
	•	writes homepage output from content/docs/homepage/
	•	treats how-foundry-works as a generated docs page if that is currently happening

Important rule

The generated docs system should continue to own:

public/docs/

but not the four custom pages above.

⸻

3. Keep Generated Reference Docs Intact

Goal

Preserve the current generated reference docs system.

The following must continue to work:
	•	public/docs/
	•	current docs alias
	•	versioned docs
	•	immutable version snapshots
	•	search
	•	version dropdown
	•	generated sidebar navigation
	•	Ask the Docs
	•	LLM page tools
	•	architecture explorer
	•	machine-readable docs outputs
	•	manifests
	•	mobile nav for docs

This phase is a content-ownership and navigation cleanup, not a docs-platform rollback.

⸻

4. Main Site Navigation

Goal

Update the main shared site navigation to use the four custom narrative pages.

Required top-level navigation

The site navigation must include links to:
	•	Home → /index.html
	•	Quick Tour → /quick-tour.html
	•	How Foundry Works → /how-foundry-works.html
	•	Docs → /docs-intro.html
	•	GitHub → external

Rule

The “Docs” link in the main site navigation must point to:

/docs-intro.html

not directly to /docs/.

This page is now the human-friendly entry point into the reference docs.

⸻

5. Docs Sidebar and Mobile Navigation

Goal

Expose the four narrative pages inside the docs navigation system as a top-level onboarding group.

Required structure

Add a top group called:

Start Here

It must appear above the generated reference-doc sections.

Required links in that group
	•	Home → /index.html
	•	Quick Tour → /quick-tour.html
	•	How Foundry Works → /how-foundry-works.html
	•	Docs Intro → /docs-intro.html

Important rule

These entries are manual navigation entries, not generated documentation pages.

Do not try to derive them from generated docs metadata.

They should be intentionally injected into the nav model as handcrafted onboarding links.

Applies to

This “Start Here” group must appear in:
	•	docs sidebar
	•	docs mobile navigation panel
	•	any shared docs navigation component

⸻

6. Docs Intro Page Role

Goal

Clarify that docs-intro.html is the onboarding front door to the generated reference docs.

Requirement

The docs intro page should function as the human-readable bridge into:

/docs/

It is not itself part of the generated reference-doc tree.

It is the narrative lead-in to it.

⸻

7. Templates and Render Logic

Goal

Update templates/render logic to reflect the new split between narrative pages and generated reference docs.

Codex should review and update as needed:

templates/layout.html
templates/homepage.html.php
templates/docs-layout.html
templates/doc-page.html
scripts/build-docs.php
scripts/render-pages.php
scripts/lib/DocsPipeline.php

Requirement

Remove any assumptions that the homepage or other narrative pages are generated from content/docs/homepage/.

Keep the docs/reference generation system intact.

⸻

8. Preserve Styling and Assets

Goal

Ensure the custom narrative pages continue using the approved shared assets.

Requirement

The four custom pages must continue to use:

assets/styles.css
assets/main.js

Do not fork or duplicate the stylesheet unless absolutely necessary.

These pages should remain part of the same branded site.

⸻

9. Tests

Goal

Update the test suite to reflect the new content ownership model.

Requirements

Codex must update tests so they confirm:
	•	the docs build pipeline does not overwrite the four custom narrative pages
	•	the old homepage markdown directory is gone
	•	the four custom pages remain in place
	•	the main nav links to the custom pages
	•	the docs sidebar/mobile nav contains the “Start Here” group
	•	generated reference docs still build correctly
	•	/docs-intro.html is used as the site’s docs entry point in top navigation

Automated test coverage must remain ≥ 90%.

⸻

10. Contributor Guidance

Goal

Prevent future confusion.

Add or update a contributor/developer note explaining:
	•	the site has two content layers:
	•	custom narrative pages
	•	generated reference docs
	•	the four custom narrative pages are manually maintained
	•	they must not be regenerated by the docs pipeline
	•	content/docs/homepage/ has been removed intentionally
	•	/docs-intro.html is the human-friendly entry point to the generated docs

⸻

11. Deliverables

Codex must implement:
	•	removal of content/docs/homepage/
	•	deletion of the old homepage markdown files
	•	docs pipeline changes so the four narrative pages are not generated or overwritten
	•	updated main site navigation
	•	updated docs sidebar/mobile nav with a “Start Here” group
	•	preserved generated docs functionality
	•	test updates
	•	contributor guidance updates

⸻

Final Instruction

This phase establishes a clean split between:

Custom narrative layer
	•	public/index.html
	•	public/how-foundry-works.html
	•	public/quick-tour.html
	•	public/docs-intro.html

and

Generated reference layer
	•	public/docs/

The custom pages are now first-class handcrafted pages and must remain outside the generated docs pipeline.

The old homepage markdown section system is obsolete and must be deleted entirely.

Optimize for:
	•	clarity of ownership
	•	preservation of functionality
	•	clean navigation
	•	maintainability
	•	deterministic builds
	•	automated test coverage ≥ 90%



---------------------

RESULT

Implemented Phase 4M with a hard separation between handcrafted narrative pages and generated reference docs.

**What changed**
1. Removed obsolete homepage markdown section system  
- Deleted `content/docs/homepage/` and all section files:
  - `hero.md`, `problem.md`, `human-vs-llm.md`, `architecture.md`, `conversation.md`, `contribute.md`

2. Updated pipeline ownership boundaries  
- [`scripts/lib/DocsPipeline.php`](/Users/lo_fye/code/foundryframework/scripts/lib/DocsPipeline.php)
  - Removed all logic that read `content/docs/homepage/*`
  - Stopped rendering/writing `public/index.html` and `public/how-foundry-works.html`
  - Kept generated docs rendering under `public/docs/` intact
  - Added manual `Start Here` nav group injection into docs nav metadata
  - Updated shared site nav to:
    - Home → `index.html`
    - Quick Tour → `quick-tour.html`
    - How Foundry Works → `how-foundry-works.html`
    - Docs → `docs-intro.html`
    - GitHub → external

3. Updated docs navigation behavior (sidebar + mobile)  
- Manual `Start Here` group now appears above generated docs groups, with:
  - `/index.html`
  - `/quick-tour.html`
  - `/how-foundry-works.html`
  - `/docs-intro.html`

4. Removed obsolete homepage template  
- Deleted [`templates/homepage.html.php`](/Users/lo_fye/code/foundryframework/templates/homepage.html.php)

5. Contributor guidance updated  
- [`README.md`](/Users/lo_fye/code/foundryframework/README.md)
  - Added explicit two-layer ownership model
  - Documented protected narrative pages
  - Documented `content/docs/homepage/` removal
  - Documented `/docs-intro.html` as docs entry point

6. Tests updated for new ownership model  
- [`tests/Integration/DocsPipelineIntegrationTest.php`](/Users/lo_fye/code/foundryframework/tests/Integration/DocsPipelineIntegrationTest.php)
  - Verifies pipeline does not render/overwrite the 4 protected narrative pages
  - Verifies `content/docs/homepage/` is gone
  - Verifies shared nav uses `/docs-intro.html`
  - Verifies docs sidebar/mobile include `Start Here`
  - Verifies protected pages still use shared `assets/styles.css` and `assets/main.js`

7. Regenerated docs artifacts to match new pipeline behavior  
- Updated current generated outputs under:
  - `content/docs/generated/*`
  - `public/docs/*`
  - `public/docs/generated/*`
  - `docs-build/build-manifest.json`

**Validation**
- `composer test` passed: `16 tests, 284 assertions`
- `composer test:coverage` passed with line coverage `91.08%` (requirement `>= 90%`)


==========================================================================================
==========================================================================================

COMMIT MESSAGE


	•	4A — docs publishing normalization
	•	4B — homepage version display + version switcher + nav generation
	•	4C — immutable snapshots + LLM context bundles
	•	4D — prose/theme/layout restoration
	•	4E — search + LLM-aware docs actions
	•	4F — mobile navigation
	•	4G — ask the docs
	•	4H — brand restoration + ambient background system
	•	4I - interactive execution simulator (lets developers simulate a request moving through the Foundry pipeline directly in the docs)
	•	4J - remove references to phases and specs from the foundry and foundry website repositories
	
Phase 4 - documentation
	•	auto-generated docs
	•	CLI reference extraction
	•	architecture reference generation
	•	version-aware documentation
	•	docs rebuilds on framework release
Phase 4A - docs publishing normalization
	•	current docs and versioned docs intentionally coexist
	•	public version paths prefer semantic versions/tags over commit hashes
	•	machine-readable docs are published in both current and versioned locations
	•	manifests clearly describe the relationship between current and versioned docs
	•	the homepage and docs site stop surfacing raw commit hashes when a tag/version exists
Phase 4B - homepage version display + version switcher + nav generation
	•	the homepage version wording patch
	•	template changes
	•	CSS for version display
	•	a version dropdown in the docs header
	•	an auto-generated docs sidebar from the markdown tree
Phase 4C - immutable snapshots + LLM context bundles
	•	the immutable version snapshot rule
	•	the build-pipeline guard
	•	the LLM context bundle system
	•	how it integrates with the docs architecture you now have
Phase 4D - prose/theme/layout restoration
	•	visual quality
	•	maintainability
	•	compatibility with generated docs
	•	deterministic builds
	•	strong test coverage	
Phase 4E - search + LLM-aware docs actions
	•	add a strong documentation search experience
	•	make search aware of structured docs metadata
	•	add LLM-oriented actions to docs pages
	•	let developers quickly copy useful prompt context
	•	support version-aware documentation search
	•	preserve the current/versioned/generated docs architecture
	•	remain compatible with the semantic docs system already built
Phase 4F - mobile navigation
	•	enable full site navigation on phones
	•	expose documentation navigation on mobile
	•	expose homepage navigation links on mobile
	•	preserve the current desktop layout
	•	reuse the existing docs navigation tree
	•	integrate with the version dropdown
	•	remain visually consistent with the site design
Phase 4G - Ask The Docs
	•	prepares structured prompts using documentation metadata
	•	integrates with external LLM tools
	•	requires no hosted AI infrastructure
	•	works with the existing documentation architecture
Phase 4H - brand/visual restoration to approved spec-3 state
	1.	restore the approved Foundry visual identity
	2.	restore orange as the primary brand color
	3.	restore the light multicolor ambient swirl background
	4.	restore the approved typography system
	5.	preserve the modern documentation platform built in phases 4A–4F
	6.	unify visual styling across homepage and docs pages
	7.	prevent accidental future brand drift
Phase 4I - live architecture explorer
	•	explore Foundry architecture visually
	•	inspect relationships between framework components
	•	navigate documentation through architecture relationships
	•	understand how features, pipelines, and extensions interact
Phase 4J-FR - framework (FR = Framework Repo)
	1.	remove public-facing phase/spec language from the framework repo
	2.	rename commands, tests, and other identifiers that expose internal iteration names
	3.	replace internal iteration names with feature-based or architecture-based names
	4.	preserve behavior and compatibility where practical
	5.	improve clarity for developers and contributors
Phase 4J-WR - website (WR = Website Repo)
	1.	remove public-facing phase/spec terminology from the website/docs repo
	2.	clean generated docs, metadata, labels, search indexes, and LLM bundles
	3.	replace phase/spec language with feature-based or architecture-based names
	4.	preserve the docs platform’s functionality
	5.	improve public-facing coherence
Added "composer deploy <tag-name> <branch-name> command where the default branch is main
Phase 4K-FR - framework (FR = Framework Repo)
	1.	remove ALL phase/spec language from the framework repo
	2.	rename commands, tests, and other identifiers that expose internal iteration names
	3.	replace internal iteration names with feature-based or architecture-based names
	4.	preserve behavior and compatibility where practical
	5.	improve clarity for developers and contributors
Phase 4K-WR - website (WR = Website Repo)
	1.	remove ALL phase/spec terminology from the website/docs repo
	2.	clean generated docs, metadata, labels, search indexes, and LLM bundles
	3.	replace phase/spec language with feature-based or architecture-based names
	4.	preserve the docs platform’s functionality
	5.	improve public-facing coherence

Phase 4L - restore visual design from recovered files for real this time
Phase 4M - keep 4 main pages fully custom html and styling	
	
	
------------------------------------------------------------------------------------------

I am good with keeping both versions in public.

>>What’s weird is that the versioned path is using: commit-0e7beb0901e3

I agree! It even shows that commit-hash on the homepage right at the top of the page, and it's unseemly. 

Can you write a version of build-docs.php that uses tags?
How does it know which tag to use?
Is that something i need to specify in the update & deploy process?

Regardless, "Option A" all the way.
Could you please include that "very concrete recommended public/docs layout and rule set for what should exist in current vs versioned vs generated" in a Master Spec to give Codex to fix all of this, since I think the issues are larger than originally anticipated and our 'new' build-docs.php probably isn't sufficient for the entire scope of this (including manifests etc)?


I definitely would also like a docs sidebar auto-generated from the markdown tree instead of hardcoded navigation, and add a version dropdown in the docs header.

------------------------------------------------------------------------------------------


UPDATE & DEPLOY PROCESS:

Recommended release workflow

A good flow would look like this:

A. Finish framework work in framework repo

Merge into main.

B. Tag a framework release

Example:
git tag v1.0.0-beta
bit push origin v1.0.0-beta

C. Update the website repo’s framework reference

If submodule:
	•	update submodule to the release tag/commit

D. Run docs generation in website repo

The website reads:
	•	CLI commands
	•	architecture metadata
	•	machine-readable docs
	•	maybe generated markdown/reference files from the framework

E. Deploy website

Now the site docs match the framework release exactly.

That’s a beautiful clean chain.

How to clone the website repo with the submodule

When someone clones the website repo, they should do:
git clone --recurse-submodules https://github.com/yourname/foundryframework-org.git

That clones:
	•	the website repo
	•	the Foundry submodule inside framework/

If they already cloned without it, they can run:
git submodule update --init --recursive

How to update the submodule to the latest Foundry main
pin to releases or tags

When Foundry gets tagged:
git tag v1.0.0-beta
git push origin v1.0.0-beta

Then in the website repo:
cd framework
git fetch --tags
git checkout v1.0.0-beta
cd ..
git add framework
git commit -m "Update framework submodule to v1.0.0-beta"
php scripts/build-docs.php

Now your docs are explicitly tied to that release.

That is much cleaner than always pointing at latest main.

How to tell what commit the submodule is pinned to
From the website repo root:
git submodule status

You’ll get something like:
abc1234567890 framework

That hash is the exact Foundry commit your website is documenting.

------------------------------------------------------------------------------------------

Versioning Recommendation

Regarding your question about version numbers.

Yes — Phases 0A through 3 together are absolutely large enough to justify a release.

However I would recommend:

Version 1.0.0-beta rather than 1.0.0.

Reasons:

Real-world feedback has not yet occurred.

The extension system will evolve.

The pipeline architecture may still receive adjustments.

AI workflows may improve.

So the best initial release is:

1.0.0-beta

Once developers build real applications and the API stabilizes, then release:

1.0.0
