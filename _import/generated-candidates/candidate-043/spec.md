PHASE 2

Phase 0A, 0B, 0C, and 0D are now canonical.

In addition to integrating with the semantic compiler, canonical application graph, extension system, migration/versioning model, doctor/analysis tooling, and graph visualization system, all new capabilities in this phase must also integrate with the execution pipeline, feature guard model, interceptor system, and execution-plan inspection/verification tools introduced in Phase 0D.

Important rules:
- Do not introduce ad hoc middleware stacks or parallel runtime request-processing systems.
- Any auth, permission, CSRF, rate-limiting, request-validation, content-negotiation, webhook-verification, locale-resolution, streaming, or other cross-cutting behavior must use the canonical pipeline/guard/interceptor architecture where appropriate.
- New features should emit graph-visible execution plans and participate in pipeline diagnostics, inspection, and visualization.
- Where useful, new capabilities should also integrate with doctor, graph visualization, and prompt-context extraction so that LLMs and humans can inspect the resulting system structure.

In short:
All future phases must be graph-native, extension-native, migration-aware, and pipeline-native.

Before implementing this phase, adapt all new capabilities to the Foundry Phase 0 semantic compiler and canonical application graph.

Important rules for this phase:
- Notifications, API resources, OpenAPI export, docs generation, and test generation v2 must all derive from the compiled graph or graph projections.
- Do not create separate parsers or registries for notifications, APIs, docs, or tests if the same information can be represented in the graph.
- Any new source specs or config files introduced in this phase must:
  - be versioned
  - compile into explicit graph nodes and edges
  - participate in diagnostics
  - support future codemod/migration handling
- OpenAPI export must be generated from graph-linked routes, schemas, auth metadata, and response contracts.
- Docs generation must be generated from the graph, not from ad hoc filesystem scans.
- Test generation v2 must use graph knowledge such as feature dependencies, schemas, auth, events, jobs, and routes.
- Any new verify commands must operate over the graph where practical.
- Any new inspect commands must surface graph-backed reality.
- Any new notification/API/docs/test capabilities that could be extensions should be implemented in a graph-aware extension-friendly way.

In short:
Phase 2 should treat the application graph as the canonical substrate for export, documentation, notification definition, API generation, and test intelligence.

Here’s a single master prompt for Codex for Foundry Roadmap Phase 2.

This phase builds the next layer developers are highly likely to ask their LLMs for once auth, CRUD, forms, admin, uploads, and listings exist:
	•	notifications and mail
	•	API generation and OpenAPI export
	•	docs generation from source contracts
	•	deeper test generation

⸻

Master Prompt for Codex: Build Foundry Roadmap Phase 2

Build Foundry Roadmap Phase 2, focused on the next group of features developers are very likely to ask an LLM to create once the basic app shell exists.

This phase should make Foundry dramatically better at generating:
	•	email and notification flows
	•	JSON APIs
	•	OpenAPI specs
	•	project documentation derived from source contracts
	•	deeper, scenario-aware automated tests with total coverage above 90%

Everything must remain aligned with Foundry’s core architecture:
	•	feature-local structure
	•	explicit contracts
	•	deterministic generation
	•	generated runtime indexes
	•	inspectable CLI
	•	strong verification
	•	high runtime clarity
	•	very high automated test coverage, above 90%

Do not add magical side systems that bypass Foundry’s existing philosophy.

⸻

Top priorities

When tradeoffs arise, prioritize in this order:
	1.	correctness
	2.	explicitness
	3.	analyzability by LLMs
	4.	deterministic generation
	5.	very high automated test coverage, above 90%
	6.	integration with existing Foundry architecture
	7.	inspectability
	8.	runtime clarity
	9.	developer ergonomics

⸻

Phase 2 scope

Build these four major capabilities:
	1.	Mail and notifications
	2.	API generation + OpenAPI export
	3.	Docs generation from source contracts
	4.	Test generation v2

Each capability must include:
	•	generation support
	•	verification support where appropriate
	•	CLI integration
	•	tests
	•	docs
	•	example usage

⸻

