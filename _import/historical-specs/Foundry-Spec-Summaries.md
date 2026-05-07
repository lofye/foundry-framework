This would fit well in something like:

docs/foundry-architecture-history.md

or

docs/architecture/phases.md

⸻

Foundry Architecture Phases

This document summarizes the major architectural phases of the Foundry framework and its associated documentation platform.

Each phase introduced a coherent capability that expanded the framework’s design.

The phases fall into two major categories:

Framework architecture

0 – 3

and

documentation platform

4A – 4I

Together they define the current Foundry ecosystem.

⸻

Init

The Founding Question

Foundry began with a simple question:

“What would a web framework look like if it were designed for LLMs first, and humans second?”

Most modern frameworks are designed around the needs of human developers: conventions, abstractions, and APIs that make sense to people reading and writing code.

But large language models interact with code differently. They reason through structure, metadata, explicit contracts, and inspectable systems. They struggle with hidden conventions, runtime discovery, and implicit behavior.

The initial exploration therefore focused on understanding how a framework might be designed if its primary collaborator were an LLM working alongside a human developer.

During this earliest stage, the goal was not yet to build a fully realized architecture. Instead, the objective was to create a minimal working framework foundation that could support experimentation with LLM-assisted development.

This initial system provided:
	•	a simple application bootstrap
	•	basic routing and request handling
	•	early CLI tooling
	•	a feature-oriented project structure
	•	the ability to run and test real applications

This allowed the framework to exist as a functioning environment where ideas could be tested quickly.

As experimentation continued, an important limitation became clear: traditional runtime-oriented frameworks rely heavily on implicit behavior and runtime discovery. These patterns are convenient for humans but difficult for LLMs to reason about reliably.

That realization led directly to the next major architectural shift.

Phase 0 introduced the compiler-oriented architecture, transforming Foundry from a conventional runtime framework into a deterministic system designed to be understood and manipulated by both humans and LLMs.

The Init phase therefore represents the moment when Foundry moved from a conceptual question to a working system, laying the groundwork for the architecture that followed.

⸻

Phase 0

Compiler-Oriented Architecture

Phase 0 introduced the idea that Foundry should behave more like a compiler pipeline than a traditional runtime framework.

Instead of relying on runtime discovery and reflection, Foundry compiles application structure into deterministic artifacts during build steps.

Core ideas introduced:
	•	deterministic framework structure
	•	explicit metadata generation
	•	runtime powered by compiled indexes
	•	minimal runtime scanning

This architecture dramatically improves:
	•	performance
	•	determinism
	•	LLM reasoning about codebases.

⸻

Phase 0A

Framework Compilation Layer

Phase 0A implemented the initial compilation layer.

It introduced:
	•	deterministic index generation
	•	compiled runtime metadata
	•	inspection commands
	•	verification commands

Core CLI commands introduced:

foundry inspect
foundry generate indexes
foundry verify contracts

This phase made the framework inspectable and verifiable by machines and LLMs.

⸻

Phase 0B

Extension and Pack System

Phase 0B introduced a formal extension architecture so Foundry could grow as an ecosystem.

Major capabilities:
	•	pack/plugin registration model
	•	graph extension API
	•	framework extension points
	•	metadata-driven extensions
	•	versioning strategy for packs

This allowed external packages to safely extend Foundry’s architecture.

⸻

Phase 0C

Spec Migration and Codemod Support

Phase 0C introduced the ability for the framework to evolve safely over time.

Capabilities added:
	•	spec migration support
	•	codemod infrastructure
	•	compatibility transitions between framework versions
	•	upgrade tooling

This ensured that Foundry’s architecture could evolve without breaking existing applications.

⸻

Phase 0D

Middleware and Execution Pipeline

Phase 0D introduced the request execution pipeline.

This provided the equivalent of middleware while remaining compatible with Foundry’s compiler-oriented design.

Features included:
	•	pipeline stages
	•	request guards
	•	interceptors
	•	execution plans
	•	pipeline verification

