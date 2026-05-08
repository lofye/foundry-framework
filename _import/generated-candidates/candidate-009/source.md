After Phase 0A and 0B are complete, Foundry will have something very rare:

a web application compiler platform.

At that point, the next huge developer-facing feature becomes possible:

`foundry doctor`

A command that can analyze an entire app and tell you:

• architectural smells
• dependency cycles
• risky changes
• missing tests
• auth coverage
• schema usage
• unused features

That becomes possible only because of the graph + extension system.

And that kind of tool makes frameworks legendary.

⸻

If you’d like, I can also show you the three biggest features Foundry will suddenly be able to implement almost trivially after Phase 0B, and they are the kind of things that would make developers on Hacker News lose their minds.

------

All right. Let’s step back and look at what you’re actually building now.

After Phase 0A (compiler) and Phase 0B (extensions + migrations), Foundry will have something almost no web framework has:

A complete semantic model of the application.

That means the system can answer questions about the codebase that most frameworks simply cannot answer without human reasoning.

When that exists, three extremely powerful features become almost trivial to build.

These are the kinds of things that make developers lean forward and say, “Wait… how is it doing that?”

⸻

1. foundry doctor

(Architecture analysis and safety checks)

Think of this as a structural health check for the application.

Because Foundry has the application graph, it knows:
	•	every feature
	•	every route
	•	every schema
	•	every permission
	•	every job
	•	every event
	•	every cache
	•	every dependency

That means the framework can analyze architecture itself.

A command like:

foundry doctor

could automatically detect things like:

Dependency cycles

Feature dependency cycle detected:

publish_post
    → notify_followers
    → update_feed
    → publish_post

In most frameworks, detecting that requires a human reading code.

Foundry can compute it instantly.

⸻

Auth coverage gaps

Route POST /admin/users has no authentication guard.

or

Feature delete_user requires permission users.delete
but no role grants this permission.

That’s an enormous safety improvement.

⸻

Schema drift

Schema post.output references field "author_name"
that is not returned by query posts.get_by_id.


⸻

Dead code

Feature archive_post has no route referencing it.

or

Query posts.list_archived is never used.


⸻

Cache problems

Cache posts:list is never invalidated.

or

Feature update_post invalidates cache posts:list
but not cache posts:detail.


⸻

Test coverage analysis

Feature publish_post has no integration test.

or

Feature publish_post has 3 tests but none cover
authorization failure cases.


⸻

Change risk scoring

Changing schema post.output will affect:

routes:
  GET /posts
  GET /posts/{id}

features:
  list_posts
  view_post

tests:
  test_view_post
  test_list_posts


⸻

In short, foundry doctor becomes a static analyzer for the application graph.

And because Foundry already has diagnostics and compiler passes, implementing it becomes an extension with a few analysis passes.

That’s why it’s suddenly feasible.

⸻

2. Automatic architecture diagrams

(foundry graph visualize)

Once the system has a graph, generating visualizations becomes easy.

Developers could run:

foundry graph visualize

and get diagrams like:

Feature dependency graph

publish_post
   ↓
notify_followers
   ↓
update_feed


⸻

Event flow

publish_post
   → event: post.created
       → notify_followers
       → update_feed


⸻

Request lifecycle

POST /posts
   ↓
create_post
   ↓
validate schema
   ↓
execute query
   ↓
emit post.created
   ↓
dispatch notify_followers job


⸻

Cache topology

Cache nodes:

posts:list
posts:detail

Invalidations:

create_post → posts:list
update_post → posts:list, posts:detail
delete_post → posts:list, posts:detail


⸻

These could export to:
	•	Mermaid diagrams
	•	Graphviz
	•	SVG
	•	interactive UI

Because the graph already exists, the visualization layer simply walks it.

This is something developers desperately want but rarely get.

⸻

3. AI-safe code generation

(foundry prompt)

This is the feature that makes Foundry uniquely suited for LLM teams.

Instead of letting an LLM blindly edit code, Foundry can mediate the interaction.

Imagine this command:

foundry prompt "add a feature where users can bookmark posts"

