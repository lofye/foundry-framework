Spec 19H — Local foundry Executable for Installed Apps

Preface

Foundry applications currently require CLI usage through:

php vendor/bin/foundry ...

While correct, this adds friction for everyday use and makes Foundry feel less polished than other ecosystems.

Developers expect an installed application to expose a convenient project-local command such as:
	•	artisan
	•	phpunit
	•	pest
	•	bin/console

Foundry should provide the same convenience.

This spec introduces a project-local executable named:

foundry

so that developers can run:

foundry whatever-command

from the root of a Foundry application after installation.

This command must remain deterministic, cross-platform-aware, and compatible with Composer-installed applications.

All new code must maintain ≥ 90% automated test coverage.

⸻

Goals

Spec 19H must:
	1.	allow project-local CLI usage via foundry ...
	2.	preserve existing support for php vendor/bin/foundry ...
	3.	work cleanly in generated Foundry apps
	4.	keep framework and scaffold behavior aligned
	5.	avoid global-install assumptions
	6.	remain explicit and maintainable

⸻

Core UX Goal

After installing or scaffolding a Foundry app, a developer standing in the project root should be able to run:

foundry inspect graph --json
foundry verify graph --json
foundry explain feature:thresholds --json

without needing to type php vendor/bin/foundry.

This convenience must be project-local, not dependent on a global package install.

⸻

Design Principle

The command should be implemented as a small executable shim committed into the app root, not as a global binary requirement.

The shim should delegate to the Composer-installed Foundry binary.

This mirrors the role played by:
	•	artisan
	•	bin/console

The shim exists to improve ergonomics, not to replace Composer’s binary mechanism.

⸻

Requirements

1. Add a project-root executable named foundry

Generated Foundry apps must include a root-level executable file named:

foundry

This file must be executable on Unix-like systems.

Its job is to delegate to the installed Foundry binary under vendor/bin/foundry.

Example conceptual behavior:

#!/usr/bin/env php
<?php
require __DIR__ . '/vendor/bin/foundry';

Codex may implement a slightly safer bootstrap if needed, but the result must remain simple and explicit.

⸻

2. Preserve current Composer binary usage

The following must continue to work:

php vendor/bin/foundry ...
vendor/bin/foundry ...

The new root-level foundry executable is an additive convenience layer.

It must not replace or break the Composer binary contract.

⸻

3. Scaffold integration

When a new Foundry app is generated, the scaffold must include:
	•	the root-level foundry executable
	•	appropriate executable permissions where supported
	•	documentation referencing the new command

This means updates may be required in:
	•	app scaffolding logic
	•	stub templates
	•	scaffold tests
	•	scaffold README / AGENTS output

⸻

4. Documentation updates

Update framework and scaffold documentation so that developer-facing usage in generated apps prefers:

foundry ...

while framework-repo-internal usage may still prefer:

php bin/foundry ...

This distinction must remain clear:
	•	framework repo itself → php bin/foundry ...
	•	generated Foundry apps → foundry ...

Documentation must not blur those two contexts.

⸻

5. AGENTS / contributor alignment

Where scaffolded app documentation or contributor instructions reference:

php vendor/bin/foundry ...

they should be updated to prefer:

foundry ...

for generated apps.

The framework repository’s own AGENTS guidance should remain unchanged unless explicitly needed.

⸻

6. Cross-platform behavior

Codex must handle the fact that:
	•	Unix-like systems support executable root scripts naturally
	•	Windows may require a companion approach

At minimum, the Unix/macOS developer experience must be clean.

If Codex determines that a Windows companion script is needed, acceptable options include:
	•	foundry.bat
	•	foundry.cmd

If added, those should also be scaffolded consistently.

The primary required outcome is:
	•	strong project-local CLI ergonomics
	•	no dependence on global installation

⸻

7. Failure behavior

If the root-level foundry shim is invoked before dependencies are installed, it must fail clearly and helpfully.

For example, it may report that:
	•	vendor/bin/foundry does not exist
	•	Composer dependencies must be installed first

It must not fail with a confusing low-level PHP warning if avoidable.

⸻

8. Do not require PATH installation

This spec must not assume that the app root has been added to the user’s shell PATH globally.

The expected usage is:
	•	from within the project root
	•	via ./foundry ... on strict shells if needed
	•	via foundry ... where the shell resolves current-directory executables appropriately or through local workflow conventions

