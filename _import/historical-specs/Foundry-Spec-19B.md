# Operator Notes (FOR CODEX EXECUTION)

## What to give Codex
- Entire file

## Verify
- CLI command is thin (delegates to engine)
- Target resolution is deterministic and well-prioritized
- ExplainEngine orchestrates without leaking responsibilities
- ExplanationPlan is fully populated and renderer-ready

## Anti-patterns to avoid
- CLI command performing analysis or graph traversal
- Target resolver doing fuzzy matching too early
- Engine directly rendering output
- ExplanationPlan being loosely structured or inconsistent

## Green-light criteria
- Deterministic explain output across runs
- Clean orchestration flow (resolve → collect → analyze → assemble → render)
- Stable JSON + text output contracts
- ≥90% test coverage across all layers

Please fix any issues and ensure that all Green-light criteria are met.

---

In total, you will be receiving 6 specs in order to fully implement `foundry explain`.
They are:
Spec 19A → Architecture (what it is)
Spec 19B → Implementation (how it works end-to-end)
Spec 19C → UX contract (what it feels like)
Spec 19D → Foundation slice (safe starting point)
Spec 19E → Intelligence layer (collectors + analyzers)
Spec 19F → Final polish (rendering + contributors + docs)

-----

Spec 19B - Implementation (how it works end-to-end)

Preface

Foundry’s canonical graph, compiler outputs, manifests, schemas, and execution pipeline make it possible to explain application structure deterministically.

Spec 19A introduced foundry explain, a CLI command that turns Foundry’s compiled architecture into clear, structured explanations for humans and tools.

This command must not depend on an LLM. It must derive its explanations deterministically from:
	•	the canonical application graph
	•	compiled projections
	•	diagnostics metadata
	•	pipeline metadata
	•	command metadata
	•	extension metadata
	•	schema/config metadata
	•	documentation metadata where appropriate

The result should be useful for:
	•	developers learning the framework
	•	app maintainers debugging behavior
	•	extension authors understanding integration points
	•	future AI-assisted workflows
	•	docs generation and tooling

All new code must maintain ≥ 90% automated test coverage.

⸻

Goals

Spec 19B must:
	1.	introduce a stable foundry explain command
	2.	resolve architectural targets deterministically
	3.	explain framework/app subjects using graph + projection metadata
	4.	provide both human-readable and machine-readable output
	5.	support future extension/app contributions to explanation output
	6.	remain fully deterministic and testable

⸻

CLI Requirements

Provide a command with a public surface similar to:

foundry explain <target>
foundry explain <target> --json
foundry explain <target> --markdown
foundry explain <target> --deep
foundry explain <target> --type=<kind>

Supported subject kinds should include at least:
	•	feature
	•	route
	•	command
	•	pipeline_stage
	•	workflow
	•	event
	•	job
	•	schema
	•	extension

Codex may choose the exact internal names, but the public behavior must be coherent.

⸻

1. Introduce Explain Target Resolution

Implement a target resolution subsystem that:
	•	parses typed selectors such as:
	•	feature:thresholds
	•	event:threshold.created
	•	workflow:streak.update
	•	supports exact node IDs and aliases
	•	supports route/command lookup
	•	detects and reports ambiguity clearly
	•	prefers explicit typed resolution over fuzzy matching

Add a normalized target DTO such as:

ExplainTarget

and a canonical resolved subject DTO such as:

ExplainSubject

Ambiguous targets must produce structured, actionable errors.

⸻

2. Introduce Explain Engine

Implement an ExplainEngine that orchestrates:
	1.	target resolution
	2.	context collection
	3.	subject analysis
	4.	contributor hooks
	5.	explanation plan assembly
	6.	rendering

The engine must not render directly and must not couple CLI code to graph traversal logic.

It should expose a method conceptually equivalent to:

explain(ExplainTarget $target, ExplainOptions $options): ExplanationPlan


⸻

3. Introduce Explain Options

Define an options object for explain behavior, supporting at least:
	•	output format
	•	deep mode
	•	include diagnostics
	•	include graph neighbors
	•	include execution flow
	•	include related commands
	•	include related docs

This options model must remain deterministic and serializable where practical.

⸻

4. Introduce Context Collectors

Implement context collectors that gather structured information from the canonical graph and compiled projections.