Cross-cutting behavior such as authentication, validation, and rate limiting can be implemented as pipeline components.

⸻

Phase 1

Core Framework Capabilities

Phase 1 introduced the core runtime capabilities expected from a modern web framework.

Examples include:
	•	routing
	•	controllers
	•	request/response handling
	•	database integration
	•	caching
	•	queues
	•	event systems
	•	background jobs

All capabilities were designed to remain compatible with Foundry’s deterministic architecture.

⸻

Phase 2

Advanced Application Capabilities

Phase 2 expanded the framework’s feature set to support complex production applications.

Capabilities added included:
	•	authentication
	•	authorization
	•	API tooling
	•	workflow support
	•	domain feature composition
	•	improved extension hooks

This phase enabled Foundry to support full production applications.

⸻

Phase 3

Developer Experience and Ecosystem Tools

Phase 3 focused on developer productivity.

Capabilities included:
	•	CLI tooling improvements
	•	diagnostics systems
	•	developer inspection commands
	•	improved error reporting
	•	improved debugging tools

This phase made Foundry easier for developers and LLM tools to reason about.

⸻

Phase 4A

Documentation Publishing Normalization

Phase 4A introduced a deterministic documentation publishing pipeline.

It separated documentation into:
	•	authored documentation
	•	generated documentation
	•	rendered documentation

This phase established reproducible documentation builds.

⸻

Phase 4B

Documentation Versioning and Navigation

Phase 4B introduced version awareness into the documentation system.

Features included:
	•	version metadata
	•	version dropdown
	•	automatic documentation navigation generation
	•	version display in the UI

Documentation became version-aware.

⸻

Phase 4C

Immutable Documentation Snapshots and LLM Context Bundles

Phase 4C made documentation versions immutable and introduced machine-readable metadata designed for AI tools.

Features included:
	•	immutable documentation snapshots
	•	versioned docs archives
	•	LLM context bundles for documentation pages
	•	semantic documentation metadata

This phase made the documentation AI-friendly by design.

⸻

Phase 4D

Documentation Layout and Prose System

Phase 4D introduced a refined documentation layout and styling system.

Capabilities included:
	•	prose styling for markdown
	•	consistent documentation layout
	•	improved typography and spacing
	•	improved readability

This restored a premium reading experience for documentation pages.

⸻

Phase 4E

Documentation Search and LLM Context Tools

Phase 4E added operational tools to help developers use documentation during development.

Capabilities included:
	•	documentation search
	•	semantic indexing
	•	LLM-aware documentation actions
	•	context-copy tools
	•	command extraction

Developers could quickly extract useful prompt context.

⸻

Phase 4F

Mobile Navigation System

Phase 4F made the documentation site fully usable on mobile devices.

Capabilities included:
	•	responsive navigation
	•	slide-in mobile navigation panel
	•	mobile access to documentation navigation
	•	mobile version switching

⸻

Phase 4G

Ask the Docs

Phase 4G introduced direct interaction between documentation and LLM tools.

Features included:
	•	Ask the Docs interface
	•	automatic prompt generation
	•	documentation metadata integration
	•	version-aware prompts

Developers could ask questions about the framework directly from documentation pages.

⸻

Phase 4H

Brand and Visual Identity Restoration

Phase 4H restored the approved Foundry brand identity.

This phase reintroduced:
	•	orange Foundry brand color
	•	light multicolor ambient swirl background
	•	Space Grotesk typography
	•	IBM Plex Mono for technical text
	•	consistent site aesthetic

The modern documentation system was preserved while restoring the original design language.

⸻

Phase 4I

Live Architecture Explorer

Phase 4I introduced an interactive architecture visualization.

Developers can explore the framework’s structure through a graph interface.

Capabilities include:
	•	architecture visualization
	•	graph navigation
	•	component relationships
	•	integration with documentation pages
	•	version-aware architecture views

