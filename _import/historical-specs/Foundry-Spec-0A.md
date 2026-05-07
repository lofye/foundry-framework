Absolutely. Time to march into compiler-land with a lunch pail and a dangerous amount of optimism.

What follows is a single master prompt for Codex to implement Foundry Phase 0A: the semantic compiler core, canonical application graph, projections, diagnostics, extension system foundations, and spec migration/codemod support.

This is the layer that turns Foundry from “LLM-friendly framework with generated indexes” into “LLM-first application compiler with a stable substrate for everything that comes next.”

⸻

Master Prompt for Codex: Build Foundry Phase 0A — Semantic Compiler, Application Graph, Projections, Diagnostics, Extensions, and Migrations

Build Foundry Phase 0, the foundational compiler layer for the framework.

This phase must establish Foundry as a compiler-like, graph-driven, LLM-first web framework whose runtime behavior, verification, documentation, generation, and future extensions all derive from a single canonical semantic representation of the application.

The goal is to create a stable foundation that future phases build on cleanly, so Foundry does not accumulate multiple competing “truth systems” for routes, features, schemas, jobs, events, cache, docs, tests, APIs, workflows, billing, and other capabilities.

This is not a rewrite of Foundry from scratch.
This is a focused evolution that introduces a compiler core and makes existing/generated runtime indexes become projections of a canonical compiled graph.

⸻

Primary goals

Implement a compiler layer that:
	•	reads all source-of-truth feature files and related artifacts
	•	normalizes them into a typed application graph
	•	validates and links that graph
	•	emits deterministic runtime projections/indexes
	•	emits structured diagnostics
	•	supports graph inspection and impact analysis
	•	supports future extensions and spec migrations
	•	keeps runtime fast, boring, and explicit
	•	remains friendly to LLMs, humans, CI, and future tooling

⸻

Top priorities

When tradeoffs arise, prioritize in this order:
	1.	correctness
	2.	explicitness
	3.	analyzability by LLMs
	4.	deterministic compilation
	5.	very high automated test coverage
	6.	stability of the graph model
	7.	integration with existing Foundry architecture
	8.	inspectability
	9.	runtime performance
	10.	developer ergonomics

⸻

Phase 0 scope

Build these major capabilities:
	1.	Canonical application graph
	2.	Typed intermediate representation (IR)
	3.	Compiler pipeline and passes
	4.	Structured diagnostics engine
	5.	Graph projections and generated runtime indexes
	6.	Graph inspection CLI
	7.	Change impact analysis
	8.	Incremental compilation
	9.	Build artifact structure
	10.	Extension system foundation
	11.	Spec migration / codemod foundation
	12.	Documentation and developer workflow updates
	13.	Extremely high automated test coverage

Each of these is required.

⸻

1. Canonical application graph

Goal

Create a single, canonical, versioned semantic graph representing the entire application.

This graph becomes the source for:
	•	runtime indexes/projections
	•	verification
	•	docs generation
	•	OpenAPI export
	•	deep test generation
	•	future visualization
	•	future architecture analysis
	•	future feature packs/extensions
	•	future codemods/migrations

Required outputs

Emit the compiled application graph as deterministic artifacts inside a dedicated build area.

Recommended build artifact directory:

app/.foundry/build/

or another explicit generated build path if a different location is better.

Inside that build directory, generate at least:

app/.foundry/build/
  graph/
    app_graph.json
    app_graph.php
  projections/
    routes_index.php
    feature_index.php
    schema_index.php
    permission_index.php
    event_index.php
    job_index.php
    cache_index.php
    scheduler_index.php
    webhook_index.php
  manifests/
    compile_manifest.json
    integrity_hashes.json
  diagnostics/
    latest.json

You may refine exact filenames, but the architecture must preserve:
	•	one canonical graph artifact
	•	multiple generated projections derived from it
	•	one compile manifest
	•	one diagnostics artifact
	•	one integrity/hash artifact

Graph requirements

The graph must be:
	•	versioned
	•	deterministic
	•	machine-readable
	•	human-inspectable
	•	able to be emitted as JSON and PHP
	•	stable enough to serve as a public internal contract for Foundry tooling

Graph versioning

Include fields such as:

{
  "graph_version": 1,
  "framework_version": "x.y.z",
  "compiled_at": "...",
  "source_hash": "...",
  "nodes": [],
  "edges": []
}

