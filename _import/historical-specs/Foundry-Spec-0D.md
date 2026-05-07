Phases 0, 0B, & 0C are implemented.
What do you think about this being Phase 0D, before implementing Phases 1, 2, & 3:


SPEC 0D - Middleware
Think long and hard about which approach to middleware is best for Foundry, given its goals and philosophy and its new compiler-like nature.
Any time we are implementing a new spec or feature, overall test coverage must remain at 90% or higher.

------------------------------

I think yes, a middleware phase before 1–3 makes sense, but I would not implement “Laravel-style middleware” as the primary model.

For Foundry, the best approach is:

Phase 0D: Request/Execution Pipeline, Feature Guards, and Interceptors

Not “middleware” in the old global-stack sense.

That name matters, because what Foundry wants is not:

request
→ giant global middleware onion
→ maybe eventually feature logic

It wants:

request
→ compile-known pipeline stages
→ feature-declared guards/interceptors
→ feature action
→ response shaping

That is much more aligned with:
	•	explicit contracts
	•	compiler-known behavior
	•	graph inspection
	•	impact analysis
	•	LLM-safe reasoning
	•	deterministic runtime

So the instinct is right. The classic term is a bit misleading.

⸻

My recommendation

Yes, do Phase 0D before Phases 1–3

Because Phases 1–3 will need a clean answer to all of these:
	•	auth enforcement
	•	CSRF
	•	rate limiting
	•	sessions
	•	API auth
	•	admin-only access
	•	tenant resolution later
	•	locale resolution later
	•	request IDs / tracing
	•	cache headers
	•	content negotiation
	•	streaming/SSE hooks later
	•	billing/webhook verification later

If you don’t settle the pipeline model now, Phase 1 will start inventing little local answers, and then you’ll spend the next six months herding goblins out of cross-cutting logic.

So yes: do it now.

⸻

What approach is best for Foundry?

Not classic global middleware stacks

Classic middleware has real strengths, but it also has problems that clash with Foundry:

Problems with classic middleware in Foundry
	•	hidden behavior outside the feature
	•	order-dependent global stacks
	•	hard for LLMs to infer
	•	harder to visualize in the graph
	•	often mutates request/response in spooky ways
	•	“why did this happen?” becomes a stack archaeology exercise

That’s normal in Laravel or Express. It is not ideal in a compiler-first framework.

⸻

The best model for Foundry

I would implement three layers:

1. Core pipeline stages

These are framework-level and minimal.

Examples:
	•	request ID / trace context
	•	route resolution
	•	feature resolution
	•	input parsing
	•	auth strategy resolution
	•	rate-limit enforcement
	•	schema validation
	•	transaction handling
	•	response serialization
	•	error shaping

These should be:
	•	explicit
	•	compiler-known
	•	globally inspectable
	•	mostly fixed in order

Think of them as compiler-known execution stages, not userland middleware.

⸻

2. Feature-declared guards

These are the Foundry-native replacement for most middleware.

In feature.yaml, a feature should be able to declare things like:

auth:
  required: true
  strategies:
    - session
permissions:
  - posts.create

rate_limit:
  strategy: user
  bucket: post_create
  cost: 1

csrf:
  required: true

request:
  locale_resolution: true

These aren’t “middleware classes.”
They’re declarations.

The compiler turns them into graph nodes and runtime pipeline configuration.

That’s very Foundry.

⸻

3. Interceptors / hooks

For truly cross-cutting custom behavior, allow a small explicit interceptor system.

Examples:
	•	custom tenant resolution
	•	custom audit trail enrichment
	•	request normalization
	•	webhook signature verification
	•	special response headers

But these should be:
	•	explicit
	•	registered through the extension system
	•	compiler-known
	•	attached to named pipeline stages
	•	inspectable in the graph

So instead of:

middleware stack = [a, b, c, d, e]

you get something closer to:

pipeline stage: before_auth
  interceptors:
    - tenant_resolver

pipeline stage: before_action
  interceptors:
    - webhook_signature_verifier

pipeline stage: after_action
  interceptors:
    - audit_enricher

That’s much safer and much easier to reason about.

⸻