1. Mail and notifications

Goal

Allow developers and LLMs to generate explicit, queueable, testable notifications without rebuilding mail delivery patterns every time.

This should cover the common prompt shapes:
	•	send a welcome email
	•	send password reset email
	•	send weekly digest
	•	notify users when X happens
	•	queue email delivery
	•	preview email templates

Required channels

Implement:
	•	mail first

Design for future expansion to:
	•	webhook
	•	SMS
	•	push

But do not build those yet unless they come nearly for free.

Required concepts

Create explicit notification definitions.

Suggested internal concepts:
	•	NotificationDefinition
	•	NotificationRegistry
	•	NotificationDispatcher
	•	NotificationChannel
	•	MailChannel
	•	MailTemplateRenderer
	•	NotificationTrace

These names can vary, but the architecture must stay explicit and inspectable.

Notification definition format

Implement a spec file format, for example notifications.yaml:

version: 1

notifications:
  - name: welcome_email
    channel: mail
    queue: default
    template: welcome_email
    input_schema:
      type: object
      additionalProperties: false
      required: [user_id]
      properties:
        user_id:
          type: string

  - name: weekly_digest
    channel: mail
    queue: default
    template: weekly_digest
    input_schema:
      type: object
      additionalProperties: false
      required: [user_id, summary_date]
      properties:
        user_id:
          type: string
        summary_date:
          type: string
          format: date

You may refine the exact shape, but keep it explicit, deterministic, and machine-readable.

New CLI

Implement:

php vendor/bin/foundry generate notification welcome_email
php vendor/bin/foundry generate notification weekly_digest

Also consider:

php vendor/bin/foundry inspect notification welcome_email
php vendor/bin/foundry verify notifications
php vendor/bin/foundry preview notification welcome_email --json

Required generation output

For each notification, generate deterministic artifacts such as:
	•	notification definition
	•	input schema
	•	template stub
	•	dispatch helper or action stub
	•	tests
	•	context manifest if appropriate

If templates live in a specific location, keep that location explicit and documented.

Required mail capabilities

Implement:
	•	queued delivery
	•	sync delivery option for development/testing
	•	template rendering
	•	plain text and/or HTML rendering strategy
	•	preview mode
	•	structured delivery tracing
	•	explicit failure reporting
	•	retry support via existing queue layer

Template system

Keep mail template rendering simple and explicit.

Requirements:
	•	deterministic template lookup
	•	explicit variable binding
	•	previewable from CLI
	•	safe escaping rules
	•	easy for LLMs to inspect

Verification requirements

Add or extend verification to check:
	•	notification definitions are valid
	•	referenced templates exist
	•	input schemas are valid
	•	referenced queues are valid
	•	notification names are unique
	•	generated indexes are synchronized

Testing requirements

Add strong tests for:
	•	notification definition parsing
	•	generation output
	•	template rendering
	•	queued dispatch
	•	sync dispatch
	•	preview output
	•	failure paths
	•	retry behavior integration
	•	verifier behavior

Example features to include

Generate or update examples for:
	•	welcome email
	•	password reset email
	•	weekly digest email

⸻

2. API generation + OpenAPI export

Goal

Allow developers and LLMs to generate JSON APIs from structured specs and export those APIs as OpenAPI documentation derived from Foundry’s existing contracts.

This should cover common prompt shapes:
	•	build a JSON API for posts
	•	expose CRUD over API
	•	generate docs for this API
	•	make errors consistent
	•	let frontend/mobile teams consume the contract safely

Required API generation

Implement API-oriented resource generation.

New CLI

php vendor/bin/foundry generate api-resource posts --spec=specs/posts.api-resource.yaml
php vendor/bin/foundry export openapi

Also consider:

php vendor/bin/foundry inspect api posts --json
php vendor/bin/foundry verify api

API resource spec

Implement a spec format such as:

version: 1
resource: posts
style: api

model:
  table: posts
  primary_key: id

fields:
  title:
    type: string
    required: true
  slug:
    type: string
    required: true
    unique: true
  body_markdown:
    type: text
    required: true
  published_at:
    type: datetime
    required: false

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