At minimum, provide collectors for:
	•	graph neighborhood
	•	pipeline metadata
	•	CLI command metadata
	•	workflow metadata
	•	event metadata
	•	schema/config metadata
	•	extension metadata
	•	diagnostics metadata
	•	docs metadata where available

Collectors should return normalized data structures and must not emit presentation text.

⸻

5. Introduce Subject Analyzers

Implement subject analyzers for at least:
	•	feature
	•	route
	•	command
	•	pipeline stage
	•	workflow
	•	event
	•	job
	•	schema
	•	extension

Each analyzer must:
	•	determine whether it supports a subject
	•	produce normalized explanation sections
	•	remain deterministic
	•	avoid direct rendering concerns

These analyzers should produce things like:
	•	summary inputs
	•	dependency relationships
	•	execution flow relationships
	•	related commands
	•	related diagnostics
	•	related docs

⸻

6. Introduce Explanation Plan

Create a stable intermediate object, ExplanationPlan, which acts as the contract between analysis and rendering.

It must contain at minimum:
	•	subject identity
	•	summary
	•	structured sections
	•	dependency/dependent relationships
	•	execution flow data
	•	diagnostics references
	•	related commands
	•	related docs
	•	metadata

This object must be suitable for both text and JSON rendering.

⸻

7. Introduce Deterministic Summary Generation

The summary in foundry explain must be deterministic and template-driven, not LLM-generated.

Implement summary generation using rules based on subject kind and collected context.

Summaries should be concise, useful, and consistent.

⸻

8. Introduce Renderers

Implement renderers for at least:
	•	text
	•	JSON
	•	markdown

Renderer logic must be fully separated from graph analysis logic.

Text renderer
Human-readable CLI output.

JSON renderer
Stable machine-readable output suitable for:
	•	docs generation
	•	IDE tooling
	•	future LLM integrations

Markdown renderer
Optional but valuable for docs and exported reports.

⸻

9. Introduce Explain Contributors

Add an extension/app contribution mechanism for explanation output.

Provide an interface such as:

ExplainContributorInterface

Contributors must be able to add sections or context for supported subjects.

This allows extensions/apps to enrich foundry explain without modifying core.

⸻

10. Include Execution Flow Where Appropriate

For subjects where it makes sense, foundry explain should show graph-derived execution flow, such as:
	•	route
	•	guards
	•	pipeline stages
	•	feature action
	•	emitted events
	•	workflows/jobs
	•	notifications

This is not runtime tracing. It is compiled architecture explanation.

⸻

11. Integrate Diagnostics

If diagnostics metadata exists for the subject, surface it in the explanation.

At minimum:
	•	related diagnostics
	•	known structural issues
	•	missing relationships or invalid bindings if already captured by metadata

Do not make foundry explain depend on re-running all diagnostics unless Codex determines that is required for correctness.

Prefer using existing diagnostic metadata/projections.

⸻

12. Integrate Related Commands and Docs

The explanation output should surface:
	•	related CLI commands
	•	related docs pages

where such metadata is already available.

This is especially valuable for onboarding and future docs integration.

⸻

13. Public API and Stability

foundry explain should be treated as a public CLI command once implemented.

Its JSON output should be designed carefully enough that it can become a stable machine-readable interface, even if still marked as evolving initially.

If Codex believes the JSON output should be marked experimental first, that is acceptable, but it must still be structured and deliberate.

⸻

14. Documentation

Update the docs to explain:
	•	what foundry explain does
	•	what subject kinds it supports
	•	how to use typed selectors
	•	how to use --json
	•	how apps/extensions can contribute explanation data

⸻

15. Testing Requirements

Automated test coverage must remain ≥ 90%.

Tests must cover at least:
	•	target resolution
	•	ambiguity handling
	•	subject analyzer selection
	•	context collection
	•	summary generation
	•	text rendering
	•	JSON rendering
	•	markdown rendering if implemented
	•	extension/app explanation contributors
	•	execution flow explanation for representative subject types
	•	diagnostics integration
	•	related commands/docs integration

Tests should include representative examples for:
	•	feature
	•	route
	•	workflow
	•	event
	•	pipeline stage
	•	extension

⸻

16. Deliverables

Codex must implement:
	•	foundry explain CLI command
	•	target resolution system
	•	explain engine
	•	context collectors
	•	subject analyzers
	•	explanation plan model
	•	deterministic summary generation
	•	text/json/markdown renderers
	•	contributor interface for apps/extensions
	•	docs updates
	•	automated tests