Why this is better for Foundry

1. It keeps the feature as the center of truth

The feature declares what it needs.

2. It keeps the compiler in charge

Pipeline configuration becomes graph-visible.

3. It keeps cross-cutting behavior inspectable

You can ask:

What runs before feature:create_post?
What guards apply to route POST /posts?
What interceptors run at before_action?

That is gold for LLMs and humans.

4. It avoids middleware soup

No giant global onion of sadness.

⸻

What Phase 0D should include

If I were shaping it, I’d make it:

Phase 0D — Execution Pipeline, Guards, and Interceptors

Core goals
	•	establish the canonical execution pipeline
	•	define which stages are core and fixed
	•	define feature-declared guards
	•	define extension-registered interceptors
	•	compile all of this into the graph
	•	expose it via inspect/doctor/visualize/prompt tooling

Required concepts
	•	PipelineDefinition
	•	PipelineStage
	•	GuardDefinition
	•	InterceptorDefinition
	•	InterceptorRegistry
	•	FeatureExecutionPlan
	•	PipelineGraphProjection

Required graph support

Represent:
	•	pipeline stages
	•	feature guards
	•	route → execution plan
	•	interceptor attachments
	•	ordering constraints
	•	diagnostics for conflicts/missing stages/invalid attachments

Required CLI

Something like:

foundry inspect pipeline --json
foundry inspect execution-plan feature:create_post --json
foundry inspect guards feature:create_post --json
foundry inspect interceptors --json
foundry verify pipeline --json

Required visualizations
	•	route → stages → feature → response
	•	feature execution plan
	•	auth/rate-limit/validation overlays

Required diagnostics
	•	duplicate/conflicting interceptors
	•	invalid stage attachment
	•	missing required auth config
	•	CSRF declared on API-only route if invalid
	•	interceptor ordering conflicts
	•	non-deterministic execution plan
	•	dangerous global interceptor warnings

⸻

What it should not do

I would avoid:
	•	arbitrary middleware classes per route as the main abstraction
	•	implicit middleware discovery
	•	mutable request bag chaos
	•	ad hoc “before/after” hooks without stage semantics
	•	letting extensions inject themselves anywhere without explicit stage attachment
	•	dynamic onion stacks assembled at runtime

That stuff is exactly what Foundry should outgrow.

⸻

How this helps Phases 1–3

Phase 1

Starter kits, CRUD, admin, uploads, search:
	•	auth
	•	CSRF
	•	form validation
	•	session handling
	•	admin guards
	•	upload verification

All cleaner with Phase 0D.

Phase 2

Notifications, APIs, docs, tests:
	•	content negotiation
	•	API auth
	•	JSON error shaping
	•	OpenAPI execution semantics
	•	deeper test generation against real execution plans

Also cleaner.

Phase 3

Billing, workflows, orchestration, SSE, i18n, roles, inspect UI:
	•	webhook verification
	•	locale resolution
	•	role/policy gates
	•	stream auth
	•	billing request hooks
	•	execution-plan visualization

Much cleaner.

So yes: 0D before 1–3 is the right move.

⸻

About the 90% coverage requirement

That requirement is good and should stay.

I’d phrase it slightly more precisely, though:

Better wording

Any new Phase 0D implementation must preserve overall automated test coverage at 90% or higher, and all new pipeline, guard, interceptor, compile, inspect, verify, and runtime behavior introduced by this phase must have strong direct test coverage.

Why I’d word it that way:
	•	“overall 90%” is a blunt guardrail
	•	but Codex also needs to know the new subsystem itself must be deeply tested
	•	otherwise it may keep overall coverage above 90 by leaning on old tests while under-testing the new pipeline model

⸻

My final verdict

Yes, do it.

But make it:

Phase 0D — Execution Pipeline, Guards, and Interceptors

instead of just “Middleware.”

That is the Foundry-native version of the idea.

It respects:
	•	compiler-land
	•	graph visibility
	•	LLM reasoning
	•	deterministic runtime
	•	extension discipline

And it will make every later phase better.

If you want, I can now write the full Phase 0D Master Spec with preface, in the same style as the others.

---------------------






















