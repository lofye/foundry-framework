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
