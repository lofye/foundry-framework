Master Spec 19K — Canonical Graph Specification & Integrity Upgrade

Preface

Foundry already includes a canonical application graph and a semantic compiler pipeline that emits graph state, projections, diagnostics, and manifests. The graph is already central to runtime projections, verification, inspection, impact analysis, migration planning, and extension hooks.  ￼  ￼

However, the current graph remains partially implicit:
	•	node semantics are strongly typed only at the class/type-name level
	•	most semantic detail lives in untyped payload arrays
	•	edge legality and meaning are not centrally declared
	•	graph verification is currently focused more on artifact integrity than graph integrity
	•	some graph deserialization behavior is permissive in ways that can hide schema drift or corruption, such as falling back to FeatureNode on unknown node types.  ￼  ￼  ￼  ￼

This spec formalizes the graph as a first-class contract, upgrades graph integrity guarantees, and evolves the graph to better support:
	•	deterministic verification
	•	observability
	•	temporal comparison
	•	impact analysis
	•	multi-agent coordination
	•	LLM-safe reasoning over the system

This spec does not replace the existing graph.

It hardens and formalizes it.

⸻

Goals
	1.	Define the graph as an explicit formal contract, not just an internal data structure.
	2.	Introduce a central registry for legal node types and edge types.
	3.	Add graph-level invariants and integrity verification.
	4.	Remove silent fallback behavior that can hide graph schema errors.
	5.	Add richer graph semantics for ownership, execution, and observability.
	6.	Prepare the graph for future temporal diffing and multi-agent subgraph coordination.

⸻

Non-Goals
	1.	Do not rewrite the entire compiler architecture.
	2.	Do not remove the current compile passes.
	3.	Do not eliminate payload arrays entirely.
	4.	Do not require every node subtype to become its own deep class hierarchy beyond what is useful.
	5.	Do not break existing graph consumers unnecessarily; this spec should include migration/compatibility handling.

⸻

Current-State Findings

Existing strengths
	•	ApplicationGraph already stores:
	•	graphVersion
	•	frameworkVersion
	•	compiledAt
	•	sourceHash
	•	metadata
	•	nodes
	•	edges  ￼
	•	GraphCompiler already version-controls the graph and emits a canonical graph artifact.  ￼
	•	LinkPass already creates a rich topology from feature definitions.  ￼
	•	ValidatePass already performs meaningful domain validation.  ￼

Current weaknesses
	•	no formal node/edge schema registry
	•	permissive deserialization fallback in NodeFactory
	•	no centralized graph invariant enforcement
	•	graph verifier focuses mostly on files/artifacts, not graph structure
	•	edge semantics are procedural, not declarative
	•	graph does not yet explicitly model ownership classes, subgraphs, or runtime evidence hooks

⸻

Required Deliverables

1. Add a Formal Graph Specification Layer in Code

Create a graph specification subsystem, for example under something like:

src/Compiler/GraphSpec/

This must define:
	•	legal node types
	•	legal edge types
	•	per-type required metadata
	•	compatibility/version rules
	•	allowed source/target combinations
	•	invariant rules

This is the in-code counterpart to the human-facing graph specification doc.

⸻

2. Introduce a Central Node Type Registry

The system must define a canonical node-type registry for all graph node types currently in use, including at minimum those already represented in Nodes.php, such as:
	•	feature
	•	route
	•	schema
	•	permission
	•	query
	•	event
	•	job
	•	cache
	•	scheduler
	•	webhook
	•	test
	•	context_manifest
	•	auth
	•	rate_limit
	•	pipeline_stage
	•	guard
	•	interceptor
	•	execution_plan
	•	and the additional resource/generation-oriented node types already present.  ￼

For each node type, declare:
	•	canonical type name
	•	semantic category
	•	whether it is compile-time only, runtime-relevant, or both
	•	required payload keys
	•	optional payload keys
	•	whether it participates in execution topology
	•	whether it participates in ownership topology
	•	graph compatibility range(s)

⸻

3. Introduce a Central Edge Type Registry

Add a formal edge taxonomy.

Each edge type must declare:
	•	canonical type name
	•	semantic class
	•	allowed source node types
	•	allowed target node types
	•	whether multiplicity is one-to-one, one-to-many, many-to-many, or unconstrained
	•	whether payload is permitted
	•	required payload keys when payload is used
	•	whether the edge is:
	•	dependency
	•	ownership
	•	execution
	•	invalidation
	•	publication/subscription
	•	observational
	•	structural only