Phase 0A introduced the semantic compiler and canonical application graph.

Phase 0B introduced extensions, packs, migrations, codemods, and versioning.

Phase 0C introduced architecture analysis, graph visualization, and AI-safe development tooling.

Phase 0D must introduce the runtime execution pipeline used for handling requests.

This phase replaces traditional middleware stacks with a deterministic execution pipeline that integrates with the application graph and compiler.

Important rules:

• The execution pipeline must be compiler-visible and represented in the application graph.
• Pipeline configuration must be deterministic and inspectable.
• Features must declare guards and requirements declaratively in their specs.
• Cross-cutting behavior must be implemented through explicit interceptors attached to pipeline stages.
• All guards, interceptors, and execution plans must be visible through graph inspection commands.
• Pipeline configuration must participate in diagnostics and verification.
• No implicit runtime middleware stacks may exist outside the pipeline model.

Phase 0D introduces three core concepts:

1. Execution pipeline
2. Feature guards
3. Stage interceptors

These mechanisms will support authentication, permissions, CSRF protection, rate limiting, request validation, transaction handling, and other cross-cutting concerns required by Phases 1–3.

All new behavior must integrate with:

• the canonical application graph
• the extension system
• the diagnostics engine
• graph inspection tooling
• visualization tooling
• the prompt-safe development loop

Test coverage must remain ≥ 90% overall, and new subsystems must have strong direct test coverage.

Below is a complete Phase 0D Master Spec you can hand directly to Codex. It follows the same structure as your previous specs and assumes Phase 0A (compiler), Phase 0B (extensions/migrations), and Phase 0C (doctor/graph/prompt) are already implemented.

The design intentionally avoids classic “middleware onion stacks” and instead builds a compiler-visible execution pipeline with guards and interceptors, which fits Foundry’s graph-first architecture.

⸻

Preface for Phase 0D (Execution Pipeline, Guards, Interceptors)

Paste this above the Phase 0D spec when giving it to Codex.

Phase 0A introduced the semantic compiler and canonical application graph.

Phase 0B introduced extensions, packs, migrations, codemods, and versioning.

Phase 0C introduced architecture analysis, graph visualization, and AI-safe development tooling.

Phase 0D must introduce the runtime execution pipeline used for handling requests.

This phase replaces traditional middleware stacks with a deterministic execution pipeline that integrates with the application graph and compiler.

Important rules:

• The execution pipeline must be compiler-visible and represented in the application graph.
• Pipeline configuration must be deterministic and inspectable.
• Features must declare guards and requirements declaratively in their specs.
• Cross-cutting behavior must be implemented through explicit interceptors attached to pipeline stages.
• All guards, interceptors, and execution plans must be visible through graph inspection commands.
• Pipeline configuration must participate in diagnostics and verification.
• No implicit runtime middleware stacks may exist outside the pipeline model.

Phase 0D introduces three core concepts:

1. Execution pipeline
2. Feature guards
3. Stage interceptors

These mechanisms will support authentication, permissions, CSRF protection, rate limiting, request validation, transaction handling, and other cross-cutting concerns required by Phases 1–3.

All new behavior must integrate with:

• the canonical application graph
• the extension system
• the diagnostics engine
• graph inspection tooling
• visualization tooling
• the prompt-safe development loop

Test coverage must remain ≥ 90% overall, and new subsystems must have strong direct test coverage.


⸻

Master Spec for Codex: Implement Foundry Phase 0D

Phase Name

Phase 0D — Execution Pipeline, Guards, and Interceptors

⸻

Goal

Introduce a deterministic execution pipeline for processing requests.

The pipeline must be:
	•	compiler-visible
	•	graph-represented
	•	deterministic
	•	inspectable
	•	extensible through the extension system
	•	safe for LLM-assisted development

This replaces traditional runtime middleware stacks.

⸻

Core Architecture

Request lifecycle

The runtime request lifecycle must follow this conceptual model:

HTTP Request
   ↓
Pipeline Start
   ↓
Pipeline Stages
   ↓
Feature Execution
   ↓
Response Handling
   ↓
HTTP Response

Each stage is explicit and visible in the application graph.

