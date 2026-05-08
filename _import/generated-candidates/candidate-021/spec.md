# Spec 19A - ARCHITECTURE FOR `foundry explain`

Internal Architecture for foundry explain

The job of foundry explain

foundry explain should answer:
	•	What is this thing?
	•	How does it fit into the application?
	•	What depends on it?
	•	What does it depend on?
	•	How does execution flow through it?
	•	What contracts, guards, events, workflows, jobs, and extensions affect it?
	•	Why is it behaving this way? (within deterministic, graph-derived limits)

The crucial design choice is this:

foundry explain must be deterministic and architecture-derived, not LLM-derived.

LLMs can be layered on later, but the core command should work entirely from Foundry’s compiled artifacts and graph/projection metadata.

That makes it:
	•	inspectable
	•	testable
	•	reproducible
	•	safe for CI
	•	useful even with no AI configured

⸻

Design principle

foundry explain should be built as a query engine over the canonical application graph plus compiled projections.

Not as:
	•	runtime reflection spaghetti
	•	ad hoc text generation
	•	direct template logic inside the command

The architecture should look like this:

CLI Command
  -> Target Resolver
  -> Explain Engine
      -> Subject Analyzer
      -> Context Collectors
      -> Explain Contributors
      -> Explanation Plan
  -> Renderer

⸻

Core concepts

1. Explain Target

The user asks to explain a target, for example:

foundry explain thresholds.create
foundry explain feature:thresholds
foundry explain event:threshold.created
foundry explain workflow:streak.update
foundry explain route:POST /thresholds
foundry explain command:doctor

So the system needs a normalized target model.

Suggested DTO:

final class ExplainTarget
{
    public string $raw;
    public ?string $kind;   // feature, route, event, workflow, command, job, schema, extension, pipeline_stage
    public string $selector;
}

⸻

2. Explain Subject

After resolution, the system should produce a canonical subject:

final class ExplainSubject
{
    public string $kind;
    public string $id;
    public string $label;
    public array $graphNodeIds;
    public array $aliases;
    public array $metadata;
}

This is the thing the rest of the pipeline explains.

⸻

3. Explain Engine

This is the core orchestrator.

Suggested shape:

interface ExplainEngineInterface
{
    public function explain(ExplainTarget $target, ExplainOptions $options): ExplanationPlan;
}

Its stages:
	1.	resolve target
	2.	collect graph neighborhood
	3.	collect projections
	4.	run analyzers/contributors
	5.	assemble ExplanationPlan
	6.	render to human or JSON output

⸻

4. Explain Options

Suggested options DTO:

final class ExplainOptions
{
    public string $format; // text|json|markdown
    public bool $deep;
    public bool $includeDiagnostics;
    public bool $includeNeighbors;
    public bool $includeExecutionFlow;
    public bool $includeRelatedCommands;
    public bool $includeRelatedDocs;
}

Initial CLI flags might be:

--json
--markdown
--deep
--type=<kind>
--no-diagnostics
--no-neighbors
--no-flow

Keep the first version modest.

⸻

5. Target Resolution

This is the first major subsystem.

Suggested class:

final class ExplainTargetResolver

Responsibilities:
	•	parse prefixes like feature:thresholds
	•	support exact node ID match
	•	support alias match
	•	support route matching
	•	support command matching
	•	detect ambiguity
	•	suggest candidates when ambiguous

Important rule:

Target resolution should prefer exact typed matches over fuzzy matches.

Resolution order:
	1.	explicit kind:selector
	2.	exact node ID
	3.	exact alias
	4.	exact route/command name
	5.	controlled fuzzy fallback with ambiguity error

If ambiguous, return structured candidates, not a vague failure.

⸻

6. Context Collection

Once the subject is resolved, the engine should gather context from two places:

A. Canonical graph

This gives:
	•	incoming edges
	•	outgoing edges
	•	node type
	•	neighborhood
	•	dependency relationships

B. Compiled projections

This gives higher-level meaning:
	•	pipeline metadata
	•	CLI metadata
	•	extension metadata
	•	diagnostics metadata
	•	schema/config metadata
	•	route metadata
	•	workflow metadata
	•	event metadata

Suggested collectors:

final class GraphNeighborhoodCollector
final class PipelineContextCollector
final class CommandContextCollector
final class WorkflowContextCollector
final class EventContextCollector
final class SchemaContextCollector
final class ExtensionContextCollector
final class DiagnosticsContextCollector
final class DocsContextCollector

Not every subject needs every collector. The engine should choose intelligently by subject kind.

⸻

7. Subject Analyzers

These turn raw graph/projection data into meaningful explanation sections.

Suggested analyzers:

interface SubjectAnalyzerInterface
{
    public function supports(ExplainSubject $subject): bool;
    public function analyze(ExplainSubject $subject, ExplainContext $context, ExplainOptions $options): array;
}

Recommended built-in analyzers:
	•	FeatureSubjectAnalyzer
	•	RouteSubjectAnalyzer
	•	PipelineStageSubjectAnalyzer
	•	CommandSubjectAnalyzer
	•	WorkflowSubjectAnalyzer
	•	EventSubjectAnalyzer
	•	JobSubjectAnalyzer
	•	SchemaSubjectAnalyzer
	•	ExtensionSubjectAnalyzer

Each analyzer should produce normalized sections, not raw strings.

⸻

8. Explanation Plan

This is the most important internal output.

It should be a stable structured object that renderers consume.

Suggested shape:

final class ExplanationPlan
{
    public array $subject;          // id, kind, label
    public array $summary;          // one-paragraph deterministic explanation
    public array $sections;         // structured sections
    public array $relationships;    // dependencies, dependents, graph neighbors
    public array $executionFlow;    // guards, stages, events, jobs, workflows
    public array $diagnostics;      // related diagnostics
    public array $relatedCommands;  // relevant CLI commands
    public array $relatedDocs;      // relevant docs pages
    public array $metadata;         // version, graph snapshot, etc.
}

This object should be the contract for:
	•	text rendering
	•	JSON output
	•	future docs/website integration
	•	future LLM integrations

⸻

9. Renderers

Separate rendering completely from analysis.

Suggested renderers:

interface ExplanationRendererInterface
{
    public function render(ExplanationPlan $plan): string;
}

Implement:
	•	TextExplanationRenderer
	•	JsonExplanationRenderer
	•	MarkdownExplanationRenderer

Text output

Should be human-readable and concise.

JSON output

Should be stable and machine-readable.

This matters a lot, because foundry explain --json will eventually become very valuable for:
	•	docs generation
	•	IDE integration
	•	AI tooling
	•	debugging pipelines

⸻

10. Explain Contributors (extensibility)

This is how you future-proof it.

Extensions and apps should be able to add explanation sections.

Suggested interface:

interface ExplainContributorInterface
{
    public function supports(ExplainSubject $subject): bool;
    public function contribute(ExplainSubject $subject, ExplainContext $context, ExplainOptions $options): array;
}

Examples:
	•	an auth extension could add permission-related explanation
	•	a workflow pack could add workflow semantics
	•	an OpenAPI extension could add API exposure notes

This should plug into the engine before final plan assembly.

⸻

11. Summary generation

The summary should still be deterministic.

Do not let the first version use an LLM to produce the summary.

Instead, use rule-based templates based on subject kind.

Examples:

Feature

thresholds is a feature that owns threshold recording, related entries, and lifecycle events. It depends on account for identity and contributes routes, workflows, and notifications to the application graph.

Pipeline stage

auth is a pipeline stage that enforces authenticated access before feature execution. It runs after request normalization and before permission checks.

That gives you consistent, trustworthy summaries.

⸻

12. Execution flow support

One of the most valuable parts of foundry explain is showing execution relationships.

For targets where it makes sense, include:
	•	route -> guards -> pipeline stages -> feature action -> emitted events -> jobs/workflows -> notifications

This doesn’t need to be full runtime tracing yet. It can be graph-derived flow explanation.

That’s enough for V1 and far easier to keep deterministic.

⸻

13. Diagnostics integration

If a subject has known issues, foundry explain should surface them.

Example:

foundry explain thresholds.create

could include:
	•	duplicate route warning
	•	missing permission mapping
	•	incompatible extension hook
	•	workflow target missing

This should come from diagnostics metadata, not by running all diagnostics live unless needed.

⸻

14. Suggested output structure

A good human-readable output might look like:

Subject
  thresholds.create
  kind: route_action

Summary
  Creates a threshold entry for the authenticated user and triggers downstream milestone logic.

Execution flow
  request
  -> auth guard
  -> permission guard
  -> thresholds feature action
  -> threshold.created event
  -> streak workflow
  -> notification job

Depends on
  account
  thresholds schema
  thresholds permissions
  streak workflow

Used by
  POST /thresholds
  API docs operation createThreshold

Related diagnostics
  none

Related commands
  foundry inspect pipeline --json
  foundry doctor --json

That is tremendously useful.

⸻

15. What not to do

Do not make V1 of foundry explain do all of these at once:
	•	real runtime tracing
	•	full language-model reasoning
	•	speculative suggestions
	•	natural-language freeform inference
	•	“fix it for me” automation

Those are good future layers, but the internal architecture should first nail:

deterministic explanation from compiled architecture