Examples of currently implicit edge types to formalize include:
	•	feature_to_route
	•	feature_to_input_schema
	•	feature_to_output_schema
	•	feature_to_permission
	•	feature_to_query
	•	feature_to_event_emit
	•	feature_to_event_subscribe
	•	feature_to_job_dispatch
	•	feature_to_cache_invalidation
	•	feature_to_scheduler_task
	•	feature_to_webhook
	•	feature_to_test
	•	feature_to_context_manifest
	•	feature_to_auth_config
	•	feature_to_rate_limit
	•	event_publisher_to_subscriber  ￼

This registry becomes the authoritative source of legal graph connectivity.

⸻

4. Add Graph Integrity Verification

Add a dedicated graph integrity verifier, either as:
	•	an expansion of GraphVerifier
	•	or a separate GraphIntegrityVerifier

It must verify at minimum:

A. Node integrity
	•	node IDs are unique
	•	node type is recognized
	•	required payload fields exist
	•	payload does not violate type rules
	•	graph compatibility is valid

B. Edge integrity
	•	edge IDs are unique
	•	edge type is recognized
	•	from and to nodes both exist
	•	source/target type pairing is legal
	•	required payload keys exist
	•	multiplicity/cardinality rules are satisfied

C. Structural integrity
	•	no orphan execution-plan nodes
	•	no orphan guard/interceptor nodes
	•	no route nodes without owning features
	•	no execution edges pointing into impossible node categories
	•	no duplicate route ownership conflicts beyond what is intentionally modeled and diagnosed

D. Compatibility integrity
	•	graph version and node compatibility ranges are consistent
	•	no deserialized node with unknown type silently coerces to another type

Add a CLI command:

foundry verify graph-integrity --json

And include graph integrity in:

foundry doctor --graph

⸻

5. Remove Silent Unknown-Type Fallback

NodeFactory::fromArray() must no longer silently default unknown node types to FeatureNode.  ￼

Replace this with deterministic failure behavior, such as:
	•	UnknownGraphNodeType exception
	•	or a specific placeholder/error node type only if intentionally designed and always surfaced as an error

This is mandatory.

Silent coercion is incompatible with Foundry’s determinism and inspectability goals.

⸻

6. Strengthen ApplicationGraph

Upgrade ApplicationGraph so it is not only a container, but a more trustworthy graph contract object.

Add capabilities such as:

A. Safe edge insertion

Optional modes or dedicated methods that verify:
	•	source node exists
	•	target node exists
	•	edge type is legal
	•	duplicate illegal edges are prevented

B. Adjacency indexes

Maintain or compute efficient adjacency structures for:
	•	outgoing edges by node
	•	incoming edges by node
	•	edges by type
	•	nodes by category

C. Subgraph extraction

Add first-class support for extracting:
	•	feature-scoped subgraphs
	•	execution subgraphs
	•	ownership subgraphs
	•	runtime/observability subgraphs

This will directly support future multi-agent coordination and targeted inspection.

D. Stable graph fingerprinting

Add graph fingerprints for:
	•	entire graph
	•	subgraphs
	•	node payload structure
	•	edge topology

This will support temporal comparison and regression tracking.

⸻

7. Add Ownership Topology

Right now many relationships are dependency-like, but ownership is not clearly modeled as a first-class concept.

Introduce explicit ownership semantics, either via:
	•	ownership edges
	•	or declared ownership metadata interpreted by the graph spec layer

At minimum, model ownership for:
	•	feature → execution plan
	•	feature → auth config
	•	feature → rate limit
	•	feature → tests
	•	feature → context manifest
	•	feature → generated runtime projections
	•	feature → resource/admin/api resource surfaces where applicable

This matters for:
	•	impact analysis
	•	bug localization
	•	safe auto-fixing
	•	multi-agent partitioning

⸻

8. Add Execution Semantics to the Graph Contract

Because Foundry already has:
	•	pipeline stages
	•	guards
	•	interceptors
	•	execution plans

the graph spec must explicitly distinguish:
	•	structural nodes
	•	execution nodes
	•	policy nodes
	•	integration nodes
	•	observational nodes

Execution-related edges must be formalized separately from generic dependencies.

This will make runtime observability much easier to map back onto the graph later.

⸻

9. Add Observability Hook Points to the Graph

To support Spec 19J cleanly, the graph must be able to host runtime evidence.

Add optional graph concepts or metadata for:
	•	traceable execution nodes
	•	profileable execution boundaries
	•	stable runtime correlation IDs for nodes/edges
	•	run/build association metadata

This does not require actual profiling implementation inside 19K, but it must shape the graph so that observability can map back cleanly later.

⸻

10. Add Graph Schema Serialization Contract

Formalize the JSON graph artifact schema.

The graph JSON must include:
	•	graph metadata
	•	node array
	•	edge array
	•	summary
	•	graph spec version
	•	optional integrity section
	•	optional compatibility section