You may refine the exact format.

Required generated API features

For a posts API resource, generate something like:
	•	api_list_posts
	•	api_view_post
	•	api_create_post
	•	api_update_post
	•	api_delete_post

Required API behavior

Implement a consistent JSON API style.

Requirements:
	•	JSON response envelope or clearly defined unwrapped resource strategy
	•	standard error envelope
	•	validation error format
	•	auth error format
	•	not found format
	•	pagination metadata format
	•	content-type correctness
	•	explicit status codes

Pick a style and document it clearly. Keep it stable and deterministic.

OpenAPI export

Implement generation of an OpenAPI document from Foundry’s existing source contracts.

Must derive from:
	•	feature definitions
	•	routes
	•	input schemas
	•	output schemas
	•	auth metadata
	•	error envelope conventions where possible

Support:
	•	JSON output
	•	YAML output if easy

CLI:

php vendor/bin/foundry export openapi --format=json
php vendor/bin/foundry export openapi --format=yaml

Required OpenAPI coverage

Include:
	•	paths
	•	methods
	•	request bodies
	•	response schemas
	•	auth/security info where possible
	•	standard error responses
	•	tags/grouping by resource or feature

Verification requirements

Add checks for:
	•	API features have valid JSON schemas
	•	routes are exportable
	•	response contracts are compatible with OpenAPI generation
	•	duplicate path/method conflicts are surfaced clearly
	•	auth metadata needed for export is present

Testing requirements

Add tests for:
	•	API generation from spec
	•	response shape consistency
	•	error envelope behavior
	•	auth enforcement
	•	pagination responses
	•	OpenAPI export correctness
	•	deterministic OpenAPI output
	•	failure modes when specs are invalid

Example requirements

Add or update an example app demonstrating:
	•	one resource exposed as JSON API
	•	generated OpenAPI export
	•	auth-protected and public endpoints

⸻

3. Docs generation from source contracts

Goal

Allow Foundry to generate clear documentation directly from the same explicit contracts and indexes that power the runtime and verification.

This should cover prompt shapes like:
	•	generate route docs
	•	document all features
	•	explain auth boundaries
	•	produce onboarding docs
	•	show events/jobs/cache relationships
	•	create docs an LLM can read before editing

New CLI

Implement:

php vendor/bin/foundry generate docs
php vendor/bin/foundry generate docs --format=markdown
php vendor/bin/foundry generate docs --format=html

Also consider:

php vendor/bin/foundry inspect docs --json

Required docs outputs

Generate docs covering at least:
	•	feature catalog
	•	route catalog
	•	auth matrix
	•	event registry
	•	job registry
	•	cache registry
	•	scheduler registry
	•	schema catalog
	•	app structure explanation
	•	LLM development workflow explanation

Output formats

Implement at least:
	•	Markdown

If reasonably easy, also implement:
	•	HTML

Docs requirements

Generated docs must be:
	•	deterministic
	•	readable by humans
	•	useful to LLMs
	•	derived from source contracts and indexes
	•	grouped clearly
	•	easy to regenerate

Suggested generated docs files

For example:

docs/
  features.md
  routes.md
  auth.md
  events.md
  jobs.md
  caches.md
  schemas.md
  llm-workflow.md

You may refine the structure.

Required content areas

features.md

For each feature:
	•	name
	•	kind
	•	route or trigger
	•	input schema
	•	output schema
	•	auth requirements
	•	DB reads/writes
	•	emitted events
	•	dispatched jobs
	•	related tests

routes.md

For each route:
	•	method
	•	path
	•	feature
	•	auth summary
	•	input/output schema references

auth.md
	•	permissions
	•	strategies
	•	features requiring auth
	•	public features
	•	admin-only features where applicable

events.md / jobs.md / caches.md
	•	explicit registry listings
	•	schema references where relevant
	•	publisher/subscriber relationships where available

llm-workflow.md

Explain the intended Foundry loop:
	•	inspect
	•	edit minimum files
	•	regenerate indexes
	•	verify
	•	test

Verification requirements

