BUILD ORDER:
30D, 30E, 30F, 30G, 36, 37, 38, 39, 40, 41, 42, 43, 44, 44B, 45A, 45B, 45C, 46, 47

Spec 30D — First-Run Experience (Zero → Value in 60 Seconds)

Preface

Foundry is powerful, but power is irrelevant if the first experience is confusing, slow, or overwhelming.

This spec defines a deterministic, fast, and confidence-building first-run experience that:
	•	works immediately after install
	•	demonstrates real value within 60 seconds
	•	aligns with Foundry’s identity as an LLM-first architecture system

⸻

Goals
	1.	Achieve time-to-value < 60 seconds
	2.	Make first-run deterministic and reproducible
	3.	Establish mental model immediately
	4.	Guide users toward:
	•	explain
	•	generate
	•	examples

⸻

Core Principle

First run should show, not tell

No long explanations. No documentation walls.

⸻

Entry Points

Trigger first-run experience when:
	•	foundry is run with no arguments
	•	OR project has no .foundry/ state
	•	OR user runs foundry init

⸻

Experience Flow

Step 1 — Welcome (fast, minimal)

Foundry Framework

Build and evolve applications using a structured architecture graph.

Let's get you to your first result.


⸻

Step 2 — Offer Quick Start Options

Choose an option:

1) Explore an example (recommended)
2) Inspect current project
3) Exit


⸻

Step 3A — Example Path (Primary)

If user selects 1:

Prompt:

Select an example:

1) Blog (reference)
2) Extensions & migrations (framework)

Then:
	•	copy example into working directory (or temp dir)
	•	run:

foundry explain


⸻

Step 4 — Immediate Output

Show:
	•	summary of application
	•	key components
	•	routes / features

⸻

Step 5 — Next Actions

Next steps:

- Modify the app:
    foundry generate "Add comments to blog posts"

- Inspect architecture:
    foundry explain --json

- Run diagnostics:
    foundry doctor


⸻

Alternate Path — Existing Project

If project detected:

Run automatically:

foundry explain

Then:

This project is ready.

Try:
  foundry generate "Add a feature"


⸻

Implementation

⸻

A. Create FirstRunService

src/UX/FirstRunService.php

Responsibilities:
	•	detect first-run conditions
	•	orchestrate flow
	•	call CLI commands internally

⸻

⸻

B. Hook into CLI entrypoint

In:

src/CLI/Application.php

If:
	•	no args
	•	OR init

Then:

FirstRunService::run();


⸻

⸻

C. Example loading mechanism

Add:

src/Examples/ExampleLoader.php

Capabilities:
	•	list available examples
	•	copy to working directory OR temp dir
	•	ensure deterministic structure

⸻

⸻

D. Non-interactive mode (important)

Support:

foundry init --example=blog

This is critical for:
	•	CI
	•	automation
	•	LLM usage

⸻

⸻

E. Output formatting

Use:
	•	same renderer as explain
	•	avoid custom formatting duplication

⸻

⸻

F. Safety
	•	never overwrite existing files without confirmation
	•	support temp-mode execution

⸻

⸻

CLI Additions

Optional but recommended:

foundry init
foundry examples:list
foundry examples:load <name>


⸻

⸻

Documentation Integration

Update README:

Add:

Getting Started

foundry

Follow the prompts.

⸻

⸻

Acceptance Criteria
	•	Running foundry with no args triggers first-run flow
	•	User reaches meaningful output within 60 seconds
	•	Example loads correctly
	•	explain runs automatically
	•	No documentation required to get started
	•	Works in non-interactive mode

⸻

⸻

Non-Goals
	•	tutorials
	•	deep docs
	•	UI/GUI

⸻

⸻

Result

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