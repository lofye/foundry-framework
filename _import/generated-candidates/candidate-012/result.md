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
