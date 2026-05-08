# Spec 4I

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
