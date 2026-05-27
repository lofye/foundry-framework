# Execution Spec: 003-roadmap-phase-3-billing-workflows-orchestration-search-localization-roles-inspect

## Historical Import Note

This spec was imported from the explicitly marked pre-canonical archive.

- Legacy name: `3 - Roadmap Phase 3 - Billing, Workflows, Orchestration, Search, Localization, Roles, Inspect`
- Legacy id: `3`
- Canonical pre-canonical id: `003`
- Imported module: `PreCanonical`
- Source archive: `_import/pre-canonical-specs.md`

## Original Pre-Canonical Spec

Master Prompt for Codex: Build Foundry Roadmap Phase 3

Build Foundry Roadmap Phase 3, focused on the next layer of modern framework capabilities developers are likely to ask an LLM to add once auth, CRUD, forms, admin, uploads, notifications, APIs, docs, and deep test generation exist.

This phase should make Foundry significantly better at handling:
	•	billing and subscriptions
	•	workflows and finite state transitions
	•	background orchestration
	•	richer search adapters
	•	realtime updates
	•	localization / i18n
	•	roles and policies scaffolding
	•	a visual inspect UI for developers

Everything must remain aligned with Foundry’s core architecture:
	•	feature-local structure
	•	explicit contracts
	•	deterministic generation
	•	generated runtime indexes
	•	inspectable CLI
	•	strong verification
	•	high runtime clarity
	•	very high automated test coverage, above 90%

Do not build magical bolt-on subsystems that ignore Foundry’s philosophy.

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
	10.	visual polish

⸻

Phase 3 scope

Build these eight major capabilities:
	1.	Billing and subscriptions pack
	2.	Workflows / finite state machines
	3.	Background orchestration layer
	4.	Search adapters beyond basic SQL search
	5.	Realtime updates
	6.	Localization / i18n
	7.	Roles and policies scaffolding
	8.	Visual inspect UI

Each capability must include:
	•	generation support where appropriate
	•	verification support where appropriate
	•	CLI integration
	•	tests
	•	docs
	•	example usage

⸻

1. Billing and subscriptions pack

Goal

Allow developers and LLMs to generate common SaaS billing behavior without re-inventing plan handling, checkout, portal links, subscription sync, and webhook plumbing each time.

This should cover common prompt shapes:
	•	add Stripe billing
	•	create plans
	•	add checkout
	•	sync subscription status
	•	handle billing webhooks
	•	show invoice history
	•	add trial support

Scope

Start with one provider first:
	•	Stripe

Design for later expansion to:
	•	Paddle
	•	Braintree
	•	others

But do not build multiple providers in this phase unless abstraction remains simple and explicit.

Required concepts

Suggested internal concepts:
	•	BillingProvider
	•	StripeBillingProvider
	•	PlanRegistry
	•	SubscriptionSyncService
	•	BillingWebhookVerifier
	•	BillingPortalService

These names can vary, but architecture must remain explicit and inspectable.

Required billing features

Generate explicit feature packs for:
	•	create_checkout_session
	•	view_billing_portal
	•	handle_billing_webhook
	•	list_invoices
	•	view_current_subscription

Optionally:
	•	cancel_subscription
	•	resume_subscription
	•	change_plan

New CLI

Implement something like:

php vendor/bin/foundry generate billing stripe
php vendor/bin/foundry inspect billing --json
php vendor/bin/foundry verify billing

If a plan config file is used, keep it explicit and machine-readable.

Plan config format

Implement a config/spec format such as:

version: 1

provider: stripe

plans:
  - key: starter
    display_name: Starter
    price_id: price_123
    interval: month
    trial_days: 14

  - key: pro
    display_name: Pro
    price_id: price_456
    interval: month
    trial_days: 14

You may refine this format.

Required data model support

Create migrations/tables or equivalent structures for:
	•	subscriptions
	•	subscription_items if needed
	•	billing_customers if needed
	•	invoices or invoice references if needed
	•	billing events / webhook receipts if useful

Keep it explicit and relational.

Webhook handling requirements

Implement:
	•	signature verification
	•	idempotency handling
	•	event persistence or receipt tracking
	•	deterministic mapping from provider events to internal actions
	•	clear failure handling

Verification requirements

Add checks for:
	•	provider config validity
	•	required environment/config keys present
	•	plan config validity
	•	webhook routes indexed
	•	billing features structurally valid

Testing requirements