Add checks for:
	•	docs generation input completeness
	•	broken schema/route references in docs pipeline
	•	deterministic output

Testing requirements

Add tests for:
	•	docs generation
	•	markdown content structure
	•	deterministic output
	•	inclusion of expected feature/route/auth data
	•	failure handling when contracts are incomplete

⸻

4. Test generation v2

Goal

Make Foundry much better at generating real tests developers and LLMs actually want, rather than only minimal scaffolding.

This should cover prompt shapes like:
	•	generate complete tests for this feature
	•	add auth failure tests
	•	add validation tests
	•	add queue/event assertions
	•	generate API tests
	•	generate scenario-based resource tests

New CLI

Implement or expand:

php vendor/bin/foundry generate tests <feature> --mode=deep
php vendor/bin/foundry generate tests <resource> --mode=resource
php vendor/bin/foundry generate tests --all-missing

You may refine the exact CLI, but it must remain explicit and deterministic.

Required test generation modes

Implement at least:
	•	basic
	•	deep

Optional but useful:
	•	resource
	•	api
	•	notification

Required test categories

Generate tests for:
	•	happy path
	•	validation failures
	•	auth failures
	•	not found cases
	•	DB side effects
	•	queue dispatch assertions
	•	event emission assertions
	•	JSON output shape
	•	list search/filter/sort/pagination behavior
	•	notification dispatch behavior
	•	API error envelope behavior

Test generation rules

Generated tests must:
	•	be deterministic
	•	reflect feature schemas and feature definitions
	•	infer likely assertions from:
	•	input schema
	•	output schema
	•	auth config
	•	events/jobs config
	•	DB config
	•	remain readable and editable

Resource-aware generation

For generated resources, create richer test packs that include:
	•	list tests
	•	create tests
	•	update tests
	•	delete tests
	•	auth tests
	•	validation tests
	•	pagination/filter/sort tests
	•	API tests for API resources

Verification requirements

Add checks for:
	•	generated test names are stable
	•	expected test files exist where requested
	•	test generation does not drift from feature contracts

Testing requirements for the test generator itself

Yes, wonderfully recursive.

Add tests for:
	•	test generation output
	•	deterministic content
	•	correct scenario selection
	•	feature-aware assertions
	•	resource-aware assertions
	•	API-aware assertions
	•	notification-aware assertions

⸻

Architecture integration requirements

All Phase 2 features must integrate cleanly into existing Foundry architecture.

Must remain feature-local

