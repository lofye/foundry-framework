Changes applied.

Summary:
- Added: Comment model
- Added: comment routes
- Modified: Post model

Inspect changes:
  foundry explain --diff


⸻

⸻

E. New CLI Command

foundry explain --diff

Shows:

Changes since last generation:

Added:
- Comment entity
- CommentController

Modified:
- Post (relations)

Removed:
- (none)


⸻

⸻

F. Optional Auto-Explain

Add flag:

foundry generate "..." --explain

Behavior:
	•	runs explain immediately after generate
	•	shows updated system

⸻

⸻

G. Iteration Hinting

After generate:

Next:

- Refine:
    foundry generate "Add validation to comments"

- Inspect:
    foundry explain --diff

- Full view:
    foundry explain


⸻

⸻

H. Failure Recovery

If generate fails:
	•	DO NOT overwrite previous snapshot
	•	Show:

Generation failed.

No changes were applied.


⸻

⸻

Implementation

⸻

A. Snapshot Service

src/Explain/Snapshot/ExplainSnapshotService.php

Methods:

capture(string $label): void
load(string $label): array


⸻

⸻

B. Diff Service

src/Explain/Diff/ExplainDiffService.php

Input:
	•	pre snapshot
	•	post snapshot

Output:
	•	structured diff

⸻

⸻

C. Generate Integration

In:

src/Generate/GenerateEngine.php

Flow:

snapshot(pre)
run generate
snapshot(post)
diff = computeDiff()
store diff


⸻

⸻

D. Storage

.foundry/
  snapshots/
    pre-generate.json
    post-generate.json
  diffs/
    last.json


⸻

⸻

E. CLI Integration

Update:

ExplainCommand
GenerateCommand

Add support for:
	•	--diff
	•	--explain

⸻

⸻

Output Constraints
	•	Must be deterministic
	•	Must not depend on LLM interpretation
	•	Must use canonical graph / explain data

⸻

⸻

Documentation

Update:
	•	README
	•	docs/generate.md
	•	docs/explain.md

Add section:

Iteration Loop

foundry generate "Add feature"
foundry explain --diff


⸻

⸻

Acceptance Criteria
	•	generate always creates snapshots
	•	explain --diff works deterministically
	•	output reflects real architectural changes
	•	no file-level diff leakage
	•	CLI messaging is consistent
	•	works across all example apps

⸻

⸻

Non-Goals
	•	visual UI
	•	git integration (future spec)
	•	interactive approval (Spec 36)

⸻

⸻

Result

Foundry becomes:
	•	iterative instead of one-shot
	•	transparent instead of opaque
	•	confidence-building instead of risky

⸻

⸻

Why This Is Critical

Without this:
	•	generate feels like magic
	•	users don’t trust changes
	•	iteration slows down

With this:

Foundry becomes a thinking partner, not just a tool

⸻

⸻

Relationship to Other Specs
	•	Builds on:
	•	Spec 33 (Explain system)
	•	Spec 35 (Generate system)
	•	Prepares for:
	•	Spec 36 (Interactive Generate)
	•	Spec 37+ (workflows)

⸻

⸻

Final Insight

This is the moment Foundry shifts from:

“LLM-assisted framework”

to:

“Structured development loop”

⸻

If you want next, we can:

👉 tighten this even further with a confidence scoring system (30F)
or
👉 connect it to Git for real-world workflows (later spec)

But this alone is a massive step forward.

------------------------------------------------------------------------------------------

RESULT

Implemented Spec 30E end to end. The generate pipeline in [src/Generate/GenerateEngine.php](/Users/lo_fye/code/foundry-framework/src/Generate/GenerateEngine.php) now captures explain-derived `pre-generate` and `post-generate` snapshots, computes and stores the latest architectural diff, and restores post-snapshot/diff state on failure so we never leave a misleading loop behind. The new services live in [src/Explain/Snapshot/ExplainSnapshotService.php](/Users/lo_fye/code/foundry-framework/src/Explain/Snapshot/ExplainSnapshotService.php) and [src/Explain/Diff/ExplainDiffService.php](/Users/lo_fye/code/foundry-framework/src/Explain/Diff/ExplainDiffService.php), with the deterministic default explain target extracted into [src/Explain/ExplainSupport.php](/Users/lo_fye/code/foundry-framework/src/Explain/ExplainSupport.php).