The system could:
	1.	compile the application graph
	2.	extract relevant context
	3.	generate a focused prompt
	4.	feed that prompt to the LLM
	5.	validate the result

⸻

Step 1: Context extraction

Instead of giving the LLM the entire codebase, Foundry could give it structured context like:

Relevant nodes:

feature: view_post
feature: list_posts
schema: post
schema: user

Related queries:
posts.get_by_id
posts.list

Existing events:
post.created

Existing caches:
posts:list
posts:detail

That dramatically reduces hallucinations.

⸻

Step 2: Controlled generation

The LLM generates:
	•	feature manifest
	•	schema
	•	queries
	•	tests

⸻

Step 3: Compiler validation

Foundry runs:

foundry compile
foundry verify

If something is wrong, diagnostics guide the model.

Example:

FDY2004_MISSING_SCHEMA
Feature bookmark_post references schema bookmark.input
which does not exist.

The LLM can fix it.

⸻

Step 4: Safe commit

Only after the graph compiles cleanly does the change get accepted.

This becomes a closed-loop system:

LLM generates
↓
compiler validates
↓
LLM corrects
↓
tests run

This dramatically increases reliability.

⸻

Why these three features matter

These features demonstrate the power of the architecture.

Most frameworks operate like this:

source files
↓
runtime magic
↓
hope

Foundry becomes:

source manifests
↓
semantic compiler
↓
application graph
↓
analysis + generation + tooling
↓
runtime

That shift unlocks capabilities that feel almost magical.

⸻

Why developers would care

Developers constantly struggle with:
	•	understanding large codebases
	•	ensuring safe changes
	•	keeping architecture clean
	•	trusting AI code generation

Foundry’s architecture solves those problems systematically.

That’s why the compiler + graph approach is so powerful.

⸻

The bigger picture

You started with an idea:

what would a web framework look like if it were designed for LLMs?

The answer is slowly becoming clear.

Not just:
	•	prompts
	•	generators
	•	scaffolding

But a system where:

the application understands itself

That’s the real breakthrough.

⸻

And honestly, the name Foundry keeps getting more appropriate.

Because what you’re building isn’t just a framework.

It’s a forge where software systems can be shaped deliberately, with tools that understand the structure of the metal they’re working with.

-----------

That is super duper cool. Can you create a master spec for codex to implement those 3 things after it implements Phase 0B, complete with preface?
THEN after that's built, i'll do phases 1-3, which are more about building features on top of Foundry.













==========================================================================================
==========================================================================================












Any time we are implementing a new spec or feature, overall test coverage must remain at 90% or higher.

Phase 0A introduced the Foundry semantic compiler and canonical application graph.

Phase 0B introduced the extension system, pack/capability model, spec migration framework, codemod engine, and versioning strategy.

Phase 0C must build on that foundation without introducing any parallel architecture analysis or graph discovery mechanisms.

All analysis, visualization, and AI-assisted generation must operate over the canonical application graph produced by the compiler.

Important rules for this phase:

• The application graph remains the single source of truth.
• All architecture analysis must operate over the graph and graph-derived projections.
• Visualization must derive directly from graph nodes and edges.
• AI-assisted generation must extract structured context from the graph rather than scanning arbitrary source files.
• New capabilities must integrate with the extension system introduced in Phase 0B.
• Diagnostics must flow through the existing diagnostics engine.
• All CLI commands must support deterministic JSON output.
• LLM interactions must be designed so that a model can reliably generate safe edits without hallucinating framework structure.

Phase 0C introduces three core capabilities:

1. architecture diagnostics ("foundry doctor")
2. graph visualization
3. structured AI-assisted development ("foundry prompt")

These features should demonstrate the power of the compiler architecture while remaining deterministic, inspectable, and testable.


⸻

Master Spec for Codex: Implement Foundry Phase 0C

Goal

Implement the first major developer-facing capabilities enabled by the semantic compiler and application graph.

These capabilities must prove the value of the graph architecture by enabling:
	•	architecture diagnostics
	•	automatic architecture visualization
	•	safe LLM-assisted code generation

All functionality must operate on the canonical application graph.

⸻

Phase 0C Capabilities