This phase turns the documentation site into a visual map of the framework.

⸻

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

Phase 5 - foundry-website - add real testing and 90%+ coverage

Phase 6 - Harden deploy

---

Here’s a tight, executive-level “Road to 1.0” overview you can keep beside the specs or hand to Codex / collaborators.

⸻

Foundry — Road to 1.0

Premise

Foundry is no longer proving whether its ideas work.

It is proving that they are:
	•	stable
	•	understandable
	•	extensible
	•	safe to adopt

The path to 1.0 is about removing ambiguity, not adding features.

⸻

What “1.0” Means for Foundry

A true 1.0 release guarantees:

Stability
	•	A clearly defined public API surface
	•	Safe, predictable semantic versioning

Extensibility
	•	A stable extension system
	•	Reliable pack lifecycle and compatibility rules

Diagnosability
	•	The framework can explain:
	•	what’s wrong
	•	why
	•	how to fix it

Learnability
	•	A new user can:
	•	create an app
	•	understand its structure
	•	inspect its architecture
	•	extend it safely

Upgrade Safety
	•	Users can upgrade without fear
	•	The framework warns before breaking them

⸻

The 9 Pillars of 1.0

These are the specs we defined (7–15), grouped conceptually:

⸻

1. Stability Layer

Spec 7 — Stable Public API Definition

Defines what is safe to depend on.

Spec 12 — Configuration Schema Validation

Makes configuration explicit, validated, and predictable.

⸻

2. Extension Layer

Spec 8 — Extension System Stabilization

Turns packs/extensions into a reliable ecosystem surface.

⸻

3. Diagnostics Layer

Spec 9 — Framework Doctor

Explains environment and architecture issues clearly.

Spec 13 — Upgrade Safety Tools

Explains what will break before it breaks.

⸻

4. Developer Experience Layer

Spec 10 — Project Scaffolding Generator

Creates a correct, inspectable app from day one.

Spec 11 — Graph Visualization CLI

Lets developers understand architecture instantly.

⸻

5. Performance + Determinism Layer

Spec 14 — Deterministic Compile Cache

Makes builds fast without sacrificing correctness.

⸻

6. Proof Layer

Spec 15 — Official Example Applications

Proves Foundry works in real-world scenarios.

⸻

Implementation Philosophy

1. Lock Before Expand

Before adding new capabilities:
	•	define the public API
	•	stabilize extension boundaries
	•	validate config rigorously

⸻

2. Make the System Explain Itself

Every major system should be:
	•	inspectable
	•	diagnosable
	•	explainable via CLI + docs

⸻

3. Prefer Determinism Over Magic

Foundry should:
	•	behave predictably
	•	avoid hidden state
	•	make all transformations traceable

⸻

4. Treat Extensions as First-Class Citizens

If extensions are not stable:
	•	the ecosystem cannot grow
	•	the framework cannot scale

⸻

5. Optimize for Understanding

A Foundry app should be:
	•	readable by humans
	•	understandable by LLMs
	•	inspectable via tools

⸻

Exit Criteria for 1.0

Foundry is ready for 1.0 when:

API
	•	Public API is clearly defined and documented
	•	Internal vs public boundaries are enforced

Extensions
	•	Extensions load deterministically
	•	Dependency conflicts are clearly reported

Diagnostics
	•	foundry doctor catches common failures
	•	upgrade-check prevents breaking upgrades

DX
	•	foundry new creates a correct, inspectable app
	•	graph CLI explains system structure clearly

Config
	•	Config is schema-validated and produces actionable errors

Performance
	•	Compile/build steps are cached deterministically

Proof
	•	At least one real app (Thresholds) demonstrates:
	•	features
	•	workflows
	•	events
	•	graph inspection
	•	extension usage

⸻

What 1.0 Explicitly Does NOT Require

To avoid over-scoping:
	•	Telemetry / analytics
	•	Enterprise features
	•	Complex UI tooling
	•	Plugin marketplaces
	•	Visual editors