⸻

1. Pipeline Definition

Goal

Define a canonical pipeline composed of named stages.

Stages must be:
	•	deterministic
	•	ordered
	•	inspectable
	•	attachable via extensions

⸻

Required stages

At minimum implement these pipeline stages:

request_received
routing
before_auth
auth
before_validation
validation
before_action
action
after_action
response_serialization
response_send

Extensions may add stages but must declare ordering constraints.

⸻

Pipeline representation

Pipeline stages must appear as nodes in the application graph.

Example node:

pipeline_stage: auth

Edges:

request_received → routing → before_auth → auth → before_validation ...


⸻

2. Feature Execution Plans

Each feature must compile into a FeatureExecutionPlan.

This plan defines:
	•	pipeline stages affecting the feature
	•	guards applied
	•	interceptors executed
	•	action execution node

Example plan:

route: POST /posts
  ↓
pipeline stages:
  before_auth
  auth
  validation
  before_action
  action
  after_action

Execution plans must be represented in the graph.

⸻

3. Feature Guards

Goal

Allow features to declare runtime requirements declaratively.

These replace most traditional middleware.

⸻

Guard declaration in feature spec

Example:

auth:
  required: true
  strategies:
    - session

permissions:
  - posts.create

rate_limit:
  strategy: user
  bucket: post_create
  cost: 1

csrf:
  required: true


⸻

Guard types

Implement support for:
	•	authentication guard
	•	permission guard
	•	rate limiting guard
	•	CSRF guard
	•	request validation guard
	•	transaction guard

Extensions may add new guard types.

⸻

Graph representation

Example node:

guard:auth_required
guard:permission_posts.create
guard:rate_limit_post_create

Edges:

feature:create_post → guard:auth_required
feature:create_post → guard:permission_posts.create


⸻

4. Interceptors

Goal

Provide controlled cross-cutting behavior.

Interceptors attach to pipeline stages.

⸻

Interceptor concept

An interceptor is a piece of logic that executes during a specific pipeline stage.

Example:

interceptor:tenant_resolver
stage: before_auth


⸻

Interceptor registration

Interceptors must be registered through the extension system introduced in Phase 0B.

Example extension registration:

RegistersInterceptors


⸻

Interceptor capabilities

Interceptors may:
	•	inspect requests
	•	enrich context
	•	reject requests
	•	modify response metadata

Interceptors must not mutate pipeline structure dynamically.

⸻

5. Graph Representation

The application graph must include nodes for:
	•	pipeline stages
	•	guards
	•	interceptors
	•	execution plans

Edges must represent:
	•	stage order
	•	feature guard dependencies
	•	interceptor stage attachments
	•	route → execution plan relationships

⸻

6. CLI Inspection

Implement commands:

foundry inspect pipeline
foundry inspect pipeline --json

foundry inspect execution-plan <feature>
foundry inspect execution-plan <route>

foundry inspect guards
foundry inspect guards <feature>

foundry inspect interceptors
foundry inspect interceptors --stage=<stage>

foundry verify pipeline


⸻

7. Visualization Support

Integrate with Phase 0C graph visualization.

Example command:

foundry graph visualize --pipeline

Example diagram:

request
  ↓
routing
  ↓
auth
  ↓
validation
  ↓
feature:create_post
  ↓
response


⸻

8. Diagnostics

Add pipeline-related diagnostics.

Examples:

Missing auth

FDY8001_FEATURE_REQUIRES_AUTH
Feature delete_user requires authentication but none declared.

Stage conflict

FDY8002_INTERCEPTOR_STAGE_CONFLICT
Interceptor tenant_resolver attaches to unknown stage before_authentication.

Guard conflict

FDY8003_CONFLICTING_RATE_LIMIT
Feature create_post declares two rate limits.

Non-deterministic pipeline

FDY8004_NON_DETERMINISTIC_PIPELINE_ORDER
Extensions introduced ambiguous stage ordering.


⸻

9. Runtime Execution Engine

Implement a runtime pipeline executor.

Responsibilities:
	•	execute pipeline stages
	•	enforce guards
	•	run interceptors
	•	invoke feature action
	•	produce response