⸻

Final Instruction

foundry explain should become one of Foundry’s signature capabilities.

It must feel like the framework is explaining itself from its own architecture, not guessing.

Optimize for:
	•	determinism
	•	architectural clarity
	•	extensibility
	•	machine readability
	•	usefulness to humans
	•	usefulness to future AI tooling
	•	strong test coverage

If you want, next I can do one of two very useful follow-ups: a sample CLI UX for foundry explain with concrete before/after examples, or a proposed class/file layout so Codex has an even more mechanical implementation path.	
	
------------------------------------------------------------------------------------------

RESULT (Spec 19B)

19B is implemented on top of the 19A architecture.

The main gaps I closed were the end-to-end parts that were still thin: real docs metadata, real contributor wiring, and richer plan-driven human output. [src/Explain/ExplainArtifactCatalog.php](/Users/lo_fye/code/foundry-framework/src/Explain/ExplainArtifactCatalog.php) now exposes deterministic docs metadata from curated and generated docs pages, [src/Explain/Collectors/DocsContextCollector.php](/Users/lo_fye/code/foundry-framework/src/Explain/Collectors/DocsContextCollector.php) resolves related docs for supported subject kinds, and [src/Explain/ExplainEngine.php](/Users/lo_fye/code/foundry-framework/src/Explain/ExplainEngine.php) merges that into the `ExplanationPlan`. Contributor support is now a real hook rather than just an interface: [src/Explain/ExplainEngineFactory.php](/Users/lo_fye/code/foundry-framework/src/Explain/ExplainEngineFactory.php) and [src/Pro/ArchitectureExplainer.php](/Users/lo_fye/code/foundry-framework/src/Pro/ArchitectureExplainer.php) accept `ExplainContributorInterface` instances, and [src/Support/ApiSurfaceRegistry.php](/Users/lo_fye/code/foundry-framework/src/Support/ApiSurfaceRegistry.php) classifies that contributor interface as an extension hook.

I also made the renderers fully useful for 19B instead of just skeletal. [src/Explain/Renderers/TextExplanationRenderer.php](/Users/lo_fye/code/foundry-framework/src/Explain/Renderers/TextExplanationRenderer.php) and [src/Explain/Renderers/MarkdownExplanationRenderer.php](/Users/lo_fye/code/foundry-framework/src/Explain/Renderers/MarkdownExplanationRenderer.php) now render sections, dependencies, execution flow, diagnostics, related commands, and related docs from the plan. The docs were updated in [docs/architecture-tools.md](/Users/lo_fye/code/foundry-framework/docs/architecture-tools.md), [docs/extension-author-guide.md](/Users/lo_fye/code/foundry-framework/docs/extension-author-guide.md), and [README.md](/Users/lo_fye/code/foundry-framework/README.md). Test coverage was extended in [tests/Unit/ExplainArchitectureCoverageTest.php](/Users/lo_fye/code/foundry-framework/tests/Unit/ExplainArchitectureCoverageTest.php), [tests/Unit/ExplainEngineTest.php](/Users/lo_fye/code/foundry-framework/tests/Unit/ExplainEngineTest.php), [tests/Unit/ApiSurfaceRegistryTest.php](/Users/lo_fye/code/foundry-framework/tests/Unit/ApiSurfaceRegistryTest.php), and [tests/Integration/CLIProCommandsTest.php](/Users/lo_fye/code/foundry-framework/tests/Integration/CLIProCommandsTest.php).

Verification:
- Explain-slice coverage: `90.85%` (`1380/1519`) for `src/Explain`, [src/Pro/ArchitectureExplainer.php](/Users/lo_fye/code/foundry-framework/src/Pro/ArchitectureExplainer.php), and [src/Pro/CLI/ExplainCommand.php](/Users/lo_fye/code/foundry-framework/src/Pro/CLI/ExplainCommand.php)
- `php vendor/bin/phpunit tests/Unit/ExplainEngineTest.php tests/Unit/ExplainArchitectureCoverageTest.php tests/Unit/ProAnalysisToolsTest.php tests/Unit/ApiSurfaceRegistryTest.php tests/Unit/CLICommandMatchesTest.php tests/Integration/CLIProCommandsTest.php tests/Integration/CLIApplicationTest.php`
- `php bin/foundry compile graph --json`
- `php bin/foundry verify graph --json`
- `php bin/foundry verify pipeline --json`
- `php bin/foundry verify contracts --json`