Add a machine-readable schema for the graph JSON itself, such as:
	•	JSON Schema
	•	or equivalent internal schema definition

This makes the graph safer for:
	•	LLM consumption
	•	external tooling
	•	diffing
	•	docs generation
	•	future Pro features

⸻

11. Add Graph Version Migration Rules

Because the graph is now becoming more formal, graph version changes must become more disciplined.

Add explicit graph-version migration support:
	•	old graph JSON can be upgraded
	•	incompatible graph artifacts fail deterministically
	•	node/edge compatibility rules are respected

This should align with the existing compiler migration philosophy, not bypass it.  ￼

⸻

12. Add a Graph Explanation / Inspection Surface

Add or expand commands such as:

foundry inspect graph-spec --json
foundry inspect node-types --json
foundry inspect edge-types --json
foundry inspect subgraph <feature> --json
foundry inspect graph-integrity --json

These should be machine-readable and human-meaningful.

This will help both developers and LLMs reason about the graph as a formal system.

⸻

13. Required Code Changes

At minimum, this spec must touch:
	•	ApplicationGraph
	•	GraphEdge
	•	NodeFactory
	•	graph verification logic
	•	compiler passes or supporting layers where graph legality is currently implicit
	•	docs generation / inspect surfaces as needed

It may also introduce:
	•	graph spec registry classes
	•	node/edge metadata classes
	•	graph integrity diagnostics
	•	schema serialization helpers

⸻

14. Backward Compatibility Requirements

This spec must preserve current graph-driven behavior where possible.

Rules:
	•	existing graph consumers should continue working after a migration layer or compatibility shim
	•	graph version must be bumped
	•	old artifacts must either migrate cleanly or fail with explicit guidance
	•	no silent semantic drift

⸻

15. Acceptance Criteria

This spec is complete only when all of the following are true:
	1.	Foundry has a central node-type registry.
	2.	Foundry has a central edge-type registry.
	3.	Unknown node types no longer silently coerce to FeatureNode.
	4.	Graph integrity verification exists and is CLI-accessible.
	5.	ApplicationGraph supports stronger integrity guarantees and subgraph extraction.
	6.	Ownership and execution semantics are explicit in the graph contract.
	7.	Graph JSON has a formalized serialization contract.
	8.	Graph versioning/migration rules are explicit.
	9.	Docs/inspect output can explain the graph structure in machine-readable terms.
	10.	Existing graph-based compile/inspect/verify flows still work or migrate cleanly.

⸻

Recommended Order
	1.	Define graph-spec data structures
	2.	add node-type registry
	3.	add edge-type registry
	4.	remove silent unknown-type fallback
	5.	add graph-integrity verifier
	6.	strengthen ApplicationGraph
	7.	add ownership/execution semantics
	8.	add graph serialization schema
	9.	add inspect surfaces
	10.	update docs

==========================================================================================

RESULT

Implemented the 19K graph-contract upgrade across the compiler, verifier, CLI, and docs. The core work lives in [src/Compiler/GraphSpec](\/Users/lo_fye/code/foundry-framework/src/Compiler/GraphSpec), with [ApplicationGraph.php](/Users/lo_fye/code/foundry-framework/src/Compiler/ApplicationGraph.php), [Nodes.php](/Users/lo_fye/code/foundry-framework/src/Compiler/IR/Nodes.php), [GraphVerifier.php](/Users/lo_fye/code/foundry-framework/src/Compiler/GraphVerifier.php), [InspectGraphCommand.php](/Users/lo_fye/code/foundry-framework/src/CLI/Commands/InspectGraphCommand.php), [VerifyGraphCommand.php](/Users/lo_fye/code/foundry-framework/src/CLI/Commands/VerifyGraphCommand.php), and [DoctorCommand.php](/Users/lo_fye/code/foundry-framework/src/CLI/Commands/DoctorCommand.php) wired to it. The graph is now explicitly versioned at `graph_version = 2` with `graph_spec_version = 1`, unknown node fallback to `FeatureNode` is gone, edge legality is registry-backed, integrity checks are first-class, subgraph extraction/fingerprints are available, and the JSON artifact now carries graph/spec/integrity/compatibility/observability metadata.

I also aligned the human-facing contract in [docs/graph-spec.md](/Users/lo_fye/code/foundry-framework/docs/graph-spec.md) and expanded coverage in [tests/Integration/CLIGraphCommandsTest.php](/Users/lo_fye/code/foundry-framework/tests/Integration/CLIGraphCommandsTest.php), [tests/Unit/GraphSpecRegistryTest.php](/Users/lo_fye/code/foundry-framework/tests/Unit/GraphSpecRegistryTest.php), and [tests/Unit/NodeFactoryTest.php](/Users/lo_fye/code/foundry-framework/tests/Unit/NodeFactoryTest.php). One last gap I found during verification was that `verify graph-integrity --json` wasn’t surfacing version metadata; that’s fixed now in [GraphIntegrityReport.php](/Users/lo_fye/code/foundry-framework/src/Compiler/GraphSpec/GraphIntegrityReport.php).