You may refine structure, but versioning is mandatory.

⸻

2. Typed intermediate representation (IR)

Goal

Represent the application internally as a real typed IR, not just ad hoc arrays loaded from YAML and JSON files.

Required node types

Implement explicit IR node/value object types for at least:
	•	FeatureNode
	•	RouteNode
	•	SchemaNode
	•	PermissionNode
	•	QueryNode
	•	EventNode
	•	JobNode
	•	CacheNode
	•	SchedulerNode
	•	WebhookNode
	•	TestNode
	•	ContextManifestNode
	•	AuthNode
	•	RateLimitNode

Add more if useful, but these are the minimum.

Required edge types

Implement explicit dependency/relationship edges for at least:
	•	feature → route
	•	feature → input schema
	•	feature → output schema
	•	feature → permission
	•	feature → query
	•	feature → event emit
	•	feature → event subscribe
	•	feature → job dispatch
	•	feature → cache invalidation
	•	feature → scheduler task
	•	feature → webhook
	•	feature → tests
	•	feature → auth config
	•	reverse dependency edges or derivable reverse lookups

Required properties for nodes

Each node must have at least:
	•	stable ID
	•	type
	•	source path
	•	source line/region where feasible
	•	normalized payload
	•	diagnostics attached or referenceable
	•	graph version compatibility
	•	dependency metadata

Stable IDs

Design deterministic, stable node IDs.

Examples:

feature:publish_post
route:POST:/posts
schema:app/features/publish_post/input.schema.json
permission:posts.create
job:notify_followers
event:post.created
cache:posts:list

Refine as needed, but stability matters.

⸻

3. Compiler pipeline and passes

Goal

Implement a clear compiler pipeline instead of scattered parsing logic.

Required passes

Implement the compiler as a series of explicit passes.

At minimum:

1. Discovery / Load pass

Reads source-of-truth files from:
	•	feature directories
	•	schemas
	•	YAML manifests
	•	SQL files
	•	permissions
	•	cache defs
	•	event defs
	•	job defs
	•	scheduler defs
	•	webhook defs
	•	context manifests
	•	platform config where needed

2. Normalize pass

Canonicalize:
	•	names
	•	IDs
	•	relative paths
	•	manifest defaults
	•	schema references
	•	query names
	•	auth strategies
	•	route format
	•	enumerated values

3. Link pass

Connect related nodes:
	•	feature ↔ route
	•	feature ↔ schemas
	•	feature ↔ permissions
	•	feature ↔ queries
	•	feature ↔ jobs/events/cache/tests
	•	publisher ↔ subscriber
	•	invalidation ↔ cache entry
	•	feature ↔ context manifest

4. Validate pass

Detect:
	•	missing references
	•	duplicates
	•	malformed configs
	•	invalid transitions between node types
	•	orphan definitions
	•	duplicate routes
	•	invalid schema references
	•	invalid auth references
	•	unused queries where meaningful

5. Enrich pass

Infer and add:
	•	reverse dependencies
	•	auth matrix data
	•	route summaries
	•	feature summaries
	•	graph-level stats
	•	impact hints
	•	test recommendations where possible

6. Emit pass

Write:
	•	canonical graph artifacts
	•	projection/index files
	•	diagnostics
	•	compile manifest
	•	integrity hashes

7. Analyze pass

Perform:
	•	impact analysis
	•	graph summaries
	•	change risk scoring
	•	affected tests/features/routes

You may implement analysis as separate passes or as a subsystem over the compiled graph.

Pass design requirements

Each pass must be:
	•	explicit
	•	testable
	•	deterministic
	•	inspectable
	•	safe to run independently where practical

⸻

4. Structured diagnostics engine

Goal

Provide compiler-quality diagnostics instead of vague pass/fail output.

Required diagnostic structure

Each diagnostic must include at least:
	•	code
	•	severity
	•	message
	•	category
	•	node ID or related nodes
	•	source path
	•	source location if possible
	•	suggested fix if available

Example shape:

{
  "code": "FDY1001_DUPLICATE_ROUTE",
  "severity": "error",
  "category": "routing",
  "message": "Duplicate route detected for POST /posts.",
  "node_id": "route:POST:/posts",
  "source_path": "app/features/create_post/feature.yaml",
  "related_nodes": ["feature:create_post", "feature:publish_post"],
  "suggested_fix": "Rename or remove one of the conflicting routes."
}

