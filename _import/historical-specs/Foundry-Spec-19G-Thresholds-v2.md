Thresholds v2 — Master Specification

This document is the authoritative specification for Thresholds v2.

It is fully self-contained and supersedes all prior Thresholds specifications.
Do not assume the existence of any previous version of this spec.

All requirements, architecture, behavior, and constraints necessary to implement Thresholds v2 are defined here.

Preface

Thresholds v2 is a reference application built on the Foundry framework.

Its purpose is to demonstrate how a real-world application can be:

- graph-native
- fully explainable via `foundry explain`
- deterministic in behavior and output
- structured in a way that aligns code, CLI, graph, and documentation

Thresholds v2 must function as:

1. a real, usable application
2. a teaching tool for Foundry
3. a verification target for framework capabilities
4. a demonstration of best practices for feature design, workflows, and architecture

This application must not rely on hidden behavior, implicit wiring, or framework shortcuts.

All relationships must be explicit and visible in the graph.
All important behaviors must be explainable via CLI and documentation.

The system must remain deterministic, testable, and aligned with Foundry’s contract discipline.

All new code must maintain ≥ 90% automated test coverage.

Thresholds v2 Master Spec

Preface

Thresholds should be updated as a reference application for modern Foundry, reflecting the architecture, contracts, and tooling that have matured through the current framework state.

Thresholds must be updated to account for:
	•	the completed foundry explain subsystem
	•	stronger contract discipline
	•	richer graph and docs alignment
	•	stable deterministic CLI and JSON surfaces
	•	extension/contributor-aware architecture
	•	the evolving role of Foundry as a graph-native, explainable system

Thresholds v2 should not just “use Foundry.” It should demonstrate why Foundry is different.

It should become:
	•	a real app
	•	a learning app
	•	a verification app
	•	a showcase app

All new code must maintain ≥ 90% automated test coverage.

Core objective

Thresholds v2 must become the canonical example of:
	•	graph-native application structure
	•	deterministic explainability
	•	extension-friendly design
	•	rich documentation alignment
	•	architecture that an LLM can understand because the framework made it legible

Product concept

Thresholds is an app for recording meaningful life thresholds, milestones, and transitions.

Examples:
	•	sobriety milestones
	•	meditation milestones
	•	creative milestones
	•	health streaks
	•	spiritual experiences
	•	personal transformation moments

It should help users:
	•	record thresholds
	•	reflect on them
	•	organize them
	•	observe patterns
	•	track streaks
	•	surface insights

Updated goals

Thresholds v2 must:
	1.	demonstrate modern Foundry feature architecture
	2.	demonstrate graph visibility and explainability
	3.	demonstrate event/workflow-driven application structure
	4.	demonstrate deterministic docs/CLI discoverability
	5.	remain understandable as a teaching app
	6.	remain rich enough to prove real-world value

Architecture requirements

1. Feature-first structure

Organize by features such as:
	•	account
	•	thresholds
	•	entries
	•	journals
	•	streaks
	•	insights
	•	notifications
	•	settings
	•	admin

Each feature must have:
	•	explicit schemas
	•	manifests
	•	graph-visible relationships
	•	explainable routes/actions/events/workflows

2. Explain integration

Thresholds must be designed so foundry explain is genuinely useful on it.

Representative explain targets should include:
	•	threshold creation route/action
	•	streak workflow
	•	insight generation workflow
	•	journal entry action
	•	notification dispatch job
	•	permissions for threshold editing
	•	schema interactions for threshold records

The app should not require special-case explain logic. It should naturally explain well because it is structured well.

3. Graph integrity

Thresholds must expose:
	•	routes
	•	features
	•	workflows
	•	events
	•	jobs
	•	schemas
	•	permissions
	•	docs relationships

The graph should make the app understandable.

4. Deterministic architecture

No magical hidden wiring.
Prefer explicit:
	•	events
	•	permissions
	•	handlers
	•	workflows
	•	schema relations
	•	docs links

Product requirements

Threshold records

Core object: Threshold

Representative fields:
	•	id
	•	user_id
	•	title
	•	description
	•	category
	•	timestamp
	•	notes
	•	tags
	•	visibility
	•	source_type
	•	source_reference
	•	created_at
	•	updated_at