Verification passed with:
- `php bin/foundry compile graph --json`
- `php bin/foundry verify graph --json`
- `php bin/foundry verify graph-integrity --json`
- `php bin/foundry doctor --graph --json`

Focused PHPUnit also passed:
- `php vendor/bin/phpunit tests/Integration/CLIGraphCommandsTest.php tests/Unit/FrameworkDoctorTest.php tests/Unit/GraphCompilerTest.php tests/Unit/GraphSpecRegistryTest.php tests/Unit/NodeFactoryTest.php`

I didn’t run the full repository PHPUnit suite, only the graph-related slice above.

34 files changed
+2467
-390
Undo
docs/graph-spec.md
src/CLI/Commands/DoctorCommand.php
src/CLI/Commands/InspectGraphCommand.php
src/CLI/Commands/VerifyGraphCommand.php
src/Compiler/ApplicationGraph.php
src/Compiler/CompileResult.php
src/Compiler/Extensions/CoreCompilerExtension.php
src/Compiler/Extensions/FoundationCompilerExtension.php
src/Compiler/Extensions/IntegrationCompilerExtension.php
src/Compiler/Extensions/PlatformCompilerExtension.php
src/Compiler/GraphCompiler.php
src/Compiler/GraphSpec/CanonicalGraphSpecification.php
src/Compiler/GraphSpec/EdgeTypeDefinition.php
src/Compiler/GraphSpec/GraphArtifactMigrator.php
src/Compiler/GraphSpec/GraphCompatibility.php
src/Compiler/GraphSpec/GraphIntegrityReport.php
src/Compiler/GraphSpec/GraphIntegrityVerifier.php
src/Compiler/GraphSpec/GraphSpecification.php
src/Compiler/GraphSpec/IllegalGraphEdge.php
src/Compiler/GraphSpec/NodeTypeDefinition.php
src/Compiler/GraphSpec/UnknownGraphEdgeType.php
src/Compiler/GraphSpec/UnknownGraphNodeType.php
src/Compiler/GraphVerifier.php
src/Compiler/IR/Nodes.php
src/Compiler/Passes/EmitPass.php
src/Doctor/Checks/GraphIntegrityCheck.php
src/Extensions/Demo/DemoCapabilityExtension.php
src/Support/ApiSurfaceRegistry.php
tests/Integration/CLIGraphCommandsTest.php
tests/Unit/CLICommandMatchesTest.php
tests/Unit/CompatibilityCheckerTest.php
tests/Unit/GraphCompilerTest.php
tests/Unit/GraphSpecRegistryTest.php
tests/Unit/NodeFactoryTest.php

------------------------------------------------------------------------------------------

Please run the full repository PHPUnit suite to ensure >= 90% test coverage of the entire codebase.

------------------------------------------------------------------------------------------

The full repository PHPUnit suite now passes, and the repo clears the `>= 90%` line-coverage bar.

I had to load Xdebug explicitly because the default Herd PHP runtime did not expose a coverage driver by default. After fixing three full-suite regressions and adding focused coverage around the new graph-contract code, the final run succeeded with `372` tests, `2938` assertions, `10` skips, and repository-wide coverage of `90.15%` lines, `70.79%` methods, and `48.39%` classes.

The small fixes needed to get the suite green were in [src/CLI/CliSurfaceVerifier.php](/Users/lo_fye/code/foundry-framework/src/CLI/CliSurfaceVerifier.php) and [tests/Fixtures/CustomUpgradeExtension.php](/Users/lo_fye/code/foundry-framework/tests/Fixtures/CustomUpgradeExtension.php). The coverage lift came from new tests in [tests/Unit/GraphIntegrityVerifierTest.php](/Users/lo_fye/code/foundry-framework/tests/Unit/GraphIntegrityVerifierTest.php) and [tests/Unit/ApplicationGraphContractTest.php](/Users/lo_fye/code/foundry-framework/tests/Unit/ApplicationGraphContractTest.php).

Command used for the authoritative coverage run:
```bash
XDEBUG_MODE=coverage php -d zend_extension=/opt/homebrew/lib/php/pecl/20240924/xdebug.so vendor/bin/phpunit --coverage-text
```

4 files changed
+772
-2
Undo
src/CLI/CliSurfaceVerifier.php
tests/Fixtures/CustomUpgradeExtension.php
tests/Unit/ApplicationGraphContractTest.php
tests/Unit/GraphIntegrityVerifierTest.php