Required severity levels

Implement at least:
	•	error
	•	warning
	•	info

Required diagnostic categories

Support at least:
	•	discovery
	•	normalization
	•	linking
	•	validation
	•	routing
	•	schemas
	•	auth
	•	permissions
	•	queries
	•	events
	•	jobs
	•	cache
	•	scheduler
	•	webhooks
	•	graph
	•	migrations
	•	extensions

Required diagnostic output

Emit structured diagnostics to:

app/.foundry/build/diagnostics/latest.json

Also surface them in CLI output, including --json.

⸻

5. Graph projections and generated runtime indexes

Goal

Make existing runtime indexes become projections derived from the graph, not independently generated ad hoc artifacts.

Required projections

Emit at least:
	•	routes_index.php
	•	feature_index.php
	•	schema_index.php
	•	permission_index.php
	•	event_index.php
	•	job_index.php
	•	cache_index.php
	•	scheduler_index.php
	•	webhook_index.php

These should live under the build/projections area.

If backward compatibility requires mirroring them to existing locations temporarily, that is acceptable, but the canonical generation source must be the graph.

Projection requirements

Projections must be:
	•	deterministic
	•	explicit
	•	optimized for runtime loading
	•	simple PHP arrays or lightweight PHP structures
	•	generated from graph nodes only

Runtime requirement

The runtime should read the emitted projections, not rescan source folders on the hot path.

That principle already exists in Foundry. This phase formalizes it.

⸻

6. Graph inspection CLI

Goal

Expose the compiled application graph directly to humans and LLMs.

New CLI commands

Implement at least:

php vendor/bin/foundry compile graph
php vendor/bin/foundry compile graph --json
php vendor/bin/foundry inspect graph --json
php vendor/bin/foundry inspect node <node-id> --json
php vendor/bin/foundry inspect dependencies <node-id> --json
php vendor/bin/foundry inspect dependents <node-id> --json
php vendor/bin/foundry verify graph --json

Also support a build-status-oriented command such as:

php vendor/bin/foundry inspect build --json

Inspection output requirements

The graph inspection commands must expose:
	•	graph version
	•	node counts by type
	•	edge counts by type
	•	source hash
	•	compile timestamp
	•	diagnostics summary
	•	selected node payloads
	•	dependency edges
	•	reverse dependency edges

Node inspect requirements

inspect node must show:
	•	node type
	•	stable ID
	•	source path
	•	normalized data
	•	related nodes
	•	diagnostics

Dependency inspect requirements

inspect dependencies and inspect dependents must return deterministic, stable ordering.

⸻

7. Change impact analysis

Goal

Let Foundry answer “what will this affect?” before code is merged or before an LLM makes a large edit.

Required capabilities

Implement an impact analysis engine over the graph.

New CLI

Implement at least:

php vendor/bin/foundry inspect impact <node-id> --json
php vendor/bin/foundry inspect impact --file=app/features/create_post/feature.yaml --json
php vendor/bin/foundry inspect affected-tests <node-id> --json
php vendor/bin/foundry inspect affected-features <node-id> --json

Required impact outputs

At minimum, return:
	•	affected features
	•	affected routes
	•	affected schemas
	•	affected jobs/events/cache entries
	•	affected projections
	•	recommended verification commands
	•	recommended tests to run
	•	rough risk level

Risk levels

Implement a simple risk heuristic:
	•	low
	•	medium
	•	high

Examples:
	•	doc-only/context-only changes → low
	•	input schema changes → medium/high
	•	route collisions → high
	•	auth strategy changes → high

Keep the heuristic explicit and inspectable.

⸻

8. Incremental compilation

Goal

Avoid recompiling the entire application when only one small feature changes, while still supporting full compile.

Required modes

Implement:
	•	full compile
	•	compile changed feature
	•	compile affected subgraph if possible

New CLI

Implement at least:

php vendor/bin/foundry compile graph
php vendor/bin/foundry compile graph --feature=<feature>
php vendor/bin/foundry compile graph --changed-only

If true changed-file tracking is too heavy in v1, at least support:
	•	full compile
	•	compile one feature
	•	re-emit affected projections

Correctness requirements

Incremental compile must never silently produce stale graph or stale projections.