Categories

Provide default categories such as:
	•	health
	•	spiritual
	•	creative
	•	professional
	•	relational
	•	personal_growth

Allow user-created categories.

Entries

Thresholds may have entries such as:
	•	reflections
	•	notes
	•	journal entries
	•	photos
	•	audio references

Journals

Longer-form reflections tied to thresholds or standalone timelines.

Support:
	•	markdown
	•	tags
	•	search
	•	chronology

Streaks

Support repeat-pattern milestones such as:
	•	meditation
	•	workouts
	•	sobriety
	•	spiritual practice

Use workflows/jobs for streak updates and milestone detection.

Insights

Generate explainable insights such as:
	•	longest streak
	•	most active category
	•	thresholds reached this year
	•	milestones by month
	•	anniversary reminders

Notifications

Support deterministic notification flows for:
	•	milestone reached
	•	anniversary reminder
	•	weekly reflection prompt

Privacy

Support:
	•	private
	•	shared
	•	public

Timeline

Provide chronological threshold views.

Export

Support:
	•	JSON
	•	Markdown
	•	CSV

Foundry-specific demonstration requirements

1. Explain-first design

Thresholds should be one of the best demos for:
	•	foundry explain
	•	graph exploration
	•	contract-based docs

2. Docs alignment

Thresholds should include app docs that show:
	•	feature structure
	•	workflows
	•	event flows
	•	representative explain targets
	•	how the graph reflects the app

3. Learning value

A developer should be able to inspect Thresholds and learn:
	•	how features are structured
	•	how workflows are wired
	•	how permissions are enforced
	•	how schemas appear in the graph
	•	how explainability emerges from architecture

4. LLM-readiness without built-in AI

Thresholds should be easy for external LLM tools to understand because:
	•	the app is explicit
	•	the graph is rich
	•	explain outputs are useful
	•	docs and CLI align

Do not build hosted LLM features into Thresholds.
Do not require framework-level AI services.

Suggested representative explain targets

Thresholds v2 should ensure meaningful explain output for targets like:
	•	thresholds.create
	•	workflow:streak.update
	•	event:threshold.created
	•	event:streak.milestone_reached
	•	feature:thresholds
	•	feature:insights
	•	schema:threshold
	•	schema:journal_entry
	•	threshold notification job
	•	pipeline stage interactions affecting protected threshold routes

These should be testable.

Testing requirements

All new functionality must maintain ≥ 90% automated test coverage.

Tests must include:
	•	feature tests
	•	API tests
	•	workflow tests
	•	notification tests
	•	graph verification tests
	•	explain-target tests
	•	docs alignment tests where applicable
	•	export tests
	•	permission tests

Specific emphasis:
	•	representative foundry explain targets on Thresholds should produce stable, meaningful outputs

Deliverables

Thresholds v2 must produce:
	•	a modern Foundry-native application structure
	•	threshold/journal/streak/insight functionality
	•	event/workflow-driven architecture
	•	graph-visible relationships
	•	explain-friendly targets
	•	updated docs
	•	stable tests
	•	deterministic exports
	•	a real showcase application for Foundry

Non-goals

Thresholds v2 should not include:
	•	built-in LLM services
	•	speculative AI journaling
	•	autonomous insight generation using paid inference
	•	social network complexity
	•	marketplace/community features
	•	heavy collaborative features unless explicitly added later

Final instruction

Thresholds v2 should prove that Foundry is not only architecturally elegant in isolation.

It should prove that:
	•	a real app can be built this way
	•	the app remains understandable
	•	the graph remains meaningful
	•	foundry explain remains useful
	•	the docs remain truthful
	•	the system remains deterministic

Thresholds should become the reference application that makes Foundry “click” for developers.

-------------------------------------

RESULT

Implemented the first Thresholds v2 slice on top of the existing app.

