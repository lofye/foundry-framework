# Implementation Plan: 003-roadmap-phase-3-billing-workflows-orchestration-search-localization-roles-inspect

## Historical Provenance

- Imported spec path: `Modules/PreCanonical/specs/003-roadmap-phase-3-billing-workflows-orchestration-search-localization-roles-inspect.md`
- Source archive: `_import/pre-canonical-specs.md`
- Legacy name: `3 - Roadmap Phase 3 - Billing, Workflows, Orchestration, Search, Localization, Roles, Inspect`
- Legacy id: `3`
- Canonical pre-canonical id: `003`

## Historical Specification Summary

The original pre-canonical specification body is preserved in the imported execution spec. This reconstruction note records adjacent marked context and result evidence without inferring modern module ownership.

## Historical Preamble Context

### Preamble Block 1

- Name: `3 - Roadmap Phase 3 - Billing, Workflows, Orchestration, Search, Localization, Roles, Inspect`

PHASE 3

Phase 0A, 0B, 0C, and 0D are now canonical.

In addition to integrating with the semantic compiler, canonical application graph, extension system, migration/versioning model, doctor/analysis tooling, and graph visualization system, all new capabilities in this phase must also integrate with the execution pipeline, feature guard model, interceptor system, and execution-plan inspection/verification tools introduced in Phase 0D.

Important rules:
- Do not introduce ad hoc middleware stacks or parallel runtime request-processing systems.
- Any auth, permission, CSRF, rate-limiting, request-validation, content-negotiation, webhook-verification, locale-resolution, streaming, or other cross-cutting behavior must use the canonical pipeline/guard/interceptor architecture where appropriate.
- New features should emit graph-visible execution plans and participate in pipeline diagnostics, inspection, and visualization.
- Where useful, new capabilities should also integrate with doctor, graph visualization, and prompt-context extraction so that LLMs and humans can inspect the resulting system structure.

In short:
All future phases must be graph-native, extension-native, migration-aware, and pipeline-native.

Before implementing this phase, adapt all new systems to the Foundry Phase 0 semantic compiler, canonical application graph, extension system foundation, and migration/versioning model.

Important rules for this phase:
- Billing, workflows, orchestration, search adapters, realtime streams, localization, roles/policies, and the inspect UI must all be graph-aware.
- These features should be implemented as graph-native capabilities and/or graph-aware extensions, not as isolated subsystems with their own hidden registries.
- Any new domain config/spec files introduced in this phase must:
  - be versioned
  - compile into explicit graph nodes and edges
  - participate in diagnostics
  - support codemod/migration evolution
- Workflow/state-machine definitions, orchestration graphs, billing plans, search indexes, stream definitions, locale metadata, roles, and policies should all have explicit graph representations.
- The inspect UI must read from the graph and graph projections, not from custom duplicate data loaders.
- Any new verification logic should operate over the graph where practical.
- Any future-facing provider systems or adapters introduced in this phase must align with the extension foundation added in Phase 0.
- If any feature in this phase would naturally fit as a compiler extension, projection provider, verifier provider, or graph analyzer, implement it that way.

In short:
Phase 3 should make Foundry’s advanced capabilities feel like first-class graph-native systems built on the compiler core, not like separate products living beside it.

Here’s a single master prompt for Codex for Foundry Roadmap Phase 3.

This phase makes Foundry feel like a genuinely well-rounded modern framework for serious applications: billing, workflows, orchestration, richer search, realtime, i18n, roles/policies, and a visual inspect UI.

## Historical Implementation Evidence

### Result Block 1

- Name: `3 - Roadmap Phase 3 - Billing, Workflows, Orchestration, Search, Localization, Roles, Inspect`

Implemented Phase 3 wiring + coverage hardening, and coverage is now above 90%.

**What I changed**
- Registered Phase 3 CLI commands in [src/CLI/Application.php](/Users/lo_fye/code/foundry/src/CLI/Application.php).
- Updated command match coverage in [tests/Unit/CLICommandMatchesTest.php](/Users/lo_fye/code/foundry/tests/Unit/CLICommandMatchesTest.php).
- Added Phase 3 compiler/diagnostic coverage tests:
  - [tests/Unit/PhaseThreeSpecCompilerTest.php](/Users/lo_fye/code/foundry/tests/Unit/PhaseThreeSpecCompilerTest.php)
  - [tests/Unit/PhaseThreeSpecPassDiagnosticsTest.php](/Users/lo_fye/code/foundry/tests/Unit/PhaseThreeSpecPassDiagnosticsTest.php)
  - [tests/Unit/PhaseThreeSpecNormalizeCodemodTest.php](/Users/lo_fye/code/foundry/tests/Unit/PhaseThreeSpecNormalizeCodemodTest.php)
- Added runtime foundation tests for billing/workflow/orchestration/search/SSE/locales/roles:
  - [tests/Unit/PhaseThreeRuntimeFoundationsTest.php](/Users/lo_fye/code/foundry/tests/Unit/PhaseThreeRuntimeFoundationsTest.php)
- Added Phase 3 verifier tests:
  - [tests/Unit/PhaseThreeVerifiersTest.php](/Users/lo_fye/code/foundry/tests/Unit/PhaseThreeVerifiersTest.php)
- Added Phase 3 CLI integration + error-path tests:
  - [tests/Integration/CLIPhase3CommandsTest.php](/Users/lo_fye/code/foundry/tests/Integration/CLIPhase3CommandsTest.php)
  - [tests/Integration/CLIPhase3CommandErrorsTest.php](/Users/lo_fye/code/foundry/tests/Integration/CLIPhase3CommandErrorsTest.php)

**Validation**
- `php vendor/bin/phpunit` passed: `220` tests, `1265` assertions.
- Coverage run (`XDEBUG_MODE=coverage php vendor/bin/phpunit --coverage-text`):
  - **Lines: 90.80%** (`12547/13819`).

So yes, overall line coverage is now back above the 90% requirement.

16 files changed
+1843
-0
Undo
src/CLI/Application.php
src/CLI/CommandContext.php
src/Compiler/Analysis/ImpactAnalyzer.php
src/Compiler/Extensions/ExtensionRegistry.php
src/Compiler/GraphVerifier.php
src/Compiler/IR/Nodes.php
src/Compiler/Passes/DiscoveryPass.php
src/Compiler/Visualization/GraphVisualizer.php
tests/Integration/CLIPhase3CommandErrorsTest.php
tests/Integration/CLIPhase3CommandsTest.php
tests/Unit/CLICommandMatchesTest.php
tests/Unit/PhaseThreeRuntimeFoundationsTest.php
tests/Unit/PhaseThreeSpecCompilerTest.php
tests/Unit/PhaseThreeSpecNormalizeCodemodTest.php
tests/Unit/PhaseThreeSpecPassDiagnosticsTest.php
tests/Unit/PhaseThreeVerifiersTest.php





worked for 31 minutes
=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-
=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-
=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-
=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-
=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-

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

## Historical Verification Evidence

Historical verification details, when present, are preserved verbatim inside the paired result blocks above.

## Historical Stabilization Notes

Historical stabilization details, when present, are preserved verbatim inside the paired result blocks above.

## Current Repository Alignment

The imported artifact is intentionally retained under `Modules/PreCanonical` as archive-lineage context. Modern module ownership remains deferred until a separate explicit alignment spec maps the pre-canonical intent into current modules.

## Uncertainty And Reconstruction Notes

No modern module inference was performed. The generated note preserves only the marked archive relationships available through `S`, `R`, and `P` blocks.
