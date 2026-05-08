What features should I have Codex add next, based on what developers are most likely to ask their LLMs to create for them. Please spec as many as you think are necessary for a well-rounded modern web framework, and I'll get Codex to build them.

---

PHASE 1

Phase 0A, 0B, 0C, and 0D are now canonical.

In addition to integrating with the semantic compiler, canonical application graph, extension system, migration/versioning model, doctor/analysis tooling, and graph visualization system, all new capabilities in this phase must also integrate with the execution pipeline, feature guard model, interceptor system, and execution-plan inspection/verification tools introduced in Phase 0D.

Important rules:
- Do not introduce ad hoc middleware stacks or parallel runtime request-processing systems.
- Any auth, permission, CSRF, rate-limiting, request-validation, content-negotiation, webhook-verification, locale-resolution, streaming, or other cross-cutting behavior must use the canonical pipeline/guard/interceptor architecture where appropriate.
- New features should emit graph-visible execution plans and participate in pipeline diagnostics, inspection, and visualization.
- Where useful, new capabilities should also integrate with doctor, graph visualization, and prompt-context extraction so that LLMs and humans can inspect the resulting system structure.

In short:
All future phases must be graph-native, extension-native, migration-aware, and pipeline-native.


Before implementing this phase, adapt all generation, verification, and inspection work to the new Foundry Phase 0 semantic compiler architecture.

Important rules for this phase:
- Do not introduce any new parallel truth systems.
- All new starter kits, resources, forms, admin features, uploads, and listing/toolkit metadata must compile into the canonical application graph.
- Any runtime indexes or generated metadata introduced by this phase must be emitted as projections from the graph, not generated independently.
- Any new spec/config formats introduced in this phase must:
  - be versioned
  - have migration/codemod support hooks
  - normalize cleanly into the graph IR
  - participate in compiler diagnostics
- Any new verification logic introduced in this phase must operate over the compiled graph where practical, rather than reparsing source files independently.
- Any new inspect commands introduced in this phase must query the graph or graph-derived projections.
- Any new generation commands must emit source-of-truth files first, then rely on compile/projection passes to produce runtime artifacts.
- Reuse the Phase 0 diagnostics, graph inspection, impact analysis, extension hooks, and migration/versioning systems wherever possible.
- If any part of this phase conflicts with the compiler-layer architecture, revise the phase implementation so the compiler layer remains canonical.

In short:
Phase 1 features must become graph-native Foundry capabilities, not bolt-on generators.

Here’s a single master prompt for Codex to build Foundry Roadmap Phase 1 — the highest-leverage next layer that will make Foundry feel like a real modern framework developers can hand to an LLM and actually get useful app slices back.

⸻

Master Prompt for Codex: Build Foundry Roadmap Phase 1

Build Foundry Roadmap Phase 1, focused on the features developers are most likely to ask an LLM to create when starting a real application.

This phase should make Foundry dramatically better at generating complete, useful app slices with deterministic structure, explicit contracts, strong verification, and test coverage above 90%.

Primary goal

Extend Foundry so developers can reliably prompt for:
	•	starter auth apps
	•	CRUD resources
	•	forms and validation UIs
	•	admin list/detail/edit screens
	•	uploads/media
	•	search/filter/sort/pagination

This phase must feel native to Foundry’s philosophy:
	•	feature-local architecture
	•	explicit contracts
	•	deterministic generation
	•	generated runtime indexes
	•	inspectable CLI
	•	strong verification
	•	high runtime clarity
	•	very high automated test coverage above 90%

Do not build these as a separate sub-framework bolted awkwardly to the side.
They must integrate cleanly into Foundry’s existing generation, verification, and feature/index model.

⸻

Top priorities

When tradeoffs arise, prioritize in this order:
	1.	correctness
	2.	explicitness
	3.	analyzability by LLMs
	4.	deterministic generation
	5.	very high automated test coverage above 90%
	6.	integration with existing Foundry architecture
	7.	runtime clarity
	8.	developer ergonomics
	9.	visual polish

⸻

Phase 1 scope

Build these six major capabilities:
	1.	Starter kits: auth + app shell
	2.	Resource generator: CRUD from a schema
	3.	Forms and field component layer
	4.	Admin back-office kit
	5.	Uploads and media pipeline
	6.	Search/filter/sort/pagination toolkit

Each capability must include:
	•	generation support
	•	verification support where appropriate
	•	integration with existing index generation
	•	integration with existing inspect commands where appropriate
	•	strong automated tests
	•	docs/examples