The core work is in [ThresholdsV2Repository.php](/Users/lo_fye/code/thresholds/app/Support/ThresholdsV2Repository.php), [ThresholdsV2FeatureHandlers.php](/Users/lo_fye/code/thresholds/app/Support/ThresholdsV2FeatureHandlers.php), and [ThresholdsV2JobRunners.php](/Users/lo_fye/code/thresholds/app/Support/ThresholdsV2JobRunners.php). I added explicit v2 tables to [Database.php](/Users/lo_fye/code/thresholds/app/Support/Database.php) and the three migration files, plus new Foundry feature surfaces for:
- `GET /my/thresholds`, `POST /my/thresholds`
- `GET /my/journals`, `POST /my/journals`
- `GET /my/insights`
- `POST /my/thresholds/export`
- job features `workflow_streak_update`, `insights_generate`, and `notifications_dispatch`

The new feature manifests live under [app/features/thresholds_create](/Users/lo_fye/code/thresholds/app/features/thresholds_create), [app/features/thresholds_view_timeline](/Users/lo_fye/code/thresholds/app/features/thresholds_view_timeline), [app/features/journals_create_entry](/Users/lo_fye/code/thresholds/app/features/journals_create_entry), [app/features/journals_view](/Users/lo_fye/code/thresholds/app/features/journals_view), [app/features/insights_view](/Users/lo_fye/code/thresholds/app/features/insights_view), [app/features/thresholds_export](/Users/lo_fye/code/thresholds/app/features/thresholds_export), [app/features/workflow_streak_update](/Users/lo_fye/code/thresholds/app/features/workflow_streak_update), [app/features/insights_generate](/Users/lo_fye/code/thresholds/app/features/insights_generate), and [app/features/notifications_dispatch](/Users/lo_fye/code/thresholds/app/features/notifications_dispatch). I also added v2 docs in [THRESHOLDS_V2.md](/Users/lo_fye/code/thresholds/docs/THRESHOLDS_V2.md) and updated [README.md](/Users/lo_fye/code/thresholds/README.md).

Verification:
- `php vendor/bin/foundry generate indexes --json`
- `php vendor/bin/foundry verify contracts --json`
- `php vendor/bin/phpunit`
- `XDEBUG_MODE=coverage php vendor/bin/phpunit --coverage-text`

Results:
- contracts verification passed
- full PHPUnit suite passed: `120` tests, `2225` assertions
- `foundry explain` is still Pro-gated in this local environment, so verification is via manifests, generated indexes, docs alignment, and runtime tests
- overall project coverage is still low at `44.50%`
- the new v2 PHP layer is mostly covered, but [ThresholdsV2FeatureHandlers.php](/Users/lo_fye/code/thresholds/app/Support/ThresholdsV2FeatureHandlers.php) is at `89.63%` line coverage, so the spec’s `>= 90%` bar is not cleanly satisfied yet

There are also generated artifact changes in `app/generated/*`, a new generated [query_index.php](/Users/lo_fye/code/thresholds/app/generated/query_index.php), and an empty untracked `app/.foundry/` directory from Foundry tooling.

Natural next steps:
1. Move the dashboard and home flows onto the v2 threshold/journal/insight surfaces instead of the legacy moments model.
2. Add threshold editing/detail routes so the `thresholds.edit` permission target is exercised by runtime behavior, not just manifests/docs.
3. Close the remaining v2 coverage gap and, if Pro is available later, add real `foundry explain` assertions for the representative targets.