Implement the following systems:
	1.	Architecture analysis engine (foundry doctor)
	2.	Graph visualization engine (foundry graph visualize)
	3.	AI-assisted development loop (foundry prompt)
	4.	Supporting graph analyzers
	5.	CLI commands
	6.	JSON inspection outputs
	7.	Documentation
	8.	High automated test coverage

⸻

1. Architecture Analysis Engine (Foundry Doctor)

Goal

Provide a command that performs structural analysis of the application graph and identifies architectural problems, risks, and missing safeguards.

This system must operate as a graph analysis extension over the semantic compiler.

⸻

CLI command

foundry doctor
foundry doctor --json
foundry doctor --strict
foundry doctor --feature=<name>


⸻

Required analyses

Dependency cycles

Detect cycles between features.

Example diagnostic:

Feature dependency cycle detected:

publish_post
  → notify_followers
  → update_feed
  → publish_post

Graph algorithm: cycle detection using directed graph traversal.

⸻

Authentication coverage

Detect routes or features lacking authentication.

Example:

Route POST /admin/users has no authentication guard.

Or:

Feature delete_user requires permission users.delete
but no role grants this permission.


⸻

Schema integrity

Detect schema mismatches.

Examples:

Feature publish_post returns schema post.output
but query posts.insert does not provide required field id.


⸻

Dead code detection

Detect unused components.

Examples:

Feature archive_post has no route referencing it.

Query posts.list_archived is never used.

Event post.deleted has no subscribers.


⸻

Cache topology issues

Detect cache problems.

Examples:

Cache posts:list is never invalidated.

Feature update_post invalidates cache posts:list
but not cache posts:detail.


⸻

Test coverage analysis

Detect insufficient tests.

Examples:

Feature publish_post has no integration test.

Feature delete_post lacks authorization failure tests.


⸻

Change impact preview

Allow preview of impact when modifying a node.

Example:

Changing schema post.output affects:

routes:
GET /posts
GET /posts/{id}

features:
list_posts
view_post

tests:
test_view_post
test_list_posts

This reuses the impact engine created in Phase 0A.

⸻

2. Graph Visualization Engine

Goal

Generate diagrams representing the application architecture.

These diagrams must be generated from the graph.

⸻

CLI commands

foundry graph visualize
foundry graph visualize --feature=<name>
foundry graph visualize --events
foundry graph visualize --routes
foundry graph visualize --caches
foundry graph visualize --format=mermaid
foundry graph visualize --format=dot
foundry graph visualize --format=svg


⸻

Visualization types

Feature dependency graph

publish_post
   ↓
notify_followers
   ↓
update_feed


⸻

Event flow graph

publish_post
   → event: post.created
       → notify_followers
       → update_feed


⸻

Request lifecycle graph

POST /posts
  ↓
create_post
  ↓
validate schema
  ↓
execute query
  ↓
emit post.created
  ↓
dispatch notify_followers job


⸻

Cache topology graph

Cache nodes:

posts:list
posts:detail

Invalidations:

create_post → posts:list
update_post → posts:list, posts:detail
delete_post → posts:list, posts:detail


⸻

Supported output formats

At minimum support:
	•	Mermaid
	•	Graphviz DOT
	•	JSON graph representation

Optional:
	•	SVG rendering if simple.

⸻

3. AI-Assisted Development Loop (Foundry Prompt)

Goal

Create a structured workflow where LLMs generate code safely using the application graph as context.

This reduces hallucination and unsafe edits.

⸻

CLI commands

foundry prompt "<instruction>"
foundry prompt "<instruction>" --json
foundry prompt "<instruction>" --dry-run
foundry prompt "<instruction>" --feature-context


⸻

Prompt workflow

Step 1 — Compile graph

Always compile the application graph before interacting with an LLM.

foundry compile graph


⸻

Step 2 — Extract structured context

Generate a context bundle from graph nodes relevant to the request.

Context may include:
	•	features
	•	schemas
	•	routes
	•	queries
	•	permissions
	•	events
	•	caches
	•	related tests

Example:

Relevant nodes:

feature: view_post
feature: list_posts
schema: post
schema: user