The CLI now supports `foundry explain --diff` in [src/CLI/Commands/ExplainCommand.php](/Users/lo_fye/code/foundry-framework/src/CLI/Commands/ExplainCommand.php) and `foundry generate ... --explain` in [src/CLI/Commands/GenerateCommand.php](/Users/lo_fye/code/foundry-framework/src/CLI/Commands/GenerateCommand.php), including concise post-generate architectural summaries and deterministic next-step hints. I also aligned the surfaced help/docs in [src/Support/ApiSurfaceRegistry.php](/Users/lo_fye/code/foundry-framework/src/Support/ApiSurfaceRegistry.php), [src/Documentation/CommandCatalog.php](/Users/lo_fye/code/foundry-framework/src/Documentation/CommandCatalog.php), [README.md](/Users/lo_fye/code/foundry-framework/README.md), [docs/architecture-tools.md](/Users/lo_fye/code/foundry-framework/docs/architecture-tools.md), [docs/explain.md](/Users/lo_fye/code/foundry-framework/docs/explain.md), [docs/generate.md](/Users/lo_fye/code/foundry-framework/docs/generate.md), [APP-README.md](/Users/lo_fye/code/foundry-framework/APP-README.md), and [APP-AGENTS.md](/Users/lo_fye/code/foundry-framework/APP-AGENTS.md). Coverage was added in [tests/Integration/CLIGenerateCommandTest.php](/Users/lo_fye/code/foundry-framework/tests/Integration/CLIGenerateCommandTest.php), [tests/Unit/ExplainSnapshotServiceTest.php](/Users/lo_fye/code/foundry-framework/tests/Unit/ExplainSnapshotServiceTest.php), and [tests/Unit/ExplainDiffServiceTest.php](/Users/lo_fye/code/foundry-framework/tests/Unit/ExplainDiffServiceTest.php).

Verification passed:
- `php vendor/bin/phpunit tests/Integration/CLIGenerateCommandTest.php tests/Integration/CLILicensedCommandsTest.php tests/Integration/CLIApplicationTest.php tests/Unit/ApiSurfaceRegistryTest.php tests/Unit/ExplainSnapshotServiceTest.php tests/Unit/ExplainDiffServiceTest.php` -> `28` tests, `423` assertions
- `php bin/foundry verify cli-surface --json` -> `131` valid, `0` invalid, `0` ambiguous, coverage `1`
- `php bin/foundry compile graph --json` -> success
- `php bin/foundry verify graph --json` -> `ok: true`
- `php vendor/bin/phpunit` -> `410` tests, `3273` assertions

20 files changed
+1777
-119
Undo
APP-AGENTS.md
APP-README.md
README.md
docs/architecture-tools.md
docs/explain.md
docs/generate.md
src/CLI/Commands/ExplainCommand.php
src/CLI/Commands/GenerateCommand.php
src/Documentation/CommandCatalog.php
src/Explain/Diff/ExplainDiffService.php
src/Explain/ExplainSupport.php
src/Explain/Snapshot/ExplainSnapshotService.php
src/Generate/GenerateEngine.php
src/Generate/Intent.php
src/Support/ApiSurfaceRegistry.php
tests/Integration/CLIApplicationTest.php
tests/Integration/CLIGenerateCommandTest.php
tests/Unit/ApiSurfaceRegistryTest.php
tests/Unit/ExplainDiffServiceTest.php
tests/Unit/ExplainSnapshotServiceTest.php


------------------------------------------------------------------------------------------



------------------------------------------------------------------------------------------



------------------------------------------------------------------------------------------
