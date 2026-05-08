Spec 10 — Project Scaffolding Generator

Preface

Before 1.0, Foundry needs an excellent first-run experience. A developer should be able to create a working project quickly and immediately see the framework’s architecture in action.

Spec 10 introduces stable project scaffolding commands and starter templates.

All new code must maintain ≥ 90% automated test coverage.

Goals

Spec 10 must:
	•	provide a clean app generator
	•	reduce friction for first-time users
	•	produce a canonical Foundry project structure
	•	include minimal but instructive starter examples

Requirements

1. New project command

Provide a command such as:

foundry new my-app

or equivalent stable scaffolding flow.

2. Starter modes

Support at least:
	•	minimal app
	•	standard app
	•	API-first app

Optional later:
	•	example/demo app templates

3. Generated project structure

Scaffolded projects must include:
	•	feature structure
	•	config structure
	•	pipeline setup
	•	basic auth or placeholder integration where appropriate
	•	example tests
	•	example docs/inspectability path

4. First-run guidance

After generation, output clear next steps such as:
	•	install dependencies
	•	run app
	•	inspect graph
	•	run doctor
	•	run tests

5. Generated app should demonstrate Foundry

A starter app should make it easy to see:
	•	architecture graph
	•	pipeline
	•	CLI inspection
	•	docs/metadata flow where practical

Deliverables
	•	project generator command
	•	starter templates
	•	first-run guidance
	•	canonical generated app structure

Testing Requirements

Tests must cover:
	•	scaffold generation
	•	template selection
	•	generated file correctness
	•	first-run behavior
	•	generated tests/app boot behavior

Coverage must remain ≥ 90%.

==========================================================================================

RESULT

Implemented Spec 10 in [src/CLI/Commands/InitAppCommand.php](/Users/lo_fye/code/foundry-framework/src/CLI/Commands/InitAppCommand.php): `foundry new` is now the stable project scaffold alias, `init app` remains supported, and scaffolds now support `minimal`, `standard`, and `api-first` modes. The generated app now includes canonical platform/config/bootstrap files, feature-local starter examples, `phpunit.xml.dist`, a boot smoke test, an inspect-ui definition, pre-generated graph docs under `docs/generated`, pre-generated inspect pages under `docs/inspect-ui`, and clearer first-run guidance. I also updated CLI/help metadata and docs in [src/Support/ApiSurfaceRegistry.php](/Users/lo_fye/code/foundry-framework/src/Support/ApiSurfaceRegistry.php), [README.md](/Users/lo_fye/code/foundry-framework/README.md), and [docs/app-scaffolding.md](/Users/lo_fye/code/foundry-framework/docs/app-scaffolding.md).

While validating the generated app, I found a runtime issue: empty request input was failing object-schema validation, which broke zero-input starter routes. That’s fixed in [src/Schema/JsonSchemaValidator.php](/Users/lo_fye/code/foundry-framework/src/Schema/JsonSchemaValidator.php), with coverage added alongside the new scaffold coverage in [tests/Integration/CLIInitAppCommandTest.php](/Users/lo_fye/code/foundry-framework/tests/Integration/CLIInitAppCommandTest.php), [tests/Integration/CLIScaffoldingCommandErrorsTest.php](/Users/lo_fye/code/foundry-framework/tests/Integration/CLIScaffoldingCommandErrorsTest.php), [tests/Integration/CLIApplicationTest.php](/Users/lo_fye/code/foundry-framework/tests/Integration/CLIApplicationTest.php), [tests/Unit/CLICommandMatchesTest.php](/Users/lo_fye/code/foundry-framework/tests/Unit/CLICommandMatchesTest.php), [tests/Unit/ApiSurfaceRegistryTest.php](/Users/lo_fye/code/foundry-framework/tests/Unit/ApiSurfaceRegistryTest.php), and [tests/Unit/SchemaValidatorTest.php](/Users/lo_fye/code/foundry-framework/tests/Unit/SchemaValidatorTest.php).

Verification: `php vendor/bin/phpunit` passed: 236 tests, 3 skipped.

11 files changed
+1130
-193
Undo
README.md
docs/app-scaffolding.md
src/CLI/Commands/InitAppCommand.php
src/Schema/JsonSchemaValidator.php
src/Support/ApiSurfaceRegistry.php
tests/Integration/CLIApplicationTest.php
tests/Integration/CLIInitAppCommandTest.php
tests/Integration/CLIScaffoldingCommandErrorsTest.php
tests/Unit/ApiSurfaceRegistryTest.php
tests/Unit/CLICommandMatchesTest.php
tests/Unit/SchemaValidatorTest.php