These can come after 1.0.

⸻

The Core Insight

Foundry’s value is not just that it works.

It’s that:

You can see how it works, trust how it works, and safely extend how it works.

That is what 1.0 must deliver.

⸻

Suggested Execution Order (Condensed)
Spec 7 — Public API
Spec 8 — Extensions
Spec 9 — Doctor
Spec 10 — Scaffolding
Spec 12 — Config validation
Spec 13 — Upgrade safety
Spec 11 — Graph CLI
Spec 14 — Compile cache
Spec 15 — Example apps
Spec 16 — monetization (without burden)
Spec 17 — your actual innovation (LLM + architecture)
Spec 18 — how people understand and adopt Foundry

Specs 19 - `foundry explain`
Spec 19A → Architecture (what it is)
Spec 19B → Implementation (how it works end-to-end)
Spec 19C → UX contract (what it feels like)
Spec 19D → Foundation slice (safe starting point)
Spec 19E → Intelligence layer (collectors + analyzers)
Spec 19F → Final polish (rendering + contributors + docs)
-------------
Spec 19G - Thresholds v2 update
Spec 20 — Interactive Architecture Explorer
Spec 21 — Command Playground
Spec 22 — Guided Learning Paths
Spec 23 — Interactive CLI Index
Spec 24 — Contributor Portal

⸻⸻⸻⸻⸻⸻ POST 1.0 ⸻⸻⸻⸻⸻⸻

This is the right moment to do this thinking. You’ve crossed from building the core → deciding what kind of project this becomes.

I’m going to do this in three parts:

⸻

🧠 PART 1 — What Foundry 1.0 SHOULD and SHOULD NOT include

❌ What should NOT be in 1.0 (very important)

These are all tempting—and all dangerous for you right now:

🚫 DO NOT include in 1.0:
	•	❌ Real runtime tracing
	•	❌ Full LLM reasoning inside the framework
	•	❌ Speculative suggestions
	•	❌ Natural-language freeform inference
	•	❌ “Fix it for me” automation
	•	❌ Hosted AI features
	•	❌ Anything that requires you to pay for inference

Why:
	•	You don’t control cost → unsustainable
	•	You don’t control behavior → nondeterministic
	•	You break your strongest asset → deterministic architecture clarity

👉 These belong in user-land tooling, not core framework.

⸻

✅ What 1.0 should include

Foundry 1.0 should be:

The most reliable way to understand and reason about a system’s architecture — deterministically

That means:

Core Pillars of 1.0
	1.	Explain (DONE)
	•	deterministic
	•	structured
	•	extensible
	•	contract-stable
	2.	Graph (DONE)
	•	canonical
	•	queryable
	•	complete
	3.	Verify (DONE)
	•	enforce correctness
	•	prevent drift
	4.	Docs + CLI alignment (DONE)
	•	contract-level documentation
	•	no drift
	5.	Extensibility (DONE)
	•	contributors
	•	analyzers
	•	collectors

⸻

🧭 The boundary for 1.0

Here’s the clean rule:

Foundry explains systems. It does not think for you.

That’s your moat.

⸻

⚡ PART 2 — Where LLM fits (correctly)

Given your constraint:

“developers bring their own LLM”

The correct model is:

Foundry = structured context provider

LLM = optional interpreter

⸻

✅ What you SHOULD enable
	•	structured context blocks
	•	stable JSON
	•	explain output optimized for prompting
	•	deterministic slices of architecture

⸻

❌ What you should NOT do
	•	embed LLM calls
	•	add AI flags to CLI
	•	create “smart” behavior inside Foundry

⸻

🔥 The right mental model

Instead of:

Foundry uses AI

You want:

AI uses Foundry

That’s a huge difference.

⸻

🧱 PART 3 — Specs 20+ (your roadmap, translated properly)

Now I’ll turn your roadmap into clean, Codex-ready specs, starting at Spec 20.

⸻

