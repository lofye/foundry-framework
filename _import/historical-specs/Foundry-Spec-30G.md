BUILD ORDER:
30G, 36, 37, 38, 39, 40, 41, 42, 43, 44, 44B, 45A, 45B, 45C, 46, 47


Spec 30G — Git Integration for Explain, Generate, Plans, and Replay

Preface

Foundry already has:
	•	explain
	•	generate
	•	interactive plan review
	•	plan persistence
	•	undo/replay
	•	policy enforcement

To fit real developer workflows, Foundry must integrate cleanly with Git.

This spec adds Git-aware workflows without making Git a hard dependency for core framework behavior.

⸻

Goals
	1.	Make generate safer in real repositories
	2.	Let Foundry understand repository state before changes
	3.	Connect plans to commits when available
	4.	Improve undo/replay using Git-aware safeguards
	5.	Preserve clean operation when Git is unavailable

⸻

Non-Goals
	•	Do not replace Git
	•	Do not require Git for local framework use
	•	Do not force automatic commits
	•	Do not build a full VCS abstraction layer

⸻

Core Principle

When Git is present, Foundry should become Git-aware.
When Git is absent, Foundry should still work.

⸻

Part 1 — Repository State Detection

Introduce a Git integration layer such as:

src/Git/GitRepositoryInspector.php

Responsibilities:
	•	detect whether repo is under Git
	•	get current branch
	•	get HEAD commit hash
	•	detect dirty working tree
	•	list changed files
	•	detect untracked files

⸻

Part 2 — Pre-Generate Safety Checks

Before applying generation changes, if Git is present:

Check:
	•	working tree clean or dirty
	•	staged vs unstaged changes
	•	untracked files that conflict with plan targets

Behavior:
	•	warn when working tree is dirty
	•	optionally fail unless --allow-dirty
	•	show relevant conflicting files

This is especially important for:
	•	interactive generate
	•	replay
	•	undo

⸻

Part 3 — Plan Metadata Enrichment

Persist Git metadata with plans (Spec 37), when available:
	•	repo root
	•	branch
	•	HEAD commit hash
	•	dirty state at time of execution
	•	changed files before generate
	•	changed files after generate

Add to persisted plan metadata.

⸻

Part 4 — Optional Commit Support

Add optional commit helpers.

CLI options

For generate:
	•	--git-commit
	•	--git-commit-message="..."

If enabled and Git is present:
	•	stage affected files
	•	create commit after successful verification

Do not auto-commit by default.

⸻

Part 5 — Optional Snapshot Tagging

Optionally persist a lightweight mapping:
	•	plan_id → commit hash

This supports:
	•	auditing
	•	replay safety
	•	blame/debuggability
	•	future UI integration

⸻

Part 6 — Explain Git Context

When explaining a target, optional Git context may be included:
	•	last commit touching relevant files
	•	whether related files are currently dirty
	•	whether target changed since last plan

This should be optional and not clutter default human output.

Prefer:
	•	--json
	•	--git
	•	or deep modes

⸻

Part 7 — Undo with Git Awareness

Undo must become Git-aware when possible.

Behavior:
	•	warn if repository state has drifted since original plan
	•	warn if affected files changed since execution
	•	optionally refuse undo unless --force

If Git metadata is available, use it to assess safety before undo.

Do not rely only on Git for undo, but use it as a strong safety signal.

⸻

Part 8 — Replay with Git Awareness

Replay must check:
	•	current branch / commit context
	•	drift from original plan environment
	•	whether affected files changed since original run

Modes:

Default replay
	•	validate and warn on drift

Strict replay

foundry plan:replay <id> --strict

	•	fail if commit or file state diverged materially

⸻

Part 9 — Diff Integration

Foundry already has explain-diff / plan diff concepts.

Add Git-aware helpers where useful:
	•	compare architectural diff vs file diff
	•	optionally show affected files that changed in Git terms
	•	keep architecture diff primary

Git diff must support, not replace, Foundry’s architecture-level understanding.

⸻

Part 10 — CLI Surface

Potential additions:

foundry git:status
foundry plan:show <id> --git
foundry explain <target> --git --json

These are optional if the same functionality fits better into existing commands.

Prefer minimal CLI sprawl.

⸻

Part 11 — Failure and Warning Rules

When Git is available, warn clearly for:
	•	dirty working tree
	•	conflicting untracked files
	•	replay drift
	•	undo drift
	•	commit failure after successful generation

Do not silently ignore Git issues when they affect safety.

⸻

Part 12 — No-Hidden-Actions Rule

Never:
	•	auto-commit without explicit user request
	•	auto-stage unrelated files
	•	auto-reset working tree
	•	auto-stash changes

All Git actions must be explicit and visible.

⸻

Part 13 — Testing

Add tests for:
	•	Git repo detection
	•	dirty tree warnings
	•	plan metadata capture
	•	optional commit flow
	•	replay drift checks
	•	undo drift checks
	•	clean fallback when Git is absent