Codex may document shell-specific notes, but must not redesign the spec around global PATH hacks.

⸻

9. Keep the implementation minimal

Do not introduce:
	•	a launcher service
	•	environment detection complexity
	•	framework-specific process wrappers
	•	hidden bootstrap logic unrelated to delegation

This should remain a small, explicit local executable entrypoint.

⸻

Recommended File Changes

Codex will likely need to update some combination of:
	•	app scaffold generator
	•	stub files for generated apps
	•	scaffolded README
	•	scaffolded AGENTS.md
	•	scaffold integration tests
	•	documentation references to generated-app command usage

Possible new scaffolded files:

foundry
foundry.bat

if Windows support is included.

⸻

Testing Requirements

All new code must maintain ≥ 90% automated test coverage.

Add tests verifying at minimum:

Scaffold tests
	•	generated apps include the root-level foundry file
	•	generated apps include Windows companion launcher if implemented
	•	generated README / AGENTS reference the correct command usage

Integration tests
	•	root-level foundry delegates correctly to the installed binary
	•	helpful failure occurs when vendor/bin/foundry is missing
	•	command arguments pass through unchanged

Contract tests
	•	existing vendor/bin/foundry usage remains functional
	•	framework-repo internal command usage remains unchanged

⸻

Deliverables

Codex must implement:
	•	root-level foundry executable in generated apps
	•	optional Windows companion launcher if needed
	•	scaffold generator updates
	•	scaffold docs updates
	•	scaffold / integration tests
	•	preserved compatibility with existing Composer binary usage

⸻

Final Instruction

This feature is about CLI ergonomics, not architectural redesign.

The result should make Foundry feel natural to use in installed applications while preserving the current framework binary behavior.

Optimize for:
	•	simplicity
	•	clarity
	•	project-local convenience
	•	scaffold consistency
	•	strong test coverage

Do not over-engineer this.

------------------------------------------------------------------------------------------

RESULT

Implemented Spec 19H in [InitAppCommand.php](/Users/lo_fye/code/foundry-framework/src/CLI/Commands/InitAppCommand.php), [CliCommandPrefix.php](/Users/lo_fye/code/foundry-framework/src/Support/CliCommandPrefix.php), and [LocalFoundryExecutableTest.php](/Users/lo_fye/code/foundry-framework/tests/Integration/LocalFoundryExecutableTest.php). New scaffolded apps now include a root `foundry` launcher plus `foundry.bat`, mark `foundry` executable on Unix-like systems, fail clearly when `vendor/bin/foundry` is missing, and keep `php vendor/bin/foundry ...` fully working. Scaffolded README/AGENTS/docs and Composer scripts now prefer `foundry ...` for app usage, while framework-repo usage stays on `php bin/foundry ...`.

I also wired app-facing command-prefix surfaces to prefer `foundry` when the launcher exists and fall back to `php vendor/bin/foundry` for older apps without it. That covers doctor/explain/prompt/impact/generated graph docs/upgrade-check guidance, plus the framework docs pages I updated in [README.md](/Users/lo_fye/code/foundry-framework/README.md) and `docs/*`.

Verification passed: `php vendor/bin/phpunit` (`354` tests, `2723` assertions, `3` skipped), `php bin/foundry compile graph --json`, and `php bin/foundry verify graph --json`.

26 files changed
+610
-369
Undo
README.md
docs/api-notifications-docs.md
docs/app-scaffolding.md
docs/architecture-tools.md
docs/example-applications.md
docs/execution-pipeline.md
docs/extension-author-guide.md
docs/extensions-and-migrations.md
docs/public-api-policy.md
docs/semantic-compiler.md
docs/upgrade-safety.md
examples/app-scaffolding/README.md
src/CLI/Commands/DoctorCommand.php
src/CLI/Commands/InitAppCommand.php
src/CLI/Commands/PromptCommand.php
src/Compiler/Analysis/ImpactAnalyzer.php
src/Compiler/Prompt/GraphPromptBuilder.php
src/Documentation/GraphDocsGenerator.php
src/Explain/ExplainSupport.php
src/Pro/Generation/AIGenerationService.php
src/Support/CliCommandPrefix.php
src/Upgrade/UpgradeAnalyzer.php
tests/Integration/CLIInitAppCommandTest.php
tests/Integration/LocalFoundryExecutableTest.php
tests/Unit/GraphDocsGeneratorTest.php
tests/Unit/ImpactAnalyzerTest.php
