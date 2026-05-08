# Spec 24 — Contributor Portal

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