That is the crown jewel.

⸻⸻⸻⸻⸻⸻⸻⸻⸻⸻⸻⸻⸻⸻⸻⸻⸻⸻⸻⸻---

RESULT (Spec 19A)

Implemented the 19A architecture layer for `foundry explain` and wired the existing command through it.

The main change is a new core explain subsystem under [src/Explain/ExplainEngine.php](/Users/lo_fye/code/foundry-framework/src/Explain/ExplainEngine.php) and [src/Explain/ExplainTargetResolver.php](/Users/lo_fye/code/foundry-framework/src/Explain/ExplainTargetResolver.php). It now has explicit DTOs for targets/options/subjects/plans, a deterministic resolver, context collectors over graph + compiled projections + diagnostics, subject analyzers, contributor/render interfaces, and stable plan output. The Pro wrapper in [src/Pro/ArchitectureExplainer.php](/Users/lo_fye/code/foundry-framework/src/Pro/ArchitectureExplainer.php) is now a compatibility adapter over that engine, and [src/Pro/CLI/ExplainCommand.php](/Users/lo_fye/code/foundry-framework/src/Pro/CLI/ExplainCommand.php) parses the initial 19A flags (`--type`, `--markdown`, `--deep`, `--no-diagnostics`, `--no-neighbors`, `--no-flow`) while preserving Pro gating.

I also added focused coverage in [tests/Unit/ExplainEngineTest.php](/Users/lo_fye/code/foundry-framework/tests/Unit/ExplainEngineTest.php) and updated [tests/Unit/ProAnalysisToolsTest.php](/Users/lo_fye/code/foundry-framework/tests/Unit/ProAnalysisToolsTest.php) for the new deterministic plan-backed behavior.

Verified with:
- `php vendor/bin/phpunit tests/Unit/ExplainEngineTest.php tests/Unit/ProAnalysisToolsTest.php tests/Unit/CLICommandMatchesTest.php tests/Unit/ApiSurfaceRegistryTest.php`
- `php vendor/bin/phpunit tests/Integration/CLIProCommandsTest.php tests/Integration/CLIApplicationTest.php`
- `php bin/foundry compile graph --json`
- `php bin/foundry verify graph --json`
- `php bin/foundry verify pipeline --json`

What’s intentionally still modest at 19A: contributors are just a hook, docs collection is a stub, and analyzers/flow output are foundational rather than the full 19B-19F behavior.

44 files changed
+3096
-359
Undo
src/Explain/Analyzers/CommandSubjectAnalyzer.php
src/Explain/Analyzers/EventSubjectAnalyzer.php
src/Explain/Analyzers/ExtensionSubjectAnalyzer.php
src/Explain/Analyzers/FeatureSubjectAnalyzer.php
src/Explain/Analyzers/GenericGraphSubjectAnalyzer.php
src/Explain/Analyzers/JobSubjectAnalyzer.php
src/Explain/Analyzers/PipelineStageSubjectAnalyzer.php
src/Explain/Analyzers/RouteSubjectAnalyzer.php
src/Explain/Analyzers/SchemaSubjectAnalyzer.php
src/Explain/Analyzers/SubjectAnalyzerInterface.php
src/Explain/Analyzers/WorkflowSubjectAnalyzer.php
src/Explain/Collectors/CommandContextCollector.php
src/Explain/Collectors/DiagnosticsContextCollector.php
src/Explain/Collectors/DocsContextCollector.php
src/Explain/Collectors/EventContextCollector.php
src/Explain/Collectors/ExplainContextCollectorInterface.php
src/Explain/Collectors/ExtensionContextCollector.php
src/Explain/Collectors/GraphNeighborhoodCollector.php
src/Explain/Collectors/ImpactContextCollector.php
src/Explain/Collectors/PipelineContextCollector.php
src/Explain/Collectors/SchemaContextCollector.php
src/Explain/Collectors/WorkflowContextCollector.php
src/Explain/Contributors/ExplainContributorInterface.php
src/Explain/ExplainArtifactCatalog.php
src/Explain/ExplainContext.php
src/Explain/ExplainEngine.php
src/Explain/ExplainEngineFactory.php
src/Explain/ExplainEngineInterface.php
src/Explain/ExplainOptions.php
src/Explain/ExplainSubject.php
src/Explain/ExplainSupport.php
src/Explain/ExplainTarget.php
src/Explain/ExplainTargetResolver.php
src/Explain/ExplanationPlan.php
src/Explain/Renderers/ExplanationRendererInterface.php
src/Explain/Renderers/JsonExplanationRenderer.php
src/Explain/Renderers/MarkdownExplanationRenderer.php
src/Explain/Renderers/TextExplanationRenderer.php
src/Explain/RuleBasedSummaryBuilder.php
src/Pro/ArchitectureExplainer.php
src/Pro/CLI/ExplainCommand.php
src/Support/ApiSurfaceRegistry.php
tests/Unit/ExplainEngineTest.php
tests/Unit/ProAnalysisToolsTest.php

