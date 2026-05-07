BUILD ORDER:
30E, 30F, 30G, 36, 37, 38, 39, 40, 41, 42, 43, 44, 44B, 45A, 45B, 45C, 46, 47

Spec 30E — Explain ⇄ Generate Feedback Loop (Tight Iteration UX)

Preface

Foundry’s core promise is not just:
	•	understanding code (explain)
	•	modifying code (generate)

It is:

a continuous, structured feedback loop between understanding and change

This spec formalizes that loop so that:
	•	every generation is grounded in the current architecture
	•	every change is immediately reflected in explain output
	•	developers can iterate rapidly with confidence

This spec is not a UI flourish.
It is a core product experience layer that makes Foundry feel coherent, trustworthy, and fast.

⸻

Goals
	1.	Create a tight, deterministic loop:

explain → generate → explain → refine

	2.	Ensure generate always operates on real current state
	3.	Provide clear before/after architectural visibility
	4.	Make iteration feel:
	•	fast
	•	safe
	•	predictable
	5.	Preserve deterministic, machine-readable output for both humans and LLMs

⸻

Non-Goals
	•	Do not implement full interactive review UI here (Spec 36)
	•	Do not implement Git integration here (Spec 30G)
	•	Do not add file-level diff visualization as the primary output
	•	Do not bypass or duplicate the Explain system
	•	Do not allow stale snapshots to be treated as truth

⸻

Core Principle

Generate is never blind.
Explain is never stale.

The user should never have to wonder:
	•	what changed
	•	whether the change actually affected the architecture
	•	what to inspect next

⸻

Part 1 — Canonical Iteration Loop

Canonical Flow
	1.	foundry explain
	2.	foundry generate "Add X"
	3.	system applies changes
	4.	system captures post-change architecture
	5.	user sees a concise architectural summary of what changed
	6.	user can immediately run:
	•	foundry explain --diff
	•	foundry explain
	•	another foundry generate ...

This loop must feel like one coherent workflow, not unrelated commands.

⸻

Part 2 — Snapshot System

Purpose

Generate must be anchored to architecture snapshots before and after mutation.

These are not file backups.
They are Explain-derived architecture state snapshots.

⸻

2.1 Pre-Generate Snapshot

Before any generate run begins, capture:

ExplainSnapshot::capture('pre-generate')

Required contents

At minimum:
	•	application summary
	•	core components
	•	routes
	•	graph subset relevant to current system state
	•	explain metadata / schema version
	•	pack/extension contributions when applicable

Storage location

.foundry/snapshots/pre-generate.json


⸻

2.2 Post-Generate Snapshot

After generation completes successfully, capture:

ExplainSnapshot::capture('post-generate')

Storage location

.foundry/snapshots/post-generate.json


⸻

2.3 Snapshot Contract

Introduce a dedicated service such as:

src/Explain/Snapshot/ExplainSnapshotService.php

Responsibilities:
	•	capture explain-derived snapshot
	•	persist deterministic JSON
	•	load snapshots by label
	•	validate schema/version compatibility

Rules
	•	snapshots must be deterministic
	•	snapshots must be derived from ExplainModel / canonical explain output
	•	snapshots must not depend on presentation formatting
	•	snapshots must not include environment-specific noise

⸻

Part 3 — Explain Diff

Purpose

After generation, Foundry must be able to show:

what changed in the architecture

This is not primarily a file diff.
It is an architecture diff.

⸻

3.1 Diff Service

Create:

src/Explain/Diff/ExplainDiffService.php

Responsibilities:
	•	compare pre and post snapshots
	•	detect added / removed / modified architecture elements
	•	emit deterministic structured diff

⸻

3.2 Diff Categories

At minimum support:
	•	added
	•	removed
	•	modified

Across relevant architectural categories such as:
	•	routes
	•	schemas
	•	commands
	•	workflows
	•	guards
	•	events
	•	generators
	•	packs/extensions
	•	graph nodes/edges