Use isolated temp repos in tests.

⸻

Part 14 — Acceptance Criteria
	•	Foundry detects Git repos cleanly
	•	generate warns on risky repo state
	•	plans store Git metadata when available
	•	optional commit flow works
	•	undo/replay become Git-aware
	•	no Git dependency is required for core usage
	•	no hidden Git side effects occur

⸻

Done Means

Foundry fits naturally into real-world Git workflows while still preserving its own higher-level architectural model.

It understands not just:
	•	the system
	•	the plan
	•	the change

but also:

the repository state in which that change lives.

------------------------------------------------------------------------------------------

RESULT

**Changes**
Git awareness is now wired into the shipped surfaces only, per your scope choice. [GitRepositoryInspector.php](/Users/lo_fye/code/foundry-framework/src/Git/GitRepositoryInspector.php) handles repo detection, branch/HEAD lookup, dirty-tree parsing, relevant-file commit metadata, and explicit scoped commits. [GenerateEngine.php](/Users/lo_fye/code/foundry-framework/src/Generate/GenerateEngine.php), [GenerateCommand.php](/Users/lo_fye/code/foundry-framework/src/CLI/Commands/GenerateCommand.php), and [Intent.php](/Users/lo_fye/code/foundry-framework/src/Generate/Intent.php) now make `generate` Git-aware: dirty repos warn or fail unless `--allow-dirty`, conflicting untracked targets fail before writes, `--git-commit` can create an explicit post-verification commit, and generate runs are persisted into existing history via [BuildArtifactStore.php](/Users/lo_fye/code/foundry-framework/src/Tooling/BuildArtifactStore.php). `foundry explain <target> --git` is now supported in [ExplainCommand.php](/Users/lo_fye/code/foundry-framework/src/CLI/Commands/ExplainCommand.php), and the CLI/help contract was updated in [ApiSurfaceRegistry.php](/Users/lo_fye/code/foundry-framework/src/Support/ApiSurfaceRegistry.php).

I also added coverage in [GitRepositoryInspectorTest.php](/Users/lo_fye/code/foundry-framework/tests/Unit/GitRepositoryInspectorTest.php), [CLIGenerateCommandTest.php](/Users/lo_fye/code/foundry-framework/tests/Integration/CLIGenerateCommandTest.php), [CLILicensedCommandsTest.php](/Users/lo_fye/code/foundry-framework/tests/Integration/CLILicensedCommandsTest.php), [ApiSurfaceRegistryTest.php](/Users/lo_fye/code/foundry-framework/tests/Unit/ApiSurfaceRegistryTest.php), and [CLIApplicationTest.php](/Users/lo_fye/code/foundry-framework/tests/Integration/CLIApplicationTest.php), plus a small stability fix in [GraphCompilerTest.php](/Users/lo_fye/code/foundry-framework/tests/Unit/GraphCompilerTest.php) so it ignores volatile timestamp/build-id fields instead of flaking on runtime speed. Docs were aligned in [README.md](/Users/lo_fye/code/foundry-framework/README.md), [docs/explain.md](/Users/lo_fye/code/foundry-framework/docs/explain.md), [docs/generate.md](/Users/lo_fye/code/foundry-framework/docs/generate.md), [docs/architecture-tools.md](/Users/lo_fye/code/foundry-framework/docs/architecture-tools.md), [APP-README.md](/Users/lo_fye/code/foundry-framework/APP-README.md), and [APP-AGENTS.md](/Users/lo_fye/code/foundry-framework/APP-AGENTS.md).

**Verification**
- `php bin/foundry verify cli-surface --json` -> `131` valid, `0` invalid, `0` ambiguous, coverage `1`
- `php bin/foundry compile graph --json` -> success
- `php bin/foundry verify graph --json` -> `ok: true`
- `php vendor/bin/phpunit` -> `423` tests, `3453` assertions, all passing

No `plan:*`, replay, or undo commands were added; this stays limited to current shipped surfaces: `generate`, `explain`, and persisted generate/history flows.

19 files changed
+1380
-12
Undo
APP-AGENTS.md
APP-README.md
README.md
docs/architecture-tools.md
docs/explain.md
docs/generate.md
src/CLI/Commands/ExplainCommand.php
src/CLI/Commands/GenerateCommand.php
src/Generate/GenerateEngine.php
src/Generate/Intent.php
src/Git/GitRepositoryInspector.php
src/Support/ApiSurfaceRegistry.php
src/Tooling/BuildArtifactStore.php
tests/Integration/CLIApplicationTest.php
tests/Integration/CLIGenerateCommandTest.php
tests/Integration/CLILicensedCommandsTest.php
tests/Unit/ApiSurfaceRegistryTest.php
tests/Unit/GitRepositoryInspectorTest.php
tests/Unit/GraphCompilerTest.php

------------------------------------------------------------------------------------------



------------------------------------------------------------------------------------------



------------------------------------------------------------------------------------------