Any generated app behavior must still live under app/features/*.

Must update generated indexes where applicable

If notifications, APIs, docs metadata, or test generation introduce new registry data, integrate it explicitly.

Must remain inspectable

Anything generated or exported should be explorable through stable CLI output.

Must remain deterministic

Repeated generation from the same inputs must produce the same outputs.

Must avoid runtime magic

Prefer explicit registries, explicit schemas, explicit template/config files, and generated indexes over clever dynamic behavior.

⸻

New CLI surface to add

Implement at least:

php vendor/bin/foundry generate notification <name>
php vendor/bin/foundry verify notifications
php vendor/bin/foundry preview notification <name>

php vendor/bin/foundry generate api-resource <name> --spec=<file>
php vendor/bin/foundry export openapi --format=json
php vendor/bin/foundry export openapi --format=yaml
php vendor/bin/foundry verify api

php vendor/bin/foundry generate docs
php vendor/bin/foundry generate docs --format=markdown
php vendor/bin/foundry generate docs --format=html

php vendor/bin/foundry generate tests <target> --mode=deep
php vendor/bin/foundry generate tests --all-missing

Support --json where practical for inspect/verify/preview commands.

⸻

Documentation requirements

Update Foundry docs to explain:
	•	notifications
	•	API resource generation
	•	OpenAPI export
	•	docs generation
	•	deeper test generation
	•	how all of these fit into inspect → edit → regenerate → verify → test

Write the docs in clear technical narrative, not empty marketing fog.

They should be useful to:
	•	human developers
	•	LLMs reading the repo

⸻

Example app requirements

Update or add examples demonstrating each new capability.

At minimum, include:

Example A: notification-enabled app
	•	welcome email
	•	digest email
	•	preview flow

Example B: API app
	•	generated resource API
	•	auth-protected endpoint
	•	OpenAPI export

Example C: docs demo
	•	generated docs from example app contracts

Example D: deep test generation demo
	•	generated feature tests
	•	generated resource tests
	•	generated API tests

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
	•	export tests
	•	template rendering tests
	•	example app end-to-end tests
	•	regression tests for bugs discovered

Specific required coverage

Notifications
	•	definition parsing
	•	generation
	•	template rendering
	•	queue integration
	•	preview
	•	delivery failure paths

API generation
	•	spec parsing
	•	generated features
	•	JSON response consistency
	•	auth errors
	•	validation errors
	•	pagination
	•	OpenAPI export correctness

Docs generation
	•	docs content generation
	•	feature/route/auth/event/job inclusion
	•	deterministic output
	•	failure handling

Test generation v2
	•	scenario coverage selection
	•	generated assertions
	•	deterministic output
	•	correct handling of resource and API features

CLI
	•	command parsing
	•	JSON output
	•	failure output
	•	stable command behavior

Examples
	•	generated docs exist and are correct
	•	OpenAPI export succeeds
	•	notifications preview correctly
	•	example API works
	•	generated deep tests run successfully

Prefer meaningful tests over shallow volume.

⸻

Implementation guidance

Keep generation flat and boring

Do not introduce abstraction palaces.

Keep runtime explicit

Notifications, APIs, docs, and tests should all derive from explicit source-of-truth contracts.

Keep output readable

Generated templates, docs, tests, and OpenAPI output should be understandable by humans and LLMs.

Reuse existing contracts where possible

Foundry already has routes, feature manifests, schemas, auth metadata, jobs, events, and indexes.
Build Phase 2 from those sources instead of inventing parallel truth systems.

⸻

Suggested internal architecture additions

Codex may introduce abstractions like:
	•	NotificationGenerator
	•	NotificationVerifier
	•	NotificationPreviewer
	•	ApiResourceGenerator
	•	OpenApiExporter
	•	DocsGenerator
	•	FeatureDocsBuilder
	•	DeepTestGenerator

But only if they improve clarity, determinism, and testability.

Avoid building extra layers just to admire their hats.

⸻

Deliverables

Produce:
	1.	full implementation of Foundry Roadmap Phase 2
	2.	updated CLI commands
	3.	new spec/config formats where needed
	4.	updated verifiers
	5.	notification template support
	6.	OpenAPI export support
	7.	docs generation support
	8.	improved test generation support
	9.	updated docs
	10.	updated example apps
	11.	very high automated test coverage

⸻

Final instruction

Build Phase 2 of Foundry so it becomes dramatically better at the next layer of tasks developers already ask LLMs to do every day:
	•	send email and notifications
	•	generate APIs
	•	export API contracts
	•	generate project docs
	•	generate deeper tests

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

Coverage is now above the 90% requirement.

- Full suite + coverage command run: `/bin/zsh -lc 'XDEBUG_MODE=coverage php vendor/bin/phpunit --coverage-text'`
- Result: **Lines 90.28% (10650/11797)**
- Suite status: **204 tests**, **1046 assertions**, **3 skipped**, no failures

Targeted coverage tests added/expanded in:

- [tests/Unit/NotificationTemplateRendererTest.php](/Users/lo_fye/code/foundry/tests/Unit/NotificationTemplateRendererTest.php)
- [tests/Unit/NotificationPreviewerTest.php](/Users/lo_fye/code/foundry/tests/Unit/NotificationPreviewerTest.php)
- [tests/Unit/DeepTestGeneratorTest.php](/Users/lo_fye/code/foundry/tests/Unit/DeepTestGeneratorTest.php)
- [tests/Unit/PhaseTwoVerifiersTest.php](/Users/lo_fye/code/foundry/tests/Unit/PhaseTwoVerifiersTest.php)

This keeps overall coverage compliant for continuing into Phase 1/2 work.

worked for 38 mins