This leaves 19B in a good state: deterministic target resolution, engine/collector/analyzer pipeline, plan-backed text/json/markdown output, diagnostics and docs integration, contributor extensibility, and coverage above the required threshold.

15 files changed
+647
-7
Undo
README.md
docs/architecture-tools.md
docs/extension-author-guide.md
src/Explain/Collectors/DocsContextCollector.php
src/Explain/ExplainArtifactCatalog.php
src/Explain/ExplainEngine.php
src/Explain/ExplainEngineFactory.php
src/Explain/Renderers/MarkdownExplanationRenderer.php
src/Explain/Renderers/TextExplanationRenderer.php
src/Pro/ArchitectureExplainer.php
src/Support/ApiSurfaceRegistry.php
tests/Integration/CLIProCommandsTest.php
tests/Unit/ApiSurfaceRegistryTest.php
tests/Unit/ExplainArchitectureCoverageTest.php
tests/Unit/ExplainEngineTest.php


## Verify
- CLI command is thin (delegates to engine)
- Target resolution is deterministic and well-prioritized
- ExplainEngine orchestrates without leaking responsibilities
- ExplanationPlan is fully populated and renderer-ready

## Anti-patterns to avoid
- CLI command performing analysis or graph traversal
- Target resolver doing fuzzy matching too early
- Engine directly rendering output
- ExplanationPlan being loosely structured or inconsistent

## Green-light criteria
- Deterministic explain output across runs
- Clean orchestration flow (resolve → collect → analyze → assemble → render)
- Stable JSON + text output contracts
- ≥90% test coverage across all layers

Please fix any issues and ensure that all Green-light criteria are met.



The implementation already satisfied the layering constraints in code, so this pass was mostly about tightening proof. I re-audited [src/Pro/CLI/ExplainCommand.php](/Users/lo_fye/code/foundry-framework/src/Pro/CLI/ExplainCommand.php), [src/Explain/ExplainTargetResolver.php](/Users/lo_fye/code/foundry-framework/src/Explain/ExplainTargetResolver.php), [src/Explain/ExplainEngine.php](/Users/lo_fye/code/foundry-framework/src/Explain/ExplainEngine.php), and [src/Explain/ExplanationPlan.php](/Users/lo_fye/code/foundry-framework/src/Explain/ExplanationPlan.php): the CLI remains thin, resolution order is still exact-before-fuzzy, the engine still only assembles plans, and rendering remains separate.

The concrete change in this verification pass was stronger automated enforcement. I added explicit plan-contract assertions in [tests/Unit/ExplainEngineTest.php](/Users/lo_fye/code/foundry-framework/tests/Unit/ExplainEngineTest.php) and repeated-run determinism assertions in [tests/Unit/ExplainArchitectureCoverageTest.php](/Users/lo_fye/code/foundry-framework/tests/Unit/ExplainArchitectureCoverageTest.php). That now proves:
- deterministic explain output across repeated runs
- stable `ExplanationPlan` key shape
- stable text, markdown, and JSON rendering from the same plan

Verified with:
- `php vendor/bin/phpunit tests/Unit/ExplainEngineTest.php tests/Unit/ExplainArchitectureCoverageTest.php tests/Unit/ProAnalysisToolsTest.php tests/Unit/ApiSurfaceRegistryTest.php tests/Unit/CLICommandMatchesTest.php tests/Integration/CLIProCommandsTest.php tests/Integration/CLIApplicationTest.php`
- explain-slice coverage: `90.85%` (`1380/1519`) for `src/Explain`, [src/Pro/ArchitectureExplainer.php](/Users/lo_fye/code/foundry-framework/src/Pro/ArchitectureExplainer.php), and [src/Pro/CLI/ExplainCommand.php](/Users/lo_fye/code/foundry-framework/src/Pro/CLI/ExplainCommand.php)
- `php bin/foundry compile graph --json`
- `php bin/foundry verify graph --json`
- `php bin/foundry verify pipeline --json`
- `php bin/foundry verify contracts --json`

This verification pass is green.

2 files changed
+40
-0
Undo
tests/Unit/ExplainArchitectureCoverageTest.php
tests/Unit/ExplainEngineTest.php