⸻

1. Starter kits: auth + app shell

Goal

Allow Foundry to generate a new app foundation that includes common authentication flows and baseline app structure so developers and LLMs do not need to reinvent auth on every new project.

Required starter kits

Implement at least:
	•	server-rendered
	•	api

New CLI

php vendor/bin/foundry generate starter server-rendered
php vendor/bin/foundry generate starter api

Support:
	•	--force
	•	--json
	•	--name=... if appropriate
	•	deterministic output

server-rendered starter must include
	•	register
	•	login
	•	logout
	•	forgot password
	•	reset password
	•	email verification
	•	account settings
	•	dashboard
	•	CSRF support
	•	session auth
	•	baseline app layout
	•	flash message pattern
	•	error page pattern

api starter must include
	•	token auth
	•	login
	•	logout / token revoke
	•	/me
	•	standard JSON API error envelope
	•	rate limiting defaults
	•	consistent auth middleware / strategy wiring

Required generated features

Place these under Foundry feature-local architecture, for example:

app/features/register_user
app/features/login_user
app/features/logout_user
app/features/request_password_reset
app/features/reset_password
app/features/verify_email
app/features/view_dashboard
app/features/view_account_settings
app/features/update_account_settings

For API starter, use corresponding API-oriented names if needed, but keep naming deterministic and obvious.

Required generated artifacts
	•	feature manifests
	•	schemas
	•	action files
	•	tests
	•	context manifests
	•	required platform config updates
	•	required migrations
	•	updated generated indexes

Required migrations

At minimum, generate tables or migration definitions for:
	•	users
	•	password reset tokens or equivalent
	•	email verification support if needed
	•	sessions / tokens depending on auth mode

Verification requirements

Add or extend verifiers to check:
	•	auth starter required features exist
	•	auth routes are indexed
	•	required migrations exist
	•	auth-required features declare valid auth strategy
	•	generated indexes are synchronized

Testing requirements

Add strong automated tests for:
	•	starter generation succeeds
	•	expected files are created
	•	generated features verify cleanly
	•	register/login/logout flow works
	•	password reset flow works
	•	email verification flow works
	•	API token flow works
	•	auth failure paths work
	•	generated indexes contain correct routes/features

⸻

2. Resource generator: CRUD from a schema

Goal

Allow a developer or LLM to define a resource in a structured spec and generate a full feature pack for standard CRUD operations.

New CLI

php vendor/bin/foundry generate resource posts --spec=specs/posts.resource.yaml

Support deterministic generation.

Resource spec format

Implement a resource spec format like:

version: 1
resource: posts
style: server-rendered

model:
  table: posts
  primary_key: id

fields:
  title:
    type: string
    required: true
    maxLength: 200
    list: true
    form: text

  slug:
    type: string
    required: true
    unique: true
    list: true
    form: text

  body_markdown:
    type: text
    required: true
    form: textarea

  published_at:
    type: datetime
    required: false
    form: datetime

auth:
  list: posts.view
  view: posts.view
  create: posts.create
  update: posts.update
  delete: posts.delete

features:
  - list
  - view
  - create
  - update
  - delete

You may refine this format, but keep it explicit, deterministic, and machine-readable.

Generated features

For a posts resource, generate:
	•	list_posts
	•	view_post
	•	create_post
	•	update_post
	•	delete_post

Generated artifacts

For each generated feature, create:
	•	feature.yaml
	•	action.php
	•	input.schema.json where appropriate
	•	output.schema.json
	•	queries.sql
	•	context.manifest.json
	•	tests

Also generate or update:
	•	permissions if requested
	•	list/filter config if applicable
	•	resource-level docs metadata
	•	indexes

CRUD behavior requirements

list
	•	pagination
	•	search/filter hooks if configured
	•	sort support if configured

view
	•	explicit lookup by primary identifier or slug-like field if configured

create
	•	validation
	•	insert query
	•	success response/page redirect

update
	•	validation
	•	update query
	•	existing record loading
	•	auth check

delete
	•	delete confirmation or action
	•	auth check

Verification requirements

Extend verification to ensure:
	•	all resource-generated features are structurally valid
	•	referenced queries exist
	•	auth permissions referenced are valid
	•	generated migrations match required fields where appropriate
	•	duplicate feature generation is handled safely and deterministically

Testing requirements

Add strong tests for:
	•	resource generation from spec
	•	deterministic file output
	•	list/create/update/delete feature verification
	•	generated queries match expected names
	•	CRUD integration flow against SQLite
	•	auth rules on CRUD features
	•	failure modes for invalid spec input