Add strong tests for:
	•	billing generation
	•	plan config parsing
	•	checkout session creation flow
	•	webhook verification
	•	webhook idempotency
	•	subscription status sync
	•	invoice listing behavior
	•	failure paths
	•	verifier behavior

Example app requirements

Add or update an example app demonstrating:
	•	plan config
	•	checkout flow
	•	webhook handling
	•	subscription status display

Total test coverage should be above 90%.

⸻

2. Workflows / finite state machines

Goal

Allow developers and LLMs to model business processes explicitly instead of scattering status transitions across ad hoc feature logic.

This should cover common prompt shapes:
	•	add approval workflow
	•	add order state transitions
	•	add publishing workflow
	•	add moderation workflow
	•	prevent invalid transitions
	•	log transitions

Required capabilities

Implement a lightweight, explicit workflow/state machine system.

New concepts

Suggested internal concepts:
	•	WorkflowDefinition
	•	WorkflowRegistry
	•	TransitionGuard
	•	WorkflowEngine
	•	TransitionLog

These can vary, but must remain explicit and testable.

Workflow definition format

Implement a spec such as workflow.yaml:

version: 1
resource: posts

states:
  - draft
  - review
  - published
  - archived

transitions:
  submit_for_review:
    from: [draft]
    to: review

  publish:
    from: [review]
    to: published

  archive:
    from: [published]
    to: archived

Support optional guards, emitted events, and audit metadata.

Example with guard:

transitions:
  publish:
    from: [review]
    to: published
    permission: posts.publish
    emit:
      - post.published

Required behavior
	•	validate allowed transitions
	•	reject invalid transitions clearly
	•	support transition guards
	•	emit configured events
	•	record transition log / audit trail
	•	integrate with feature-local actions

New CLI

Implement:

php vendor/bin/foundry generate workflow posts --spec=specs/posts.workflow.yaml
php vendor/bin/foundry inspect workflow posts --json
php vendor/bin/foundry verify workflows

Integration requirements

Generated or configured workflows must be usable by features like:
	•	publish_post
	•	approve_comment
	•	moderate_user
	•	fulfill_order

Keep the integration explicit. Do not bury state changes behind invisible hooks.

Verification requirements

Add checks for:
	•	valid state sets
	•	valid transitions
	•	missing states
	•	impossible transitions
	•	unknown guard permissions
	•	duplicate transition names

Testing requirements

Add strong tests for:
	•	workflow parsing
	•	valid transition execution
	•	invalid transition rejection
	•	permission-guarded transitions
	•	transition event emission
	•	transition log persistence
	•	verifier behavior

Example requirements

Add an example workflow, such as:
	•	post publishing workflow
	•	order workflow
	•	moderation workflow

⸻

3. Background orchestration layer

Goal

Allow developers and LLMs to model multi-step background processes beyond single jobs.

This should cover prompt shapes like:
	•	run a chain of jobs
	•	fan out processing
	•	wait for subtasks
	•	show workflow progress
	•	retry failed stages
	•	resume failed workflows

Scope

Build a durable orchestration layer on top of Foundry’s existing queue/job system.

Required capabilities

Implement support for:
	•	chained jobs
	•	fan-out / fan-in
	•	workflow status tracking
	•	workflow step tracking
	•	retry strategy per step or workflow
	•	failure recovery hooks
	•	progress inspection

Suggested concepts
	•	OrchestrationDefinition
	•	WorkflowRun
	•	WorkflowStepRun
	•	Orchestrator
	•	WorkflowProgressTracker

These can vary.

Orchestration spec

Implement a definition format such as:

version: 1
name: process_uploaded_document

steps:
  - name: extract_text
    job: extract_document_text

  - name: generate_summary
    job: generate_document_summary
    depends_on: [extract_text]

  - name: classify_document
    job: classify_document
    depends_on: [extract_text]

  - name: finalize
    job: finalize_document_processing
    depends_on: [generate_summary, classify_document]

Support retries, queue names, and failure actions if practical.

New CLI

Implement:

php vendor/bin/foundry generate orchestration process_uploaded_document --spec=specs/process_uploaded_document.yaml
php vendor/bin/foundry inspect orchestration process_uploaded_document --json
php vendor/bin/foundry verify orchestrations

Required persistence

Create data structures for:
	•	workflow runs
	•	step runs
	•	statuses
	•	timestamps
	•	failure info
	•	progress metadata

Verification requirements

Add checks for:
	•	unknown jobs
	•	circular dependencies
	•	impossible step graphs
	•	duplicate step names
	•	invalid failure behavior definitions

Testing requirements