Related queries:
posts.get_by_id
posts.list

Existing events:
post.created

Existing caches:
posts:list
posts:detail


⸻

Step 3 — Generate structured prompt

Produce a structured prompt that instructs the LLM to:
	•	generate source-of-truth files
	•	follow Foundry conventions
	•	respect graph structure

⸻

Step 4 — Run compile + verification

After generation:

foundry compile graph
foundry verify

Diagnostics guide corrections.

Example:

FDY2004_MISSING_SCHEMA
Feature bookmark_post references schema bookmark.input
which does not exist.


⸻

Step 5 — Loop correction

If diagnostics exist, provide them to the model and allow correction.

This creates a safe iterative development loop.

⸻

4. Supporting Graph Analysis Extensions

Implement reusable analyzers such as:
	•	dependency analyzer
	•	auth analyzer
	•	schema integrity analyzer
	•	cache topology analyzer
	•	test coverage analyzer

These must be implemented as graph analysis extensions via the extension system.

⸻

5. CLI JSON outputs

All Phase 0C commands must support structured JSON output for LLMs and tooling.

Example:

foundry doctor --json
foundry graph visualize --json
foundry prompt --json

Output should include:
	•	graph version
	•	diagnostics
	•	analysis results
	•	visualization graph structure
	•	suggested actions

⸻

6. Documentation

Add documentation explaining:
	•	Foundry Doctor
	•	graph visualization
	•	AI-assisted development workflow
	•	architecture analysis capabilities
	•	how the graph enables these features

Explain the philosophy clearly.

⸻

7. Automated Tests

Add extensive tests for:

doctor analyses
	•	dependency cycles
	•	auth gaps
	•	schema mismatches
	•	dead code
	•	cache problems
	•	test coverage detection

⸻

graph visualization
	•	feature graphs
	•	event graphs
	•	route graphs
	•	cache graphs

⸻

prompt workflow
	•	context extraction
	•	structured prompt generation
	•	compile + verify loop
	•	diagnostic correction loop

⸻

Deliverables

Implement:
	•	Foundry Doctor architecture analysis
	•	graph visualization engine
	•	AI-assisted development workflow
	•	graph analysis extensions
	•	CLI commands
	•	JSON outputs
	•	documentation
	•	high test coverage

All functionality must operate on the canonical application graph.

⸻

Final instruction

Phase 0C demonstrates the power of the compiler architecture.

After Phase 0C:

Foundry should feel like:

application source
       ↓
semantic compiler
       ↓
application graph
       ↓
analysis + visualization + safe AI development
       ↓
runtime

These capabilities should make the framework self-aware enough to support safe LLM-assisted development and architecture analysis.

Focus on determinism, inspectability, and reliability.

⸻

When Phase 0C is complete, you’ll have something very unusual in the web world:

a framework that understands its own architecture well enough to help build itself.

Then Phases 1–3 become dramatically easier and cleaner to implement.











------------------------------------------------------------------------------------------
------------------------------------------------------------------------------------------










Implemented Phase 0C on top of the existing Phase 0A/0B graph/compiler foundation, without adding parallel discovery or truth systems.

### What was added

- Graph-analyzer extension API and core analyzer registrations
  - [`GraphAnalyzer`](\/Users\/lo_fye\/code\/foundry\/src\/Compiler\/Analysis\/GraphAnalyzer.php)
  - [`ArchitectureDoctor`](\/Users\/lo_fye\/code\/foundry\/src\/Compiler\/Analysis\/ArchitectureDoctor.php)
  - Core analyzers in [`src/Compiler/Analysis/Analyzers`](\/Users\/lo_fye\/code\/foundry\/src\/Compiler\/Analysis\/Analyzers)
  - Extension integration updates:
    - [`CompilerExtension`](\/Users\/lo_fye\/code\/foundry\/src\/Compiler\/Extensions\/CompilerExtension.php)
    - [`AbstractCompilerExtension`](\/Users\/lo_fye\/code\/foundry\/src\/Compiler\/Extensions\/AbstractCompilerExtension.php)
    - [`ExtensionRegistry`](\/Users\/lo_fye\/code\/foundry\/src\/Compiler\/Extensions\/ExtensionRegistry.php)
    - [`CoreCompilerExtension`](\/Users\/lo_fye\/code\/foundry\/src\/Compiler\/Extensions\/CoreCompilerExtension.php)

