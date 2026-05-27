# Execution Spec: 028-ws-framework-internals-reference-from-framework-src

## Historical Import Note

This spec was imported from the explicitly marked pre-canonical archive.

- Legacy name: `28-WS - Framework Internals Reference from framework/src/`
- Legacy id: `28`
- Canonical pre-canonical id: `028`
- Imported module: `PreCanonical`
- Source archive: `_import/pre-canonical-specs.md`

## Original Pre-Canonical Spec

Goals
	1.	Give developers and LLMs a trustworthy map of the framework internals
	2.	Document major subsystems under framework/src/
	3.	Provide concise per-class summaries where useful
	4.	Keep the output deterministic and regenerable
	5.	Avoid overwhelming first-time users

⸻

Non-Goals
	•	Do not manually author one page per file
	•	Do not turn the docs into raw API dumps
	•	Do not duplicate canonical philosophy/execution/graph docs
	•	Do not make internals reference the first thing new users see

⸻

Required Output

Generate a new docs section under something like:

public/docs/framework-internals/

and corresponding machine-readable outputs under:

public/docs/generated/

Use the existing website docs pipeline. Do not replace it.

⸻

Required Pages

Create generated pages for at least:
	1.	Framework Internals Index
	•	overview of major src/ subsystems
	•	links to subsystem pages
	2.	One page per major top-level subsystem under framework/src/
Examples may include:
	•	Compiler
	•	CLI
	•	Feature
	•	Config
	•	Auth
	•	Cache
	•	Queue
	•	Events
	•	Scheduler
	•	Storage
	•	Webhook
	•	AI
	•	Observability
	•	Verification
	•	Generation
	•	Support
	3.	Optional class detail pages or expandable sections for important classes

⸻

Per-Subsystem Page Requirements

Each subsystem page should include:
	•	subsystem name
	•	directory path
	•	purpose summary
	•	major classes
	•	short description of each major class
	•	notable relationships to other subsystems
	•	notable CLI/docs links where relevant

Do not dump raw source code.

Keep each class description concise and factual.

⸻

Class Inclusion Rules

Include classes that are:
	•	public-facing within the framework internals
	•	architecturally important
	•	entry points
	•	registries
	•	drivers
	•	compilers
	•	verifiers
	•	runtime coordinators

Lower-priority helper/value classes may be grouped rather than given full individual treatment.

⸻

Source of Truth

Generate from:

framework/src/

Use:
	•	namespace
	•	class names
	•	file paths
	•	docblocks if useful
	•	lightweight static analysis of constructor/method names if helpful

Do not invent behavior not supported by code.

⸻

Generated Metadata

Also emit machine-readable JSON, for example:

content/docs/generated/framework-internals.json
content/docs/generated/framework-internals/<subsystem>.json

and publish them under:

public/docs/generated/

Include:
	•	subsystem
	•	class
	•	path
	•	summary
	•	relationships
	•	tags/categories

This should be useful for LLM context and search.

⸻

Docs Positioning

This new internals section must be discoverable, but not the first experience.

Recommended placement:
	•	linked from architecture docs
	•	linked from reference docs
	•	linked from architecture explorer
	•	linked from contributor docs

Do not make it replace “Getting Started.”

⸻

Cross-Linking

Add links where useful to:
	•	philosophy
	•	execution model
	•	graph spec
	•	architecture overview
	•	CLI reference
	•	architecture reference
	•	architecture explorer

Internals docs should support the higher-level docs, not compete with them.

⸻

Rendering Style

Optimize for:
	•	clarity
	•	scanability
	•	stable headings
	•	deterministic structure
	•	LLM readability

Avoid:
	•	giant undifferentiated lists
	•	excessive prose
	•	duplicated explanations across pages

⸻

Acceptance Criteria
	•	website generates a framework internals reference from framework/src/
	•	there is an index page plus subsystem pages
	•	output is deterministic
	•	machine-readable JSON is emitted
	•	pages are linked into the docs site
	•	docs remain navigable and not overwhelming for new users
	•	no hand-maintained per-file docs burden is introduced

⸻

Implementation Bias

Prefer:
	•	subsystem-level clarity first
	•	important-class summaries second
	•	exhaustive per-file detail only where clearly valuable

The docs should help someone answer:

“What is in the framework, where does it live, and what role does it play?”

not:

“Show me an unreadable dump of every class.”

Link to these docs from:
	•	architecture-reference
	•	architecture-explorer
	•	contributing
	•	and a short note in the root README/docs index.


------------------------------------------------------------------------------------------