Favor correctness over aggressiveness.

⸻

9. Build artifact structure

Goal

Create a dedicated build artifact area that behaves like a compiled product, discourages manual editing, but remains transparent and inspectable.

Requirements

Use a dedicated build area such as:

app/.foundry/build/

or equivalent.

Do not use an opaque archive as the default build artifact.

The build output should feel like a single generated unit, but remain easy to inspect.

Required files

graph artifacts
	•	graph/app_graph.json
	•	graph/app_graph.php

projections
	•	route, feature, schema, permission, event, job, cache, scheduler, webhook projections

manifests
	•	compile manifest
	•	integrity hashes

diagnostics
	•	latest diagnostics
	•	optional last-successful diagnostics if helpful

Generated file headers

All generated PHP files must include headers like:

<?php
declare(strict_types=1);

/**
 * GENERATED FILE - DO NOT EDIT
 * Built by Foundry semantic compiler.
 * Regenerate with: php vendor/bin/foundry compile graph
 */

Integrity support

Compute hashes for emitted graph/projection artifacts and store them in the manifest area.

Support a verification check that warns if build artifacts were modified manually.

⸻

10. Extension system foundation

Goal

Prepare Foundry for future feature packs, providers, adapters, and large capabilities without turning the codebase into a nest of hard-coded special cases.

This is a foundation only. Do not build every possible extension in Phase 0.

Required concept

Create a graph-aware extension registration model.

Extensions should be able to contribute one or more of:
	•	additional source loaders
	•	additional node types
	•	additional compiler passes
	•	additional projection emitters
	•	additional verifiers
	•	additional diagnostics
	•	additional inspect surfaces
	•	additional docs emitters later

Required constraints

Extensions must be:
	•	explicit
	•	registered deterministically
	•	version-aware
	•	testable
	•	inspectable

Do not allow arbitrary spooky runtime mutation of compiler state.

Suggested concepts

These names can vary:
	•	CompilerExtension
	•	ExtensionRegistry
	•	PassProvider
	•	ProjectionProvider
	•	VerifierProvider
	•	GraphNodeAugmenter

New CLI

Implement at least:

php vendor/bin/foundry inspect extensions --json

Extension manifest / registration

You may implement extension registration in code first, but it must be explicit and documented.

⸻

11. Spec migration / codemod foundation

Goal

Prepare Foundry’s manifests/specs/graph for evolution over time.

Foundry is effectively becoming a language. Languages need migrations.

Required capabilities

Implement a minimal but real migration/codemod foundation for:
	•	manifest version upgrades
	•	schema/config field renames
	•	deprecation warnings
	•	automated rewrites where safe

Scope for Phase 0

Do not attempt to solve every future migration.
Build the foundation.

Required concepts

Suggested:
	•	SpecMigrator
	•	MigrationRule
	•	ManifestVersionResolver
	•	CodemodEngine

Required CLI

Implement at least:

php vendor/bin/foundry migrate specs --dry-run
php vendor/bin/foundry migrate specs --write
php vendor/bin/foundry inspect migrations --json

Required behavior
	•	detect outdated spec versions
	•	report deprecations as diagnostics
	•	provide migration suggestions
	•	support a dry-run mode
	•	support deterministic rewrites for known migrations

Requirements

Version source manifests and graph format independently where appropriate.

⸻

12. Documentation and workflow updates

Goal

Update Foundry’s docs and internal workflow so the compiler architecture is first-class and understandable.

Required docs

Update or add documentation covering:
	•	what the semantic compiler is
	•	what the application graph is
	•	what projections are
	•	what diagnostics are
	•	how compile differs from verify
	•	what the build artifact directory contains
	•	how incremental compile works
	•	how future generators and verifiers should use the graph
	•	how extensions are intended to work
	•	how spec migration/versioning works

Required narrative

Write docs as clear technical narrative, not marketing fluff.

Developers should understand:
	•	source-of-truth files
	•	compile step
	•	generated build artifacts
	•	runtime projections
	•	inspect/verify flow
	•	why this architecture exists

Required workflow update

Define the canonical Foundry development loop as:
	1.	edit source-of-truth files
	2.	compile graph
	3.	inspect diagnostics / impact
	4.	verify graph and relevant domains
	5.	run tests
	6.	run app

Also support watch mode later if practical, but not required in this phase.