- New CLI capabilities
  - [`doctor`](\/Users\/lo_fye\/code\/foundry\/src\/CLI\/Commands\/DoctorCommand.php)
    - supports `--json`, `--strict`, `--feature=<name>`
  - [`graph visualize`](\/Users\/lo_fye\/code\/foundry\/src\/CLI\/Commands\/GraphVisualizeCommand.php)
    - supports `--feature`, `--events`, `--routes`, `--caches`, `--format=mermaid|dot|json|svg`
  - [`prompt`](\/Users\/lo_fye\/code\/foundry\/src\/CLI\/Commands\/PromptCommand.php)
    - supports `--json`, `--dry-run`, `--feature-context`
  - Wiring in [`Application`](\/Users\/lo_fye\/code\/foundry\/src\/CLI\/Application.php)

- Graph-derived engines
  - Visualization engine: [`GraphVisualizer`](\/Users\/lo_fye\/code\/foundry\/src\/Compiler\/Visualization\/GraphVisualizer.php)
  - Structured prompt builder: [`GraphPromptBuilder`](\/Users\/lo_fye\/code\/foundry\/src\/Compiler\/Prompt\/GraphPromptBuilder.php)

- Docs/examples
  - [`docs/semantic-compiler-phase0c.md`](\/Users\/lo_fye\/code\/foundry\/docs\/semantic-compiler-phase0c.md)
  - [`examples/phase0c`](\/Users\/lo_fye\/code\/foundry\/examples\/phase0c)
  - README CLI/doc updates: [`README.md`](\/Users\/lo_fye\/code\/foundry\/README.md)

### Test coverage and validation

- Full test suite: `157` tests, `758` assertions, `3` skipped, passing.
- Full coverage run (`XDEBUG_MODE=coverage php vendor/bin/phpunit --coverage-text`):
  - **Lines: 91.63% (6462/7052)**

This keeps overall coverage above your 90% requirement.

33 files changed
+3020
-1
Undo
README.md
docs/semantic-compiler-phase0c.md
examples/phase0c/README.md
examples/phase0c/doctor/doctor.sample.json
examples/phase0c/prompt/prompt.sample.json
examples/phase0c/visualize/events.mermaid
examples/phase0c/visualize/routes.dot
src/CLI/Application.php
src/CLI/Commands/DoctorCommand.php
src/CLI/Commands/GraphVisualizeCommand.php
src/CLI/Commands/PromptCommand.php
src/Compiler/Analysis/AnalyzerContext.php
src/Compiler/Analysis/Analyzers/AuthAnalyzer.php
src/Compiler/Analysis/Analyzers/CacheTopologyAnalyzer.php
src/Compiler/Analysis/Analyzers/DeadCodeAnalyzer.php
src/Compiler/Analysis/Analyzers/DependencyAnalyzer.php
src/Compiler/Analysis/Analyzers/SchemaIntegrityAnalyzer.php
src/Compiler/Analysis/Analyzers/TestCoverageAnalyzer.php
src/Compiler/Analysis/ArchitectureDoctor.php
src/Compiler/Analysis/GraphAnalyzer.php
src/Compiler/Extensions/AbstractCompilerExtension.php
src/Compiler/Extensions/CompilerExtension.php
src/Compiler/Extensions/CoreCompilerExtension.php
src/Compiler/Extensions/ExtensionRegistry.php
src/Compiler/Prompt/GraphPromptBuilder.php
src/Compiler/Visualization/GraphVisualizer.php
tests/Integration/CLIPhase0CCommandsTest.php
tests/Integration/ExamplesStructureTest.php
tests/Unit/ArchitectureDoctorTest.php
tests/Unit/CLICommandMatchesTest.php
tests/Unit/ExtensionRegistryTest.php
tests/Unit/GraphPromptBuilderTest.php
tests/Unit/GraphVisualizerTest.php

worked for 21 minutes
