First-run experience becomes:
	•	fast
	•	guided
	•	confidence-building

Instead of:
	•	confusing
	•	documentation-heavy
	•	abstract

⸻

⸻

Why This Matters

Right now, Foundry’s biggest risk is not capability—it’s:

perceived complexity

This spec directly neutralizes that.




⸻

⸻

What Comes Next

After this, the next highest-leverage spec would be:

👉 Spec 30E — Explain → Generate Feedback Loop (Tight Iteration UX)

This is what turns Foundry into something addictive instead of just impressive.

------------------------------------------------------------------------------------------

RESULT

Implemented Spec 30D. The CLI now routes `foundry` with no arguments into a deterministic first-run flow via [FirstRunService.php](/Users/lo_fye/code/foundry-framework/src/UX/FirstRunService.php), with curated offline example loading handled by [ExampleLoader.php](/Users/lo_fye/code/foundry-framework/src/Examples/ExampleLoader.php). I added explicit `init`, `examples:list`, and `examples:load` commands in [InitCommand.php](/Users/lo_fye/code/foundry-framework/src/CLI/Commands/InitCommand.php) and [ExamplesCommand.php](/Users/lo_fye/code/foundry-framework/src/CLI/Commands/ExamplesCommand.php), wired them into [Application.php](/Users/lo_fye/code/foundry-framework/src/CLI/Application.php), and made [ExplainCommand.php](/Users/lo_fye/code/foundry-framework/src/CLI/Commands/ExplainCommand.php) choose a deterministic default subject when no target is provided so the walkthrough can immediately produce useful architecture output.

I also updated the CLI contract and onboarding copy in [ApiSurfaceRegistry.php](/Users/lo_fye/code/foundry-framework/src/Support/ApiSurfaceRegistry.php), [CommandCatalog.php](/Users/lo_fye/code/foundry-framework/src/Documentation/CommandCatalog.php), [README.md](/Users/lo_fye/code/foundry-framework/README.md), [APP-README.md](/Users/lo_fye/code/foundry-framework/APP-README.md), and [APP-AGENTS.md](/Users/lo_fye/code/foundry-framework/APP-AGENTS.md), plus added first-run coverage in [CLIFirstRunExperienceTest.php](/Users/lo_fye/code/foundry-framework/tests/Integration/CLIFirstRunExperienceTest.php) and updated the existing CLI metadata/assertion tests.

One implementation choice I made: the spec’s “Blog (reference)” path loads the runnable [blog-api example](https://example.invalid) equivalent from the repo, and “Extensions & migrations” is composed from the `hello-world` app plus the extensions/migrations assets, because the repo’s named examples in those categories were not both directly explainable app projects as-is.

Verification passed:
- `php bin/foundry verify cli-surface --json` -> `131` valid, `0` invalid, `0` ambiguous, `0` orphan handlers, coverage `1`
- `php bin/foundry compile graph --json` -> completed successfully
- `php bin/foundry verify graph --json` -> `ok: true`
- `php vendor/bin/phpunit` -> `400` tests passed, `3186` assertions

18 files changed
+1203
-34
Undo
APP-AGENTS.md
APP-README.md
README.md
src/CLI/Application.php
src/CLI/CliSurfaceVerifier.php
src/CLI/Commands/ExamplesCommand.php
src/CLI/Commands/ExplainCommand.php
src/CLI/Commands/InitCommand.php
src/Documentation/CommandCatalog.php
src/Examples/ExampleLoader.php
src/Support/ApiSurfaceRegistry.php
src/UX/FirstRunService.php
tests/Integration/CLIApplicationTest.php
tests/Integration/CLIFirstRunExperienceTest.php
tests/Integration/CLIInitAppCommandTest.php
tests/Integration/CLILicensedCommandsTest.php
tests/Unit/ApiSurfaceRegistryTest.php
tests/Unit/CLICommandMatchesTest.php


------------------------------------------------------------------------------------------



------------------------------------------------------------------------------------------



------------------------------------------------------------------------------------------