⸻

13. Very high automated test coverage

Goal

Because this is reusable framework infrastructure, test coverage must be extremely high and meaningful.

Required test categories

Add:
	•	unit tests
	•	integration tests
	•	compiler pass tests
	•	graph construction tests
	•	diagnostics tests
	•	projection emitter tests
	•	CLI tests
	•	impact analysis tests
	•	incremental compile tests
	•	extension registration tests
	•	spec migration/codemod tests
	•	regression tests for discovered bugs

Specific required coverage

Graph construction
	•	source loading
	•	node creation
	•	stable IDs
	•	edge linking
	•	normalization

Diagnostics
	•	duplicate route errors
	•	missing schema errors
	•	invalid permission references
	•	unknown job/event/cache references
	•	malformed configs

Projections
	•	routes projection correctness
	•	feature/schema/job/event/cache projection correctness
	•	deterministic output
	•	build manifest/integrity output

CLI
	•	compile graph
	•	inspect graph
	•	inspect node
	•	inspect dependencies/dependents
	•	verify graph
	•	inspect impact
	•	migrate specs

Impact analysis
	•	file → node mapping
	•	node → affected features
	•	node → affected projections
	•	risk scoring basics
	•	recommended tests/verification

Incremental compilation
	•	compile single feature
	•	compile affected outputs
	•	stale build prevention

Extension system
	•	registration
	•	pass execution
	•	projection contribution
	•	inspection output

Migrations
	•	outdated spec detection
	•	dry-run codemod
	•	deterministic write mode
	•	diagnostic emission for deprecated fields

Prefer meaningful tests over theater.

⸻

Integration requirements

Existing runtime compatibility

Preserve current Foundry behavior where practical.

If a compatibility bridge is needed so old generated index locations continue to work temporarily, that is acceptable.

But the new canonical architecture must clearly be:
	•	source files
	•	compiled graph
	•	emitted projections
	•	runtime loads projections

Existing command compatibility

Keep existing inspect/generate/verify commands working where possible, but begin routing their logic through the graph/compiler foundation.

Where old commands are superseded, document the transition clearly.

Existing file structure

Do not require every existing app to be rewritten immediately.
Add migration paths and compatibility where practical.

⸻

Suggested internal architecture additions

Codex may introduce abstractions like:
	•	ApplicationGraph
	•	GraphCompiler
	•	CompilerPass
	•	DiscoveryPass
	•	NormalizePass
	•	LinkPass
	•	ValidatePass
	•	EnrichPass
	•	EmitPass
	•	AnalyzePass
	•	Diagnostic
	•	DiagnosticBag
	•	ProjectionEmitter
	•	ImpactAnalyzer
	•	CompilerExtension
	•	SpecMigrator

Only introduce abstractions that improve clarity, determinism, and testability.

Do not build an academic cathedral with seventeen abstract factories wearing monocles.

⸻

New CLI surface to add

Implement at least:

php vendor/bin/foundry compile graph
php vendor/bin/foundry compile graph --json
php vendor/bin/foundry compile graph --feature=<feature>
php vendor/bin/foundry compile graph --changed-only

php vendor/bin/foundry inspect graph --json
php vendor/bin/foundry inspect build --json
php vendor/bin/foundry inspect node <node-id> --json
php vendor/bin/foundry inspect dependencies <node-id> --json
php vendor/bin/foundry inspect dependents <node-id> --json
php vendor/bin/foundry inspect impact <node-id> --json
php vendor/bin/foundry inspect impact --file=<path> --json
php vendor/bin/foundry inspect affected-tests <node-id> --json
php vendor/bin/foundry inspect affected-features <node-id> --json
php vendor/bin/foundry inspect extensions --json
php vendor/bin/foundry inspect migrations --json

php vendor/bin/foundry verify graph --json

php vendor/bin/foundry migrate specs --dry-run
php vendor/bin/foundry migrate specs --write

Support --json consistently for inspection and verification commands.

⸻

Example and demo requirements

Update or add example material demonstrating:

Example A — simple app compile

Show:
	•	feature source files
	•	compiled graph
	•	projections
	•	diagnostics output

Example B — impact analysis

Show:
	•	change to one feature/schema
	•	affected nodes
	•	affected tests
	•	affected projections

Example C — migration example