⸻

3. Forms and field component layer

Goal

Allow Foundry to generate consistent server-rendered forms from schemas and/or resource specs so LLMs do not need to handcraft repetitive form markup every time.

Requirements

Create a schema-driven form system with support for:
	•	text
	•	textarea
	•	email
	•	password
	•	select
	•	radio
	•	checkbox
	•	datetime
	•	hidden
	•	file
	•	repeatable simple arrays like tags

Output style

Use server-rendered HTML first. Keep JS minimal.

Required capabilities
	•	field rendering helpers
	•	validation error display
	•	old input replay / sticky values
	•	label + help text support
	•	accessible IDs and error associations
	•	CSRF field helper
	•	deterministic markup structure

Form metadata

Support either:
	•	UI metadata embedded in resource specs
	•	or a dedicated lightweight form config format
	•	or both

Whatever you choose, it must remain explicit and deterministic.

Integration requirements

Generated create_* and update_* resource features must automatically use this form layer.

Testing requirements

Add tests for:
	•	form generation from schema/resource spec
	•	field rendering for all supported field types
	•	validation errors render correctly
	•	old input values are preserved
	•	file fields render correctly
	•	select/radio/checkbox fields bind correctly

⸻

4. Admin back-office kit

Goal

Allow Foundry to generate common admin interfaces that developers repeatedly ask LLMs to build: list tables, filters, moderation queues, row actions, and bulk actions.

New CLI

php vendor/bin/foundry generate admin-resource posts

Support structured config input if needed.

Admin spec format

Implement something like:

resource: posts
table:
  columns:
    - title
    - slug
    - status
    - created_at
filters:
  - status
  - created_at
bulk_actions:
  - delete
  - publish
row_actions:
  - edit
  - delete

You may refine this format.

Required generated features

For an admin resource, generate at least:
	•	admin_list_posts
	•	admin_view_post if useful
	•	admin_update_post
	•	admin_delete_post
	•	admin_bulk_update_posts if bulk actions selected

Requirements
	•	admin-only auth guard
	•	list table
	•	filter form
	•	search
	•	sort
	•	pagination
	•	row actions
	•	bulk actions
	•	clear table column mapping
	•	moderation queue pattern where useful

UI requirements

Keep UI simple, explicit, and server-rendered.

Verification requirements

Ensure:
	•	admin features declare admin auth
	•	admin routes are indexed
	•	configured columns/filters/actions are valid against resource fields

Testing requirements

Add tests for:
	•	admin resource generation
	•	auth restriction to admin users
	•	list/filter/pagination behavior
	•	bulk action execution
	•	invalid config handling

⸻

5. Uploads and media pipeline

Goal

Provide a first-class upload/media feature set so developers and LLMs can add avatars, attachments, and media handling without rebuilding upload security and storage logic from scratch.

New CLI

php vendor/bin/foundry generate uploads avatar
php vendor/bin/foundry generate uploads attachments

You may also support a more explicit spec-driven form if needed.

Required support
	•	local storage
	•	S3-compatible storage
	•	safe file naming
	•	file validation
	•	mime/type restrictions
	•	size restrictions
	•	metadata storage
	•	ownership rules
	•	signed access where appropriate
	•	optional image variant generation job

Suggested schema/tables

Implement something like:

files
	•	id
	•	disk
	•	path
	•	original_name
	•	mime_type
	•	size_bytes
	•	checksum if desired
	•	created_at

file_attachments
	•	id
	•	file_id
	•	owner_type
	•	owner_id
	•	field_name
	•	created_at

You may adapt the exact schema, but keep it explicit and relational.

Required feature patterns

Support at least:
	•	single avatar upload pattern
	•	generic attachment upload pattern

Required integration

The forms layer must support file fields.
Generated features must be able to attach uploaded files to resource records safely.

Optional v1 bonus
	•	image variant generation job
	•	thumbnail metadata
	•	basic image dimension capture

Verification requirements

Add checks for:
	•	upload feature config validity
	•	disk/storage target validity
	•	file field schema consistency

Testing requirements

Add tests for:
	•	upload feature generation
	•	local file upload flow
	•	file validation failure
	•	attachment ownership behavior
	•	metadata persistence
	•	signed access behavior if implemented

⸻

6. Search / filter / sort / pagination toolkit

Goal

Give Foundry a canonical listing/query toolkit so generated list views and APIs feel complete and consistent.