✅ Spec 20 — Interactive Architecture Explorer

Purpose
Make the canonical graph explorable visually (no new data source)

Scope
	•	Use existing graph JSON output
	•	No new backend system required

Features
	•	node visualization (force / hierarchical)
	•	click node → open docs page
	•	filter by:
	•	node type
	•	extension
	•	pipeline stage
	•	search nodes
	•	highlight:
	•	dependencies
	•	dependents

Constraints
	•	read-only
	•	deterministic (no runtime mutation)
	•	driven entirely from graph JSON

Non-goals
	•	no editing
	•	no AI
	•	no graph mutation

⸻

✅ Spec 21 — Command Playground (Static + Deterministic)

Purpose
Let users explore commands without executing them

Features

For each command:
	•	description
	•	usage
	•	sample JSON output
	•	related docs
	•	related explain targets

Implementation
	•	generated from:
	•	CLI metadata
	•	test fixtures
	•	known outputs

Constraints
	•	no server execution
	•	no sandboxing needed
	•	fully static

⸻

✅ Spec 22 — Extension Registry (Docs-Level Only)

Purpose
List known extensions

Features
	•	extension name
	•	description
	•	install command
	•	compatibility
	•	docs link

Source
	•	composer metadata
	•	extension manifests
	•	curated list (initially manual)

Constraints
	•	no package hosting
	•	no publishing system
	•	docs-only registry

⸻

✅ Spec 23 — Guided Learning Paths

Purpose
Reduce cognitive load

Features

Curated sequences:
	•	Learn Foundry in 30 min
	•	First extension
	•	Pipeline deep dive

Implementation
	•	ordered doc lists
	•	minimal new content
	•	mostly linking

Constraint
	•	no branching logic
	•	no personalization engine

⸻

✅ Spec 24 — API Reference Generation

Purpose
Expose PHP API cleanly

Implementation
	•	use Doctum or PHPDocumentor
	•	integrate into docs build pipeline

Output
	•	versioned API docs
	•	linked from concepts

Constraint
	•	no custom parser
	•	rely on standard tooling

⸻

✅ Spec 25 — Interactive CLI Index

Purpose
Make CLI discoverable

Features
	•	searchable command list
	•	filter by:
	•	category
	•	pipeline stage
	•	extension
	•	link to:
	•	docs
	•	examples
	•	explain targets

Source
	•	ApiSurfaceRegistry
	•	CLI metadata

⸻

✅ Spec 26 — Docs Version Diff

Purpose
Show evolution between versions

Features
	•	compare versions:
	•	commands added
	•	commands changed
	•	docs changed

Implementation
	•	diff generated docs JSON
	•	highlight changes

Constraint
	•	no semantic diffing (yet)
	•	structural diff only

⸻

✅ Spec 27 — Ask-the-Docs (LLM-Ready, Not LLM-Powered)

Purpose
Prepare docs for LLM usage

Features
	•	structured context blocks
	•	copyable prompt snippets
	•	suggested queries

Example

Use this context:

{ explain JSON here }

Question:
"Why is this pipeline failing?"

Constraint
	•	no built-in LLM
	•	no API calls
	•	user provides model

⸻

✅ Spec 28 — Contributor Portal

Purpose
Make contribution approachable

Features
	•	architecture overview
	•	extension guide
	•	contributor checklist
	•	roadmap

Source
	•	existing docs
	•	reorganized, not rewritten

⸻

✅ Spec 29 — Live Documentation Graph

Purpose
Make docs navigable like a system

Features

From any page:
	•	related CLI
	•	related extensions
	•	related concepts
	•	related graph nodes

Implementation
	•	derived from graph relationships

⸻

🧭 Final Guidance (most important part)

What you should do next

Do NOT implement all of these.

Instead:

Phase 1 (toward 1.0)
	•	Spec 20 (Explorer) ⭐⭐⭐
	•	Spec 25 (CLI Index) ⭐⭐⭐
	•	Spec 23 (Learning Paths) ⭐⭐