Add tests for:
	•	orchestration parsing
	•	chain execution
	•	fan-out/fan-in behavior
	•	failure handling
	•	retry handling
	•	progress tracking
	•	inspection output
	•	verifier behavior

Example requirements

Add an example orchestration such as:
	•	document processing pipeline
	•	import pipeline
	•	AI analysis pipeline

⸻

4. Search adapters beyond basic SQL search

Goal

Allow Foundry apps to grow from simple SQL-based listing search to stronger search backends without rewriting the world.

This should cover prompt shapes:
	•	add real search
	•	use Meilisearch
	•	use Postgres full text
	•	keep a SQL fallback
	•	sync indexed content

Scope

Implement a search abstraction with explicit adapters.

Required adapters

Implement at least:
	•	SQL/basic fallback
	•	Postgres full text if reasonably straightforward
	•	Meilisearch

You may choose SQL + Meilisearch first if that is more practical, but keep adapter contracts explicit.

Required concepts

Suggested:
	•	SearchAdapter
	•	SqlSearchAdapter
	•	MeilisearchAdapter
	•	SearchIndexDefinition
	•	SearchSyncJob

Search index definition format

Implement a config like:

version: 1
index: posts

source:
  table: posts
  primary_key: id

fields:
  - title
  - slug
  - body_markdown

filters:
  - status
  - created_at

New CLI

Implement:

php vendor/bin/foundry generate search-index posts --spec=specs/posts.search.yaml
php vendor/bin/foundry inspect search posts --json
php vendor/bin/foundry verify search

Required behavior
	•	explicit adapter selection
	•	index config validation
	•	sync/update job support
	•	query abstraction usable by generated list/API features
	•	fallback behavior documented clearly

Verification requirements

Add checks for:
	•	invalid adapter config
	•	invalid indexed fields
	•	invalid filters
	•	missing backend configuration

Testing requirements

Add strong tests for:
	•	search config parsing
	•	SQL adapter behavior
	•	Meilisearch adapter integration boundaries
	•	sync job behavior
	•	invalid config handling
	•	verifier behavior

Example requirements

Add an example app or example feature showing:
	•	a searchable posts index
	•	adapter selection
	•	indexed queries

⸻

5. Realtime updates

Goal

Allow developers and LLMs to add simple realtime behavior for progress updates, admin dashboards, notifications, or long-running workflows.

This should cover prompt shapes:
	•	show live progress
	•	add live notifications
	•	stream job status
	•	update the dashboard in real time

Scope

Implement the simplest robust thing first:
	•	SSE (Server-Sent Events)

Design for later WebSocket support, but do not build a huge socket system in this phase unless it stays extremely clean.

Required capabilities
	•	authenticated streams
	•	event stream definitions
	•	workflow/job progress stream
	•	notification stream pattern
	•	simple client-side consumption example

Suggested concepts
	•	StreamDefinition
	•	StreamRegistry
	•	SseEmitter
	•	StreamAuthResolver

New CLI

Implement:

php vendor/bin/foundry generate stream job-progress
php vendor/bin/foundry inspect streams --json
php vendor/bin/foundry verify streams

Required stream behavior
	•	explicit route or stream definition
	•	auth-aware stream access
	•	clear event payload format
	•	heartbeats / keepalive if needed
	•	graceful disconnect handling

Verification requirements

Add checks for:
	•	valid stream definitions
	•	auth strategy presence
	•	route conflicts
	•	payload schema issues if applicable

Testing requirements

Add tests for:
	•	stream definition parsing
	•	auth behavior
	•	SSE response behavior
	•	progress payload output
	•	verifier behavior

Example requirements

Add an example showing:
	•	live job progress
	•	admin moderation queue updates
	•	or live notification stream

⸻

6. Localization / i18n

Goal

Allow Foundry apps to support multiple languages and locale-aware output in an explicit, deterministic way.

This should cover prompt shapes:
	•	add localization
	•	translate validation messages
	•	support English and French
	•	localize dates and labels

Required capabilities
	•	translation file loading
	•	locale selection
	•	localized validation messages
	•	localized UI labels/messages
	•	date/number formatting helpers
	•	default locale config
	•	per-request locale resolution

Suggested structure

For example:

app/platform/lang/en/*.php
app/platform/lang/fr/*.php

Or another explicit structure. Keep it simple and documented.

New CLI

Implement:

php vendor/bin/foundry generate locale en
php vendor/bin/foundry generate locale fr
php vendor/bin/foundry inspect locales --json
php vendor/bin/foundry verify locales

Verification requirements

Add checks for:
	•	missing keys across locales
	•	invalid locale config
	•	malformed translation files

Testing requirements

Add tests for:
	•	locale loading
	•	fallback behavior
	•	validation message localization
	•	date/number formatting helpers
	•	verifier behavior

Example requirements

Add an example with at least two locales.

⸻

7. Roles and policies scaffolding

Goal

Make it easier for developers and LLMs to scaffold common authorization structures beyond raw permission strings.

This should cover prompt shapes:
	•	add roles
	•	create admin/editor/viewer roles
	•	seed permissions
	•	generate policy scaffolding
	•	document who can do what

Required capabilities
	•	role model / table support
	•	user-role assignment support
	•	permission group support
	•	seeded default roles
	•	policy scaffolding
	•	auth matrix generation hooks

New CLI

Implement:

php vendor/bin/foundry generate roles
php vendor/bin/foundry generate policy posts
php vendor/bin/foundry inspect roles --json
php vendor/bin/foundry verify policies

Required generated artifacts
	•	role-related migrations
	•	seed data or seed stubs
	•	policy config or feature-linked policy files
	•	docs hooks

Verification requirements

Add checks for:
	•	referenced roles exist
	•	referenced permissions exist
	•	policy references resolve cleanly
	•	role seeds are structurally valid

Testing requirements

Add strong tests for:
	•	role generation
	•	role-permission mapping
	•	policy generation
	•	auth behavior with roles
	•	verifier behavior

Example requirements

Add an example showing:
	•	admin/editor/viewer roles
	•	feature access restrictions by role

⸻

8. Visual inspect UI

Goal

Provide a browser-based developer UI that exposes the same inspectable reality Foundry’s CLI already provides.

This should cover prompt shapes:
	•	show me all routes
	•	show me feature boundaries
	•	show me generated indexes
	•	show me what changed
	•	show me queue/event traces
	•	help me understand what the LLM just edited

Scope

Build a lightweight developer-facing inspect UI, not a giant admin product.

Required capabilities

Expose at least:
	•	feature registry
	•	route registry
	•	schema registry
	•	auth/permission registry
	•	job/event/cache/scheduler registries
	•	context manifests
	•	verification results
	•	recent trace summaries where practical

Requirements
	•	server-rendered first
	•	protected behind explicit dev/admin auth
	•	deterministic, inspectable pages
	•	can be disabled in production via config if desired

New routes/features

Implement a Foundry dev-inspect area such as:

/dev/inspect/features
/dev/inspect/routes
/dev/inspect/schemas
/dev/inspect/auth
/dev/inspect/jobs
/dev/inspect/events
/dev/inspect/caches
/dev/inspect/context/{feature}

You may choose a different path structure, but keep it clear.

New CLI

Also add any inspect support needed, but do not replace the CLI.
The UI complements the CLI.

Verification requirements

Add checks for:
	•	inspect UI routes protected correctly
	•	referenced registries exist
	•	production-disabled behavior works if configurable

Testing requirements

Add tests for:
	•	inspect UI auth restrictions
	•	page rendering
	•	registry display correctness
	•	feature context display
	•	config-driven enable/disable behavior

Example requirements

Add an example or screenshots/docs showing the inspect UI in use.

⸻

Architecture integration requirements

All Phase 3 features must integrate cleanly into existing Foundry architecture.

Must remain feature-local

Any generated app behavior must still live under app/features/* where appropriate.

Must integrate with generated indexes

If billing, workflows, orchestration, search, streams, locales, roles, or inspect UI require registry/index support, implement it explicitly and deterministically.

Must remain inspectable

Everything must be explorable via CLI and/or inspect UI.

Must remain deterministic

Repeated generation from the same inputs must produce the same outputs.

Must avoid runtime magic

Prefer explicit config/specs, explicit registries, explicit schemas, explicit generated files, and explicit routes over hidden conventions.

⸻

New CLI surface to add

Implement at least:

php vendor/bin/foundry generate billing stripe
php vendor/bin/foundry inspect billing --json
php vendor/bin/foundry verify billing

php vendor/bin/foundry generate workflow <name> --spec=<file>
php vendor/bin/foundry inspect workflow <name> --json
php vendor/bin/foundry verify workflows

php vendor/bin/foundry generate orchestration <name> --spec=<file>
php vendor/bin/foundry inspect orchestration <name> --json
php vendor/bin/foundry verify orchestrations

php vendor/bin/foundry generate search-index <name> --spec=<file>
php vendor/bin/foundry inspect search <name> --json
php vendor/bin/foundry verify search

php vendor/bin/foundry generate stream <name>
php vendor/bin/foundry inspect streams --json
php vendor/bin/foundry verify streams

php vendor/bin/foundry generate locale <locale>
php vendor/bin/foundry inspect locales --json
php vendor/bin/foundry verify locales

php vendor/bin/foundry generate roles
php vendor/bin/foundry generate policy <name>
php vendor/bin/foundry inspect roles --json
php vendor/bin/foundry verify policies

Add --json support where practical for inspect and verify commands.

⸻

Documentation requirements

Update Foundry docs to explain:
	•	billing
	•	workflows
	•	orchestration
	•	search adapters
	•	realtime/SSE
	•	localization
	•	roles/policies
	•	inspect UI

Write docs as clear technical narrative, not fluff.

They should help:
	•	human developers
	•	LLMs reading the repo

⸻

Example app requirements

Update or add example apps demonstrating these new capabilities.

At minimum, include:

Example A: SaaS billing app
	•	plan config
	•	checkout flow
	•	webhook handling
	•	subscription status page

Example B: workflow app
	•	a publish/review workflow
	•	transition guards
	•	transition logs

Example C: orchestration app
	•	multi-step document or AI processing flow
	•	progress tracking

Example D: search app
	•	searchable resource with adapter config

Example E: localized app
	•	two locales
	•	localized validation/UI copy

Example F: inspect UI demo
	•	dev inspect pages enabled
	•	example registry browsing

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
	•	export/config tests
	•	UI tests where appropriate
	•	example app end-to-end tests
	•	regression tests for bugs discovered

Specific required coverage

Billing
	•	plan config
	•	checkout session generation
	•	webhook verification
	•	idempotency
	•	subscription sync
	•	invoice listing
	•	verifier behavior

Workflows
	•	parsing
	•	valid transitions
	•	invalid transitions
	•	permission guards
	•	event emission
	•	transition log persistence

Orchestration
	•	parsing
	•	execution order
	•	dependencies
	•	retries
	•	failure handling
	•	progress tracking

Search
	•	config parsing
	•	adapter selection
	•	SQL fallback behavior
	•	backend integration boundaries
	•	verifier behavior

Realtime
	•	stream definition parsing
	•	auth
	•	SSE responses
	•	progress payloads
	•	verifier behavior

i18n
	•	locale loading
	•	missing key detection
	•	fallback behavior
	•	localized validation messages

Roles/policies
	•	role generation
	•	permission mapping
	•	policy generation
	•	role-based access behavior

Inspect UI
	•	auth restrictions
	•	page rendering
	•	registry data correctness
	•	enable/disable behavior

Examples
	•	example flows verify cleanly and work end to end

Prefer meaningful tests over shallow surface coverage.

⸻

Implementation guidance

Keep generation flat and boring

Do not create abstraction castles with twenty-seven butlers.

Keep runtime explicit

Use explicit configs, registries, schemas, routes, and generated outputs.

Keep output readable

Generated code, configs, docs, and UI output should be understandable by humans and LLMs.

Reuse existing Foundry contracts where possible

Do not invent parallel truth systems unless absolutely necessary.

⸻

Suggested internal architecture additions

Codex may introduce abstractions like:
	•	BillingGenerator
	•	WorkflowGenerator
	•	WorkflowVerifier
	•	OrchestrationGenerator
	•	SearchIndexGenerator
	•	StreamGenerator
	•	LocaleVerifier
	•	RoleGenerator
	•	InspectUiController

Only do so when it improves clarity, determinism, and testability.

Avoid abstraction theater.

⸻

Deliverables

Produce:
	1.	full implementation of Foundry Roadmap Phase 3
	2.	updated CLI commands
	3.	new spec/config formats where needed
	4.	updated verifiers
	5.	billing support
	6.	workflow support
	7.	orchestration support
	8.	richer search support
	9.	realtime/SSE support
	10.	i18n support
	11.	roles/policies support
	12.	visual inspect UI
	13.	updated docs
	14.	updated example apps
	15.	very high automated test coverage

⸻

Final instruction

Build Phase 3 of Foundry so it becomes dramatically better at the next tier of real-world tasks developers ask LLMs to do:
	•	add SaaS billing
	•	model workflows
	•	orchestrate background pipelines
	•	use better search backends
	•	stream progress in real time
	•	localize apps
	•	scaffold roles/policies
	•	visually inspect system structure and traces

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