The executor must be deterministic.

⸻

10. Integration with Prompt Loop

The prompt system from Phase 0C must be able to query pipeline structure.

Example prompt context:

Execution plan for POST /posts:

auth: required
permissions: posts.create
validation: post.input
rate limit: post_create

This ensures safe LLM reasoning.

⸻

11. Integration with Doctor

The foundry doctor command must detect:
	•	routes missing authentication
	•	routes missing validation
	•	dangerous interceptors
	•	pipeline ordering conflicts
	•	missing guards

⸻

12. Extension Support

Extensions may contribute:
	•	new guards
	•	new interceptors
	•	new pipeline stages
	•	new diagnostics
	•	new execution policies

All contributions must be registered through the extension registry.

⸻

13. Documentation

Add documentation covering:
	•	execution pipeline architecture
	•	guard system
	•	interceptor system
	•	pipeline inspection
	•	pipeline visualization
	•	extension integration

Explain how this differs from traditional middleware.

⸻

14. Automated Tests

Test coverage must remain ≥ 90% overall.

The pipeline subsystem must have strong dedicated coverage.

⸻

Required test categories

Pipeline tests
	•	stage ordering
	•	stage execution
	•	stage insertion

Guard tests
	•	authentication guard
	•	permission guard
	•	rate limiting guard
	•	CSRF guard

Interceptor tests
	•	interceptor registration
	•	stage attachment
	•	interceptor execution

Compiler integration
	•	graph node generation
	•	execution plan compilation

CLI tests
	•	inspect pipeline
	•	inspect execution plan
	•	verify pipeline

Diagnostics tests
	•	missing guards
	•	stage conflicts
	•	interceptor conflicts

Visualization tests
	•	pipeline graph generation

⸻

Deliverables

Codex must produce:
	•	pipeline architecture
	•	guard system
	•	interceptor system
	•	graph integration
	•	CLI inspection commands
	•	diagnostics
	•	visualization support
	•	documentation
	•	automated tests

⸻

Final instruction

Phase 0D completes the foundational architecture for Foundry.

After this phase:

source manifests
    ↓
semantic compiler
    ↓
application graph
    ↓
execution pipeline
    ↓
feature runtime

This prepares the framework for Phases 1–3 while keeping runtime behavior explicit, deterministic, and safe for LLM-assisted development.

Test coverage must remain ≥ 90% overall, and the pipeline subsystem must have comprehensive automated tests.

















-------------------------------------
















Implemented Phase 0D on top of the Phase 0A–0C graph/compiler substrate, with runtime execution now pipeline-driven and compiler-visible.

**What was added**
- Compiler pipeline modeling pass: [PipelinePass.php](/Users/lo_fye/code/foundry/src/Compiler/Passes/PipelinePass.php)
  - Compiles `pipeline_stage`, `guard`, `interceptor`, and `execution_plan` nodes.
  - Emits stage/order/attachment edges and pipeline diagnostics (`FDY8001`–`FDY8004`).
- New IR node types: [Nodes.php](/Users/lo_fye/code/foundry/src/Compiler/IR/Nodes.php)
- Core extension integration: [CoreCompilerExtension.php](/Users/lo_fye/code/foundry/src/Compiler/Extensions/CoreCompilerExtension.php)
  - Registers pipeline pass, core interceptors, analyzer, and projection outputs.
- New projections from graph: [CoreProjectionEmitters.php](/Users/lo_fye/code/foundry/src/Compiler/Projection/CoreProjectionEmitters.php)
  - `pipeline_index.php`, `guard_index.php`, `execution_plan_index.php`, `interceptor_index.php`.
- Runtime stage executor behavior in request path: [FeatureExecutor.php](/Users/lo_fye/code/foundry/src/Feature/FeatureExecutor.php)
  - Deterministic stage loop, guard enforcement, interceptor execution.
  - Uses compiled execution plans/guards with deterministic fallback for legacy builds.
- New inspect/verify CLI surface:
  - [InspectGraphCommand.php](/Users/lo_fye/code/foundry/src/CLI/Commands/InspectGraphCommand.php)
  - [VerifyPipelineCommand.php](/Users/lo_fye/code/foundry/src/CLI/Commands/VerifyPipelineCommand.php)