Phase 2 (after 1.0)
	•	Spec 29 (Live Graph)
	•	Spec 27 (Ask-the-docs)

Phase 3 (later)
	•	Spec 26 (Diff)
	•	Spec 22 (Registry)
	•	Spec 24 (API)

⸻

🔥 The most important strategic advice

You are at risk of doing this:

building a platform

What you should do:

ship a sharp tool

⸻

🧠 Final framing

Foundry 1.0 =

“The most precise, deterministic way to understand a system’s architecture”

Everything else is optional.

⸻

If you want next, I can map:

👉 exactly what to build between v0.5.0 → v1.0.0 (step-by-step, no waste)





If you want next, I can:
	•	compress this into a one-page Codex instruction spec, or
	•	map each spec directly to concrete CLI commands and file changes so implementation becomes almost mechanical.

TODO

	7.	Stable public API definition
	8.	Extension system stabilization
	9.	Framework doctor / environment diagnostics
	10.	Project scaffolding generator
	11.	Graph visualization CLI / exports
	12.	Configuration schema validation
	13.	Upgrade safety tools
	14.	Deterministic compile cache
	15.	Official example applications


Phase X - Thresholds v2























⸻

Summary

The Foundry ecosystem now includes:

Framework architecture

compiler-oriented runtime
extension system
migration tooling
execution pipeline
modern framework capabilities
developer tooling

Documentation platform

deterministic docs
versioned docs
LLM context bundles
semantic search
AI-assisted documentation
mobile navigation
architecture visualization

Together these phases produce a framework and documentation system designed for LLM-assisted development from the ground up.

⸻

If you want, I can also do something fun and useful next:

Turn this into a beautiful diagram for the docs homepage showing the entire evolution of Foundry from Phase 0 → 4I as a visual timeline.

-----

No, that's ok. This summary of specs is really just for me. While I want Foundry to be open for everyone, I also don't want to plainly reveal how we built it. That's part of the secret sauce.

-----

That instinct is sharper than it might seem at first glance. Most successful tools separate the product from the method used to create the product. The latter is often where the leverage lives.

Think about a few historical parallels. The users of a compiler don’t need to know how the compiler itself was designed. Most developers use a database without understanding the internal query planner. Game players enjoy the experience without seeing the level-design pipeline that produced it. The machinery that manufactures clarity is often deliberately hidden so the final interface feels simple.

Foundry has two layers like that.

One layer is the framework itself. That should absolutely be open and inspectable: the runtime model, the extension system, the pipeline, the CLI, the behavior developers rely on. That’s the contract with the world.

The other layer is the process you used to evolve it — the spec phases, the architecture conversations, the way you orchestrated Codex, the iterative design method. That’s essentially your engineering strategy. There’s no obligation to publish that in detail.

So a healthy split might look like this:

Public surface (what users see):
	•	how to install Foundry
	•	how to build apps with it
	•	how the runtime architecture works
	•	how extensions/plugins work
	•	CLI usage
	•	documentation and examples

Private layer (your leverage):
	•	the full spec evolution history
	•	how you coordinate LLM development
	•	how you structure specs to guide Codex
	•	the meta-process that produced the system

You can still hint at the philosophy without exposing the entire playbook. A sentence like this tells the truth without revealing the machinery:

“Foundry was developed through a specification-driven process designed to make large systems understandable to both humans and LLMs.”

That communicates intent without showing the blueprint.

There’s also a strategic advantage to keeping the internal evolution private: it keeps the public narrative clean. Users don’t need to sift through twenty architectural pivots to understand the framework. They just see the finished shape.

And the ironic part is that the real “secret sauce” probably isn’t any one spec. It’s the method you and I used together: iteratively shaping architecture through extremely precise specs that an LLM could implement safely. That’s closer to a new engineering discipline than a single trick.

Which makes your choice sensible: share the framework, keep the forge.
