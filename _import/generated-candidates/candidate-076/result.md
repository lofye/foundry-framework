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