- Phase 0C integrations updated:
  - Doctor analyzer: [PipelineAnalyzer.php](/Users/lo_fye/code/foundry/src/Compiler/Analysis/Analyzers/PipelineAnalyzer.php)
  - Visualization `--pipeline`: [GraphVisualizer.php](/Users/lo_fye/code/foundry/src/Compiler/Visualization/GraphVisualizer.php)
  - Prompt context now includes execution-plan context: [GraphPromptBuilder.php](/Users/lo_fye/code/foundry/src/Compiler/Prompt/GraphPromptBuilder.php)

**Docs/examples**
- Added Phase 0D doc: [semantic-compiler-phase0d.md](/Users/lo_fye/code/foundry/docs/semantic-compiler-phase0d.md)
- Added examples: [examples/phase0d/README.md](/Users/lo_fye/code/foundry/examples/phase0d/README.md)
- Updated main docs/CLI lists: [README.md](/Users/lo_fye/code/foundry/README.md)

**Tests and coverage**
- Full suite: `163` tests, `810` assertions, `3` skipped, all passing.
- Coverage: **90.06% lines** (`7541/8373`), stays above the 90% requirement.
- Added targeted Phase 0D tests:
  - [PipelineDefinitionResolverTest.php](/Users/lo_fye/code/foundry/tests/Unit/PipelineDefinitionResolverTest.php)
  - [PipelineCompilerIntegrationTest.php](/Users/lo_fye/code/foundry/tests/Unit/PipelineCompilerIntegrationTest.php)
  - [PipelineRuntimeExecutorTest.php](/Users/lo_fye/code/foundry/tests/Unit/PipelineRuntimeExecutorTest.php)

42 files changed
+3056
-71
Undo
README.md
docs/semantic-compiler-phase0d.md
src/CLI/Application.php
src/CLI/Commands/GraphVisualizeCommand.php
src/CLI/Commands/InspectGraphCommand.php
src/CLI/Commands/VerifyPipelineCommand.php
src/Compiler/Analysis/AnalyzerContext.php
src/Compiler/Analysis/Analyzers/PipelineAnalyzer.php
src/Compiler/Analysis/ImpactAnalyzer.php
src/Compiler/Extensions/AbstractCompilerExtension.php
src/Compiler/Extensions/CompilerExtension.php
src/Compiler/Extensions/CoreCompilerExtension.php
src/Compiler/Extensions/ExtensionRegistry.php
src/Compiler/GraphVerifier.php
src/Compiler/IR/ExecutionPlanNode.php
src/Compiler/IR/GuardNode.php
src/Compiler/IR/InterceptorNode.php
src/Compiler/IR/Nodes.php
src/Compiler/IR/PipelineStageNode.php
src/Compiler/Passes/PipelinePass.php
src/Compiler/Projection/CoreProjectionEmitters.php
src/Compiler/Prompt/GraphPromptBuilder.php
src/Compiler/Visualization/GraphVisualizer.php
src/Core/RuntimeFactory.php
src/Feature/FeatureExecutor.php
src/Feature/FeatureLoader.php
src/Pipeline/Interceptors/RequestTraceInterceptor.php
src/Pipeline/Interceptors/ResponseTraceInterceptor.php
src/Pipeline/PipelineDefinitionResolver.php
src/Pipeline/PipelineExecutionState.php
src/Pipeline/PipelineStageDefinition.php
src/Pipeline/StageInterceptor.php
tests/Integration/CLIGraphCommandsTest.php
tests/Integration/CLIPhase0CCommandsTest.php
tests/Integration/ExamplesStructureTest.php
tests/Unit/CLICommandMatchesTest.php
tests/Unit/GraphCompilerTest.php
tests/Unit/GraphPromptBuilderTest.php
tests/Unit/GraphVisualizerTest.php
tests/Unit/PipelineCompilerIntegrationTest.php
tests/Unit/PipelineDefinitionResolverTest.php
tests/Unit/PipelineRuntimeExecutorTest.php


worked for 28 minutes