Required support
	•	text search using configured fields
	•	exact filters
	•	enum filters
	•	date range filters
	•	sort whitelist
	•	page pagination
	•	optional cursor pagination design hooks if useful
	•	normalized query parameter handling

New list config

Implement a config format like:

resource: posts
search:
  fields: [title, slug]
filters:
  status:
    type: enum
  created_from:
    type: date
  created_to:
    type: date
sort:
  allowed: [created_at, title]
  default: -created_at
pagination:
  mode: page
  per_page: 25

Integration requirements

The resource generator and admin resource generator must both be able to consume this listing toolkit.

Query generation requirements

Generate explicit named queries or query-building logic in a deterministic, inspectable way.
Do not introduce opaque runtime magic.

Verification requirements

Add checks for:
	•	invalid search fields
	•	invalid filter field references
	•	invalid sort configuration
	•	invalid pagination config

Testing requirements

Add tests for:
	•	search behavior
	•	exact filters
	•	date filters
	•	sort whitelisting
	•	pagination behavior
	•	invalid query param handling
	•	deterministic generated output from list configs

⸻

Architecture integration requirements

All six capabilities must integrate into Foundry’s existing architecture.

Must use Foundry feature-local structure

Generated output must live under app/features/* using Foundry conventions.

Must update generated indexes

Any generated starter/resource/admin/upload features must properly feed:
	•	routes.php
	•	feature_index.php
	•	schema_index.php
	•	permission_index.php
	•	event_index.php
	•	job_index.php
	•	cache_index.php
	•	scheduler_index.php
	•	webhook_index.php

where applicable.

Must integrate with existing commands

Where appropriate, ensure generated features work with:
	•	inspect
	•	generate indexes
	•	verify feature
	•	verify contracts
	•	verify auth
	•	verify jobs
	•	other relevant existing commands

Must remain deterministic

Repeated generation from the same spec must produce the same result.

Must remain inspectable

Generated outputs must be understandable by humans and LLMs.

Avoid introducing runtime-discovered magic or hidden conventions.

⸻

New CLI surface to add

Implement at least these commands:

php vendor/bin/foundry generate starter server-rendered
php vendor/bin/foundry generate starter api

php vendor/bin/foundry generate resource <name> --spec=<file>
php vendor/bin/foundry generate admin-resource <name>
php vendor/bin/foundry generate uploads avatar
php vendor/bin/foundry generate uploads attachments

If useful, also add:

php vendor/bin/foundry inspect resource <name>
php vendor/bin/foundry verify resource <name>

All generation and verification commands should support --json where practical.

⸻

Documentation requirements

Update or add documentation covering:
	•	starter kits
	•	resource generation
	•	form generation
	•	admin resources
	•	uploads/media
	•	listing toolkit
	•	how these interact with Foundry’s inspect/generate/verify loop

Write docs as clear technical narrative, not fluff.

The docs should help both:
	•	human developers
	•	LLMs reading the project

⸻

Example app requirements

Update or add example apps that demonstrate these new capabilities.

At minimum, include examples for:

Example A: starter auth app

Demonstrate:
	•	register/login/logout
	•	dashboard
	•	account settings

Example B: blog/resource app

Demonstrate:
	•	generated CRUD
	•	forms
	•	list/search/filter/sort/pagination

Example C: admin panel

Demonstrate:
	•	admin list
	•	bulk action
	•	moderation or publish/unpublish flow

Example D: uploads app

Demonstrate:
	•	avatar or attachment uploads

⸻

Testing requirements

This is reusable framework infrastructure.
Aim for extremely high test coverage across all new functionality.

Required categories

Add:
	•	unit tests
	•	integration tests
	•	generator tests
	•	verifier tests
	•	CLI tests
	•	end-to-end example app tests
	•	regression tests for any bugs discovered

Specific required coverage

Starter kits
	•	generated files
	•	routes
	•	migrations
	•	auth flows
	•	failure paths

Resource generator
	•	spec parsing
	•	generated feature packs
	•	CRUD execution
	•	deterministic output

Forms
	•	field rendering
	•	errors
	•	sticky values
	•	accessibility hooks

Admin
	•	auth restriction
	•	filters
	•	bulk actions
	•	generation

Uploads
	•	storage behavior
	•	validation
	•	metadata persistence
	•	ownership rules

Search/filter/sort/pagination
	•	query normalization
	•	valid/invalid filters
	•	sort whitelist
	•	pagination output

CLI
	•	command parsing
	•	JSON output
	•	failure output

Examples
	•	example apps verify cleanly
	•	example flows actually work

Prefer meaningful tests over inflated but shallow coverage.

⸻

Implementation guidance

Keep generation flat and boring

Avoid unnecessary indirection layers, service pyramids, or runtime wizardry.

Keep runtime explicit

Favor explicit query definitions, generated configs, and plain PHP arrays/classes over magical dynamic behavior.

Keep output readable

Generated files should be stable, understandable, and suitable for LLM inspection in future runs.

Keep extension points clear

It is okay to introduce pack/spec abstractions, but they must remain explicit and inspectable.

⸻

Suggested internal architecture additions

Codex may introduce internal abstractions like:
	•	StarterGenerator
	•	ResourceGenerator
	•	AdminResourceGenerator
	•	UploadFeatureGenerator
	•	ListConfigGenerator
	•	FormSchemaRenderer
	•	ResourceVerifier

But do not build abstraction towers for their own sake.
Only introduce abstractions that improve determinism, testability, and clarity.

⸻

Deliverables

Produce:
	1.	full implementation of Foundry Roadmap Phase 1
	2.	updated CLI commands
	3.	new spec formats where needed
	4.	updated verifiers
	5.	updated/generated stubs
	6.	updated docs
	7.	updated example apps
	8.	very high automated test coverage
	9.	benchmark notes if any meaningful performance implications arise

⸻

Final instruction

Build Phase 1 of Foundry so it becomes dramatically better at the kinds of tasks developers already ask LLMs to do every day:
	•	create an app with auth
	•	scaffold CRUD
	•	build forms
	•	add admin screens
	•	support uploads
	•	add search/filter/sort/pagination

Keep everything aligned with Foundry’s core philosophy:
	•	explicit contracts
	•	feature-locality
	•	deterministic generation
	•	inspectable reality
	•	generated indexes
	•	strong verification
	•	high test coverage

Do not optimize for cleverness.
Optimize for repeatable, understandable, LLM-friendly software construction.
Ensure total test coverage is above 90%.




















==========================================================================================



















Implemented. Phase 1 is now graph-native, extension-native, migration-aware, and pipeline-native, and coverage is back above 90%.

**What I added**
- New Phase 1 CLI surfaces:
  - `generate starter ...`, `generate resource ...`, `generate admin-resource ...`, `generate uploads ...`
  - `inspect resource <name>`
  - `verify resource <name>`
  - Command wiring in [Application.php](/Users/lo_fye/code/foundry/src/CLI/Application.php), [GeneratePhaseOneCommand.php](/Users/lo_fye/code/foundry/src/CLI/Commands/GeneratePhaseOneCommand.php), [InspectResourceCommand.php](/Users/lo_fye/code/foundry/src/CLI/Commands/InspectResourceCommand.php), [VerifyResourceCommand.php](/Users/lo_fye/code/foundry/src/CLI/Commands/VerifyResourceCommand.php)

- New generation subsystem:
  - [StarterGenerator.php](/Users/lo_fye/code/foundry/src/Generation/StarterGenerator.php)
  - [ResourceGenerator.php](/Users/lo_fye/code/foundry/src/Generation/ResourceGenerator.php)
  - [AdminResourceGenerator.php](/Users/lo_fye/code/foundry/src/Generation/AdminResourceGenerator.php)
  - [UploadsGenerator.php](/Users/lo_fye/code/foundry/src/Generation/UploadsGenerator.php)
  - [FormSchemaRenderer.php](/Users/lo_fye/code/foundry/src/Generation/FormSchemaRenderer.php)
  - Extended [FeatureGenerator.php](/Users/lo_fye/code/foundry/src/Generation/FeatureGenerator.php) with array-based generation + `--force` support and Phase 1 metadata passthrough.

- Compiler/graph integration for Phase 1 specs:
  - Discovery of `app/specs/*/*.yaml` in [SourceScanner.php](/Users/lo_fye/code/foundry/src/Compiler/SourceScanner.php) and [DiscoveryPass.php](/Users/lo_fye/code/foundry/src/Compiler/Passes/DiscoveryPass.php)
  - New pass [PhaseOneSpecPass.php](/Users/lo_fye/code/foundry/src/Compiler/Passes/PhaseOneSpecPass.php) to compile spec nodes/edges + diagnostics.
  - New IR node types in [Nodes.php](/Users/lo_fye/code/foundry/src/Compiler/IR/Nodes.php): `starter_kit`, `resource`, `admin_resource`, `upload_profile`, `listing_config`, `form_definition`.
  - New projections in [PhaseOneProjectionEmitters.php](/Users/lo_fye/code/foundry/src/Compiler/Projection/PhaseOneProjectionEmitters.php): `starter_index.php`, `resource_index.php`, `admin_resource_index.php`, `upload_profile_index.php`, `listing_index.php`, `form_index.php`.
  - Graph verifier updated in [GraphVerifier.php](/Users/lo_fye/code/foundry/src/Compiler/GraphVerifier.php).

- Extension/codemod integration:
  - New extension [PhaseOneCompilerExtension.php](/Users/lo_fye/code/foundry/src/Compiler/Extensions/PhaseOneCompilerExtension.php) registered by default in [ExtensionRegistry.php](/Users/lo_fye/code/foundry/src/Compiler/Extensions/ExtensionRegistry.php)
  - New codemod [PhaseOneSpecNormalizeCodemod.php](/Users/lo_fye/code/foundry/src/Compiler/Codemod/PhaseOneSpecNormalizeCodemod.php)

- Inspection/analysis updates:
  - Resource-aware impact/analyzer/visual labels updates in:
    - [ImpactAnalyzer.php](/Users/lo_fye/code/foundry/src/Compiler/Analysis/ImpactAnalyzer.php)
    - [AnalyzerContext.php](/Users/lo_fye/code/foundry/src/Compiler/Analysis/AnalyzerContext.php)
    - [GraphVisualizer.php](/Users/lo_fye/code/foundry/src/Compiler/Visualization/GraphVisualizer.php)

- Docs/examples:
  - [roadmap-phase1.md](/Users/lo_fye/code/foundry/docs/roadmap-phase1.md)
  - [examples/phase1/README.md](/Users/lo_fye/code/foundry/examples/phase1/README.md)
  - README CLI/docs/examples updated in [README.md](/Users/lo_fye/code/foundry/README.md)

**Tests added**
- [CLIPhase1CommandsTest.php](/Users/lo_fye/code/foundry/tests/Integration/CLIPhase1CommandsTest.php)
- [CLIPhase1CommandErrorsTest.php](/Users/lo_fye/code/foundry/tests/Integration/CLIPhase1CommandErrorsTest.php)
- [PhaseOneSpecCompilerTest.php](/Users/lo_fye/code/foundry/tests/Unit/PhaseOneSpecCompilerTest.php)
- [PhaseOneSpecPassDiagnosticsTest.php](/Users/lo_fye/code/foundry/tests/Unit/PhaseOneSpecPassDiagnosticsTest.php)
- [FormSchemaRendererTest.php](/Users/lo_fye/code/foundry/tests/Unit/FormSchemaRendererTest.php)
- [ResourceVerifierTest.php](/Users/lo_fye/code/foundry/tests/Unit/ResourceVerifierTest.php)
- [PhaseOneSpecNormalizeCodemodTest.php](/Users/lo_fye/code/foundry/tests/Unit/PhaseOneSpecNormalizeCodemodTest.php)

**Validation**
- `php vendor/bin/phpunit`: passed (`172` tests, `905` assertions, `3` skipped)
- Coverage command: `XDEBUG_MODE=coverage php vendor/bin/phpunit --coverage-text`
  - **Lines: 90.13% (9192/10199)**

So yes: coverage is currently above the 90% floor after Phase 1 changes.

22 files changed
+358
-30
Undo
README.md
src/CLI/Application.php
src/CLI/CommandContext.php
src/Compiler/Analysis/AnalyzerContext.php
src/Compiler/Analysis/ImpactAnalyzer.php
src/Compiler/CompilationState.php
src/Compiler/Extensions/ExtensionRegistry.php
src/Compiler/GraphVerifier.php
src/Compiler/IR/Nodes.php
src/Compiler/IR/StarterKitNode.php
src/Compiler/Passes/DiscoveryPass.php
src/Compiler/Passes/NormalizePass.php
src/Compiler/Passes/ValidatePass.php
src/Compiler/Projection/CoreProjectionEmitters.php
src/Compiler/Prompt/GraphPromptBuilder.php
src/Compiler/SourceScanner.php
src/Compiler/Visualization/GraphVisualizer.php
src/Generation/FeatureGenerator.php
tests/Integration/ExamplesStructureTest.php
tests/Unit/CLICommandMatchesTest.php
tests/Unit/FormSchemaRendererTest.php
tests/Unit/PhaseOneSpecNormalizeCodemodTest.php


worked for 29 minutes