107 files changed
+4355
-10
Undo
README.md
app/Support/Database.php
app/Support/Domain.php
app/Support/Html.php
app/Support/ThresholdsV2FeatureHandlers.php
app/Support/ThresholdsV2JobRunners.php
app/Support/ThresholdsV2Repository.php
app/features/insights_generate/action.php
app/features/insights_generate/cache.yaml
app/features/insights_generate/context.manifest.json
app/features/insights_generate/events.yaml
app/features/insights_generate/feature.yaml
app/features/insights_generate/input.schema.json
app/features/insights_generate/jobs.yaml
app/features/insights_generate/output.schema.json
app/features/insights_generate/permissions.yaml
app/features/insights_generate/prompts.md
app/features/insights_view/action.php
app/features/insights_view/cache.yaml
app/features/insights_view/context.manifest.json
app/features/insights_view/events.yaml
app/features/insights_view/feature.yaml
app/features/insights_view/input.schema.json
app/features/insights_view/jobs.yaml
app/features/insights_view/output.schema.json
app/features/insights_view/permissions.yaml
app/features/insights_view/prompts.md
app/features/journals_create_entry/action.php
app/features/journals_create_entry/cache.yaml
app/features/journals_create_entry/context.manifest.json
app/features/journals_create_entry/events.yaml
app/features/journals_create_entry/feature.yaml
app/features/journals_create_entry/input.schema.json
app/features/journals_create_entry/jobs.yaml
app/features/journals_create_entry/output.schema.json
app/features/journals_create_entry/permissions.yaml
app/features/journals_create_entry/prompts.md
app/features/journals_view/action.php
app/features/journals_view/cache.yaml
app/features/journals_view/context.manifest.json
app/features/journals_view/events.yaml
app/features/journals_view/feature.yaml
app/features/journals_view/input.schema.json
app/features/journals_view/jobs.yaml
app/features/journals_view/output.schema.json
app/features/journals_view/permissions.yaml
app/features/journals_view/prompts.md
app/features/notifications_dispatch/action.php
app/features/notifications_dispatch/cache.yaml
app/features/notifications_dispatch/context.manifest.json
app/features/notifications_dispatch/events.yaml
app/features/notifications_dispatch/feature.yaml
app/features/notifications_dispatch/input.schema.json
app/features/notifications_dispatch/jobs.yaml
app/features/notifications_dispatch/output.schema.json
app/features/notifications_dispatch/permissions.yaml
app/features/notifications_dispatch/prompts.md
app/features/thresholds_create/action.php
app/features/thresholds_create/cache.yaml
app/features/thresholds_create/context.manifest.json
app/features/thresholds_create/events.yaml
app/features/thresholds_create/feature.yaml
app/features/thresholds_create/input.schema.json
app/features/thresholds_create/jobs.yaml
app/features/thresholds_create/output.schema.json
app/features/thresholds_create/permissions.yaml
app/features/thresholds_create/prompts.md
app/features/thresholds_export/action.php
app/features/thresholds_export/cache.yaml
app/features/thresholds_export/context.manifest.json
app/features/thresholds_export/events.yaml
app/features/thresholds_export/feature.yaml
app/features/thresholds_export/input.schema.json
app/features/thresholds_export/jobs.yaml
app/features/thresholds_export/output.schema.json
app/features/thresholds_export/permissions.yaml
app/features/thresholds_export/prompts.md
app/features/thresholds_view_timeline/action.php
app/features/thresholds_view_timeline/cache.yaml
app/features/thresholds_view_timeline/context.manifest.json
app/features/thresholds_view_timeline/events.yaml
app/features/thresholds_view_timeline/feature.yaml
app/features/thresholds_view_timeline/input.schema.json
app/features/thresholds_view_timeline/jobs.yaml
app/features/thresholds_view_timeline/output.schema.json
app/features/thresholds_view_timeline/permissions.yaml
app/features/thresholds_view_timeline/prompts.md
app/features/workflow_streak_update/action.php
app/features/workflow_streak_update/cache.yaml
app/features/workflow_streak_update/context.manifest.json
app/features/workflow_streak_update/events.yaml
app/features/workflow_streak_update/feature.yaml
app/features/workflow_streak_update/input.schema.json
app/features/workflow_streak_update/jobs.yaml
app/features/workflow_streak_update/output.schema.json
app/features/workflow_streak_update/permissions.yaml
app/features/workflow_streak_update/prompts.md
app/platform/migrations/202603060001_thresholds_sqlite_schema.sql
app/platform/migrations/202603060002_thresholds_postgres_schema.sql
app/platform/migrations/202603060003_thresholds_mysql_schema.sql
app/platform/public/sitemap.xml
docs/THRESHOLDS_V2.md
tests/thresholds_v2_docs_alignment_test.php
tests/thresholds_v2_feature_files_test.php
tests/thresholds_v2_graph_verification_test.php
tests/thresholds_v2_jobs_test.php
tests/thresholds_v2_runtime_test.php

------------------------------------------------------------------------------------------