⸻

3.3 Diff Output Contract

Example shape:

{
  "schema_version": 1,
  "summary": {
    "added": 3,
    "removed": 0,
    "modified": 2
  },
  "added": [
    {
      "type": "schema",
      "id": "comment",
      "label": "Comment schema"
    }
  ],
  "removed": [],
  "modified": [
    {
      "type": "route",
      "id": "GET /posts/{slug}",
      "before": "...",
      "after": "..."
    }
  ]
}

Requirements
	•	deterministic ordering
	•	stable keys
	•	explicit type labeling
	•	no prose-only output

⸻

Part 4 — Generate Integration

Purpose

The Generate engine must automatically participate in the snapshot/diff loop.

⸻

4.1 Generate Pipeline Integration

Inside the Generate pipeline:
	1.	capture pre snapshot
	2.	run generate planning + execution
	3.	if generate succeeds:
	•	capture post snapshot
	•	compute explain diff
	•	persist latest diff
	4.	if generate fails:
	•	do not overwrite post snapshot
	•	do not emit misleading diff

⸻

4.2 Storage Layout

.foundry/
  snapshots/
    pre-generate.json
    post-generate.json
  diffs/
    last.json

Optional future:
	•	timestamped histories
	•	plan-linked diffs

Not required now.

⸻

4.3 Failure Behavior

If generation fails:
	•	preserve pre snapshot
	•	do not replace post snapshot with partial state
	•	do not write misleading “last diff”
	•	return structured error

Example human output:

Generation failed.

No architectural diff was recorded because no completed post-generate state was produced.


⸻

Part 5 — CLI Surface

5.1 foundry explain --diff

Add support for:

foundry explain --diff

Behavior
	•	load .foundry/diffs/last.json
	•	render architectural changes since last successful generate

Human output example

Changes since last generation:

Added:
- Comment schema
- POST /comments route

Modified:
- Post workflow
- Blog pack graph contribution

Removed:
- none

JSON output

foundry explain --diff --json

Must return the structured diff contract directly.

⸻

5.2 foundry generate --explain

Add optional flag:

foundry generate "Add comments" --explain

Behavior

After successful generation:
	•	compute architectural diff
	•	then render updated explain output or a concise explain summary

This should be useful for users who want immediate post-change inspection without typing a second command.

⸻

5.3 Suggested Next Actions

After every successful generate, print a deterministic next-step hint such as:

Next:

- Inspect architectural changes:
    foundry explain --diff

- View full current system:
    foundry explain

- Continue iterating:
    foundry generate "Refine X"

This must be concise and consistent.

⸻

Part 6 — Explain Contract Alignment

Purpose

The snapshot/diff system must not invent its own structural model.

It must reuse the Explain system.

Strict Rules
	•	snapshots must be derived from ExplainModel or its equivalent canonical output
	•	diff must compare explain-derived state, not raw files
	•	explain --diff must not reconstruct architecture from scratch independently of Explain

No parallel truth systems allowed.

⸻

Part 7 — Data Freshness Rules

Purpose

Prevent stale state from being treated as current truth.

Rules
	•	a diff is only valid if both pre and post snapshots exist and share compatible schema versions
	•	snapshot metadata must include:
	•	explain schema version
	•	framework version if available
	•	timestamp
	•	source hash / graph hash if available

If incompatible

foundry explain --diff must fail clearly:

Unable to compute architectural diff: snapshot versions are incompatible.

Do not produce best-effort comparisons across incompatible snapshot schemas.

⸻

Part 8 — Pack / Extension Awareness

This loop must work for both core and pack-contributed changes.

Requirements

If generation:
	•	installs a pack
	•	modifies pack-contributed graph state
	•	adds or removes extension-contributed nodes

Then snapshots and diffs must reflect that clearly.

Example diff item:

{
  "type": "pack",
  "id": "foundry/blog",
  "change": "added"
}

Or:

{
  "type": "route",
  "id": "GET /posts",
  "origin": "extension",
  "extension": "foundry/blog"
}

This is critical for later marketplace and pack trust flows.

⸻

Part 9 — LLM / Machine Consumption

Purpose

This loop must be easy for LLMs to use as structured context.

Requirements
	•	snapshots are JSON
	•	diffs are JSON
	•	diff categories are stable
	•	no hidden semantics only visible in human output
	•	machine-readable output must be first-class, not an afterthought

This allows LLMs to:
	•	understand what just changed
	•	propose the next iteration
	•	avoid re-explaining unchanged context unnecessarily

⸻

Part 10 — Human Experience Constraints

This feature must make iteration feel better, not noisier.

Required UX properties
	•	concise by default
	•	useful immediately after generate
	•	no overwhelming dump unless user asks for full explain
	•	no redundant repetition of the whole architecture if a diff summary is enough

Recommended default behavior after generate

Show:
	•	generation success/failure
	•	concise architecture summary of changes
	•	next recommended commands

Do not automatically dump the full explain output unless --explain is requested.

⸻

Part 11 — Internal Components

Introduce or update the following components:
	•	ExplainSnapshotService
	•	ExplainDiffService
	•	Generate integration hook in GenerateEngine
	•	CLI support in ExplainCommand
	•	CLI support in GenerateCommand

Optional internal model objects:
	•	ExplainSnapshot
	•	ExplainDiff
	•	ExplainDiffItem

Use explicit data models if they improve determinism and testing.

⸻

Part 12 — Testing

Add tests for:
	1.	pre-generate snapshot creation
	2.	post-generate snapshot creation
	3.	diff generation after successful generate
	4.	no diff generation on failed generate
	5.	deterministic diff ordering
	6.	foundry explain --diff
	7.	foundry explain --diff --json
	8.	foundry generate --explain
	9.	extension/pack-aware diff output
	10.	snapshot schema/version mismatch handling

Testing Bias

Assert:
	•	exact JSON shape where possible
	•	exact added/removed/modified classification
	•	stable ordering
	•	clean failure modes

Do not rely only on visual snapshot tests.

⸻

Part 13 — Documentation

Update:
	•	README
	•	explain docs
	•	generate docs
	•	onboarding/iteration docs if applicable

Add a short section describing the iteration loop:

foundry generate "Add feature"
foundry explain --diff
foundry generate "Refine feature"

Documentation must explain that the diff is architectural, not just file-based.

⸻

Part 14 — Acceptance Criteria
	•	generate always creates pre/post explain snapshots on successful runs
	•	foundry explain --diff works deterministically
	•	diff output reflects real architectural changes
	•	failed generation does not produce misleading diffs
	•	snapshots and diffs are machine-readable and version-aware
	•	extension/pack contributions are represented correctly
	•	users get a fast, useful next step after generate

⸻

Done Means

Foundry now provides a true tight iteration loop:
	•	understand current system
	•	change the system
	•	immediately see what changed architecturally
	•	iterate again with confidence

This is the moment Foundry stops feeling like separate commands and starts feeling like:

one continuous, structured development conversation.
:::

This is the version I’d hand to Codex.

The key upgrade is that it now clearly defines:
	•	the snapshot contract
	•	the diff contract
	•	failure behavior
	•	CLI behavior
	•	pack awareness
	•	schema/version safety

So Codex can implement it without inventing too much of the system on its own.

⸻

⸻

B. Post-Generate Snapshot

After generate completes:

Capture:

.foundry/snapshots/post-generate.json


⸻

⸻

C. Diff Generation (Explain-Level)

Create:

src/Explain/Diff/ExplainDiffService.php

Produces:

{
  "added": [...],
  "removed": [...],
  "modified": [...]
}

This is NOT file diff.

This is:

architecture-level diff

⸻

⸻

D. CLI Output (Generate)

After running:

foundry generate "Add comments"

Output:

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