## Verify
- Clear layering: CLI → Resolver → Engine → Collectors → Analyzers → Plan → Renderer
- No analysis logic inside CLI command
- No rendering logic inside analyzers or collectors
- ExplanationPlan is the single source of truth for output

## Anti-patterns to avoid
- Command directly accessing graph/projections
- Collectors returning formatted text instead of structured data
- Analyzers generating human-readable strings
- Renderer querying graph or projections

## Green-light criteria
- Fully deterministic output
- Clean separation of concerns across all layers
- DTOs (Target, Subject, Plan) are stable and testable
- Architecture supports future contributors and extensions cleanly
- >90% test coverage

Please fix any issues and ensure that all Green-light criteria are met.

-----------

The 19A `foundry explain` slice is green-lit now.

The architecture is cleanly separated: [src/Pro/CLI/ExplainCommand.php](/Users/lo_fye/code/foundry-framework/src/Pro/CLI/ExplainCommand.php) only parses flags, enforces Pro gating, loads the graph, and delegates. Resolution and orchestration live in [src/Explain/ExplainTargetResolver.php](/Users/lo_fye/code/foundry-framework/src/Explain/ExplainTargetResolver.php) and [src/Explain/ExplainEngine.php](/Users/lo_fye/code/foundry-framework/src/Explain/ExplainEngine.php). Collectors and analyzers under [src/Explain](/Users/lo_fye/code/foundry-framework/src/Explain) return structured data only, and renderers under [src/Explain/Renderers](/Users/lo_fye/code/foundry-framework/src/Explain/Renderers) only consume [src/Explain/ExplanationPlan.php](/Users/lo_fye/code/foundry-framework/src/Explain/ExplanationPlan.php). [src/Pro/ArchitectureExplainer.php](/Users/lo_fye/code/foundry-framework/src/Pro/ArchitectureExplainer.php) is now just a thin adapter over engine + renderer, so `ExplanationPlan` is the single source of truth for JSON, text, and markdown output.

I also verified the slice with focused coverage and tests. Explain-scope coverage is `90.76%` (`1110/1223`) for `src/Explain`, [src/Pro/ArchitectureExplainer.php](/Users/lo_fye/code/foundry-framework/src/Pro/ArchitectureExplainer.php), and [src/Pro/CLI/ExplainCommand.php](/Users/lo_fye/code/foundry-framework/src/Pro/CLI/ExplainCommand.php). The main coverage additions are in [tests/Unit/ExplainEngineTest.php](/Users/lo_fye/code/foundry-framework/tests/Unit/ExplainEngineTest.php), [tests/Unit/ExplainArchitectureCoverageTest.php](/Users/lo_fye/code/foundry-framework/tests/Unit/ExplainArchitectureCoverageTest.php), [tests/Unit/ProAnalysisToolsTest.php](/Users/lo_fye/code/foundry-framework/tests/Unit/ProAnalysisToolsTest.php), and [tests/Integration/CLIProCommandsTest.php](/Users/lo_fye/code/foundry-framework/tests/Integration/CLIProCommandsTest.php).

Verified with:
- `php vendor/bin/phpunit tests/Unit/ExplainEngineTest.php tests/Unit/ExplainArchitectureCoverageTest.php tests/Unit/ProAnalysisToolsTest.php tests/Unit/CLICommandMatchesTest.php tests/Unit/ApiSurfaceRegistryTest.php tests/Integration/CLIProCommandsTest.php tests/Integration/CLIApplicationTest.php`
- `php -d zend_extension=/opt/homebrew/lib/php/pecl/20240924/xdebug.so -d xdebug.mode=coverage vendor/bin/phpunit --coverage-clover /tmp/explain-clover.xml ...`
- `php bin/foundry compile graph --json`
- `php bin/foundry verify graph --json`
- `php bin/foundry verify pipeline --json`
- `php bin/foundry verify contracts --json`

The only qualification is scope: the `>90%` result is for the new explain architecture slice, not the entire framework repository.

7 files changed
+506
-66
Undo
src/Explain/ExplainResponse.php
src/Explain/Renderers/ExplanationRendererFactory.php
src/Pro/ArchitectureExplainer.php
src/Pro/CLI/ExplainCommand.php
tests/Integration/CLIProCommandsTest.php
tests/Unit/ExplainArchitectureCoverageTest.php
tests/Unit/ProAnalysisToolsTest.php