Show:
	•	outdated manifest
	•	codemod dry run
	•	migrated output

Example D — extension hook example

Show:
	•	minimal compiler extension contributing a node or pass

⸻

Performance requirements

Compile-time expectations

Compilation may introduce a build step. That is acceptable.

However:
	•	full compile should remain reasonably fast
	•	incremental compile should exist
	•	compile pipeline must be efficient and testable

Runtime expectations

The compiler layer should improve or at least preserve runtime performance by moving more work from runtime to build time.

Runtime must continue to prefer precomputed projections over source scanning or dynamic discovery.

⸻

Non-goals for Phase 0

Do not try to fully implement:
	•	billing
	•	workflows
	•	orchestration
	•	OpenAPI export
	•	visual inspect UI
	•	all future extensions

Those are Phase 1–3 and beyond.

This phase is about the substrate they should sit on.

It is acceptable to include minimal hooks that make those future systems easier.

⸻

Deliverables

Produce:
	1.	full implementation of Foundry Phase 0 semantic compiler
	2.	canonical application graph artifacts
	3.	typed IR and compiler pass architecture
	4.	projection emitters for runtime indexes
	5.	structured diagnostics system
	6.	graph inspection CLI
	7.	impact analysis system
	8.	incremental compilation support
	9.	extension foundation
	10.	spec migration/codemod foundation
	11.	updated documentation
	12.	updated examples/demos
	13.	extremely high automated test coverage

⸻

Final instruction

Build Foundry Phase 0 so the framework becomes a true LLM-first application compiler.

The important shift is this:
	•	source manifests and schemas are the authored truth
	•	the semantic compiler builds a canonical application graph
	•	projections are emitted from that graph
	•	runtime reads projections
	•	inspection, verification, docs, tests, and future tooling all operate over the graph

This phase should make everything that comes after cleaner, safer, and more powerful.

Do not optimize for cleverness.
Optimize for:
	•	explicitness
	•	determinism
	•	inspectability
	•	graph stability
	•	runtime clarity
	•	future extensibility
	•	very high test coverage

Foundry should come out of this phase feeling less like “a framework with generated files” and more like “a web application compiler with a fast runtime.”

⸻

That’s the Phase 0 spec.

My honest recommendation after this: implement Phase 0 first, then lightly revise Phases 1–3 only where needed so they target the graph/compiler APIs instead of directly inventing their own parsing/emission logic. That should keep the re-speccing modest instead of apocalyptic.













================================================================================================
================================================================================================
















Implemented Phase 0 end-to-end with a new semantic compiler foundation and graph-driven runtime projections.

**What I added**
- Canonical graph compiler subsystem with typed IR nodes, edges, pass pipeline, diagnostics, projections, impact analysis, incremental planning, extension registry, and spec migration foundations:
  - [GraphCompiler.php](/Users/lo_fye/code/foundry/src/Compiler/GraphCompiler.php)
  - [ApplicationGraph.php](/Users/lo_fye/code/foundry/src/Compiler/ApplicationGraph.php)
  - [DiscoveryPass.php](/Users/lo_fye/code/foundry/src/Compiler/Passes/DiscoveryPass.php)
  - [NormalizePass.php](/Users/lo_fye/code/foundry/src/Compiler/Passes/NormalizePass.php)
  - [LinkPass.php](/Users/lo_fye/code/foundry/src/Compiler/Passes/LinkPass.php)
  - [ValidatePass.php](/Users/lo_fye/code/foundry/src/Compiler/Passes/ValidatePass.php)
  - [EmitPass.php](/Users/lo_fye/code/foundry/src/Compiler/Passes/EmitPass.php)
  - [ImpactAnalyzer.php](/Users/lo_fye/code/foundry/src/Compiler/Analysis/ImpactAnalyzer.php)
  - [SpecMigrator.php](/Users/lo_fye/code/foundry/src/Compiler/Migration/SpecMigrator.php)
  - [ExtensionRegistry.php](/Users/lo_fye/code/foundry/src/Compiler/Extensions/ExtensionRegistry.php)

- Build artifact model under `app/.foundry/build` with:
  - graph JSON/PHP
  - projections
  - compile manifest
  - integrity hashes
  - diagnostics latest

