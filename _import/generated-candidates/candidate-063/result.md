First-run onboarding is now data-driven and more truthful. [ExampleLoader.php](/Users/lo_fye/code/foundry-framework/src/Examples/ExampleLoader.php) now defines explicit onboarding metadata for each choice, including taxonomy, load mode, source examples, copy plan, overwrite behavior, and the deterministic default explain target. I standardized the visible example id to `blog-api`, kept `blog` as a compatibility alias, and made the composed `extensions-migrations` path say exactly what it does instead of pretending it is a standalone framework example.

The first-run UX and CLI discovery now consume that metadata instead of hardcoded labels. [FirstRunService.php](/Users/lo_fye/code/foundry-framework/src/UX/FirstRunService.php), [ExamplesCommand.php](/Users/lo_fye/code/foundry-framework/src/CLI/Commands/ExamplesCommand.php), [Application.php](/Users/lo_fye/code/foundry-framework/src/CLI/Application.php), [ApiSurfaceRegistry.php](/Users/lo_fye/code/foundry-framework/src/Support/ApiSurfaceRegistry.php), and [CommandCatalog.php](/Users/lo_fye/code/foundry-framework/src/Documentation/CommandCatalog.php) now all describe the same flow: `blog-api` is the canonical direct-copy onboarding example, `extensions-migrations` is a composed reference setup, and `foundry explain` with no target is an intentional deterministic contract. I aligned the docs to match in [README.md](/Users/lo_fye/code/foundry-framework/README.md), [APP-README.md](/Users/lo_fye/code/foundry-framework/APP-README.md), [APP-AGENTS.md](/Users/lo_fye/code/foundry-framework/APP-AGENTS.md), [architecture-tools.md](/Users/lo_fye/code/foundry-framework/docs/architecture-tools.md), [example-applications.md](/Users/lo_fye/code/foundry-framework/docs/example-applications.md), and [examples/README.md](/Users/lo_fye/code/foundry-framework/examples/README.md).

Coverage was tightened in [CLIFirstRunExperienceTest.php](/Users/lo_fye/code/foundry-framework/tests/Integration/CLIFirstRunExperienceTest.php), [CLIApplicationTest.php](/Users/lo_fye/code/foundry-framework/tests/Integration/CLIApplicationTest.php), [ApiSurfaceRegistryTest.php](/Users/lo_fye/code/foundry-framework/tests/Unit/ApiSurfaceRegistryTest.php), and the new [ExampleLoaderTest.php](/Users/lo_fye/code/foundry-framework/tests/Unit/ExampleLoaderTest.php). Verification passed:
- `php vendor/bin/phpunit tests/Integration/CLIFirstRunExperienceTest.php tests/Integration/CLIApplicationTest.php tests/Unit/ApiSurfaceRegistryTest.php tests/Unit/ExampleLoaderTest.php tests/Integration/CLILicensedCommandsTest.php` -> `25` tests, `429` assertions
- `php bin/foundry verify cli-surface --json` -> `131` valid, `0` invalid, `0` ambiguous, coverage `1`
- `php bin/foundry compile graph --json` -> success
- `php bin/foundry verify graph --json` -> `ok: true`
- `php vendor/bin/phpunit` -> `403` tests, `3231` assertions

17 files changed
+328
-43
Undo
APP-AGENTS.md
APP-README.md
README.md
docs/architecture-tools.md
docs/example-applications.md
examples/README.md
src/CLI/Application.php
src/CLI/CliSurfaceVerifier.php
src/CLI/Commands/ExamplesCommand.php
src/Documentation/CommandCatalog.php
src/Examples/ExampleLoader.php
src/Support/ApiSurfaceRegistry.php
src/UX/FirstRunService.php
tests/Integration/CLIApplicationTest.php
tests/Integration/CLIFirstRunExperienceTest.php
tests/Unit/ApiSurfaceRegistryTest.php
tests/Unit/ExampleLoaderTest.php


------------------------------------------------------------------------------------------




------------------------------------------------------------------------------------------