- Projection emission now graph-derived, with compatibility mirrors in `app/generated/*` (including new `query_index.php`):
  - [CoreProjectionEmitters.php](/Users/lo_fye/code/foundry/src/Compiler/Projection/CoreProjectionEmitters.php)

- New CLI surface implemented:
  - `compile graph`
  - `inspect graph|build|node|dependencies|dependents|impact|affected-tests|affected-features|extensions|migrations`
  - `verify graph`
  - `migrate specs --dry-run|--write`
  - Command wiring in [Application.php](/Users/lo_fye/code/foundry/src/CLI/Application.php)
  - New commands in `src/CLI/Commands/*Graph*` and `MigrateSpecsCommand.php`

- Existing flows integrated:
  - `generate indexes` now routes through compiler via [IndexGenerator.php](/Users/lo_fye/code/foundry/src/Generation/IndexGenerator.php)
  - runtime and loader prefer compiled build projections with compatibility fallback:
    - [RuntimeFactory.php](/Users/lo_fye/code/foundry/src/Core/RuntimeFactory.php)
    - [FeatureLoader.php](/Users/lo_fye/code/foundry/src/Feature/FeatureLoader.php)
  - legacy impact commands now graph-backed:
    - [ImpactCommand.php](/Users/lo_fye/code/foundry/src/CLI/Commands/ImpactCommand.php)

- Docs/workflow/examples updated:
  - [README.md](/Users/lo_fye/code/foundry/README.md)
  - [ARCHITECTURE.md](/Users/lo_fye/code/foundry/ARCHITECTURE.md)
  - [FEATURE_SPEC.md](/Users/lo_fye/code/foundry/FEATURE_SPEC.md)
  - [docs/semantic-compiler-phase0.md](/Users/lo_fye/code/foundry/docs/semantic-compiler-phase0.md)
  - [examples/phase0/README.md](/Users/lo_fye/code/foundry/examples/phase0/README.md)

- Test coverage added for compiler/CLI/impact/extensions/migrations:
  - [GraphCompilerTest.php](/Users/lo_fye/code/foundry/tests/Unit/GraphCompilerTest.php)
  - [CLIGraphCommandsTest.php](/Users/lo_fye/code/foundry/tests/Integration/CLIGraphCommandsTest.php)
  - [ImpactAnalyzerTest.php](/Users/lo_fye/code/foundry/tests/Unit/ImpactAnalyzerTest.php)
  - [ExtensionRegistryTest.php](/Users/lo_fye/code/foundry/tests/Unit/ExtensionRegistryTest.php)
  - [SpecMigratorTest.php](/Users/lo_fye/code/foundry/tests/Unit/SpecMigratorTest.php)

**Validation**
- Ran full suite: `vendor/bin/phpunit`
- Result: `OK` (127 tests, 411 assertions, 10 skipped)

18 files changed
+321
-62
Undo
.gitignore
ARCHITECTURE.md
FEATURE_SPEC.md
README.md
src/CLI/Application.php
src/CLI/CommandContext.php
src/CLI/Commands/ImpactCommand.php
src/CLI/Commands/InitAppCommand.php
src/CLI/Commands/InspectGraphCommand.php
src/Compiler/GraphCompiler.php
src/Compiler/GraphEdge.php
src/Compiler/Projection/GenericProjectionEmitter.php
src/Core/RuntimeFactory.php
src/Feature/FeatureLoader.php
src/Generation/ContextManifestGenerator.php
tests/Integration/ExamplesStructureTest.php
tests/Unit/CLICommandMatchesTest.php
tests/Unit/GraphCompilerTest.php

Worked 39 minutes 38 seconds

============================================================

Please try to get coverage above 90%, and then we'll move on to Phase 1

-----------

Yes. Coverage is now above 90%.

I added targeted tests in:
- [CoverageBoostCoreTest.php](/Users/lo_fye/code/foundry/tests/Unit/CoverageBoostCoreTest.php)
- [CoverageBoostCompilerTest.php](/Users/lo_fye/code/foundry/tests/Unit/CoverageBoostCompilerTest.php)

Validation results:
- `php vendor/bin/phpunit`: `138` tests, `581` assertions, passing (with skips)
- `XDEBUG_MODE=coverage php vendor/bin/phpunit --coverage-text`:
  - `Lines: 90.94% (4539/4991)`
  - `Methods: 75.87%`
  - `Classes: 52.98%`

Ready to move on to Phase 1.
