Spec 30D-2 — First-Run Experience Cleanup and Truthfulness Pass

Purpose

Clean up the first-run experience introduced in Spec 30D so it is:
	•	honest
	•	deterministic
	•	taxonomy-aligned
	•	documentation-consistent
	•	easier for both humans and LLMs to trust

This is a cleanup and refinement pass, not a redesign.

⸻

Goals
	1.	Align first-run option labels with the actual examples/assets loaded
	2.	Ensure onboarding terminology matches the real example taxonomy
	3.	Remove any misleading or fuzzy wording from first-run UX
	4.	Ensure docs/help/readmes all describe the first-run flow consistently
	5.	Preserve deterministic behavior and current passing verification

⸻

Non-Goals
	•	Do not redesign the first-run architecture
	•	Do not add new onboarding flows unless required for consistency
	•	Do not replace FirstRunService
	•	Do not remove offline-first behavior
	•	Do not weaken the current deterministic default explain behavior

⸻

Context

Spec 30D landed successfully, but one implementation choice introduced a truthfulness gap:
	•	the first-run menu labels imply example types that do not map perfectly to the actual example assets being loaded
	•	one path uses the runnable blog-api equivalent
	•	one path composes hello-world plus extensions/migrations assets

That is acceptable temporarily, but the UX and docs must now reflect reality cleanly.

⸻

Part 1 — First-Run Menu Truthfulness

Audit the current first-run menu options and determine whether each option name exactly matches what is actually loaded.

If a label over-promises or is imprecise, rename it.

Examples of acceptable naming patterns:
	•	“Load runnable blog API example”
	•	“Load example app with extensions and migrations assets”
	•	“Explore canonical app example”
	•	“Explore framework extension example”

Examples of unacceptable naming:
	•	labels that imply a different example than the one actually loaded
	•	labels that imply a complete standalone example when the flow composes assets from multiple sources
	•	labels that use category names in a misleading way

Required outcome:
	•	each first-run option must accurately describe the real action taken

⸻

Part 2 — Taxonomy Alignment

Align first-run UX with the current canonical example taxonomy used elsewhere in the repo:
	•	canonical
	•	reference
	•	framework

Do not use mismatched or legacy taxonomy in the first-run flow unless the example inventory truly requires a special phrase.

If the first-run menu needs friendlier user-facing wording, that is allowed, but the wording must still map clearly to the taxonomy.

Example:
	•	user-facing: “Explore a canonical app example”
	•	internal/example taxonomy: canonical

Required outcome:
	•	no conceptual mismatch between:
	•	examples/README.md
	•	docs/example-applications.md
	•	first-run menu
	•	README onboarding instructions

⸻

Part 3 — Example Loader Contract Cleanup

Audit ExampleLoader and document or enforce the exact contract for each onboarding choice.

For each first-run load path, make explicit:
	•	source example(s)
	•	whether it is a direct copy or composed setup
	•	destination behavior
	•	overwrite behavior
	•	temporary vs working-directory behavior

If needed, add structured metadata for each onboarding option rather than hardcoding ambiguous labels inline.

For example, introduce an internal registry shape such as:
	•	id
	•	label
	•	description
	•	taxonomy
	•	source_examples
	•	mode (direct_copy | composed)
	•	explain_default_target

Required outcome:
	•	onboarding choices are data-driven and inspectable
	•	no hidden composition logic without clear metadata

⸻

Part 4 — Deterministic Default Explain Contract

Spec 30D introduced deterministic default subject selection in ExplainCommand when no target is provided.

This cleanup pass must make that behavior explicit and intentional.

Required tasks:
	1.	document the default explain behavior in CLI/help/docs
	2.	ensure tests describe it as a stable contract
	3.	ensure the selection logic is deterministic, not “best effort”
	4.	ensure example onboarding depends on that contract intentionally

Required outcome:
	•	“foundry explain with no target” is clearly defined behavior, not an accidental convenience

⸻

Part 5 — CLI Help and Discovery Consistency

Audit all CLI-facing descriptions of the first-run flow, including:
	•	top-level foundry no-args behavior
	•	init
	•	examples:list
	•	examples:load
	•	help text
	•	CLI metadata / surface registry / command catalog

Ensure they all describe the same behavior and use the same vocabulary.

Fix:
	•	stale command summaries
	•	duplicated but inconsistent descriptions
	•	overlong explanations where shorter wording would be clearer

Required outcome:
	•	CLI discovery feels like one coherent system

⸻

Part 6 — Documentation Consistency Pass

Audit and reconcile:
	•	README.md
	•	APP-README.md
	•	APP-AGENTS.md
	•	command catalog docs
	•	any example-related onboarding docs

Required checks:
	1.	do all docs describe the same first-run path?
	2.	do they name the same commands?
	3.	do they use the same example terminology?
	4.	do they describe no-args foundry behavior correctly?
	5.	do they clearly separate:
	•	first-run exploration
	•	example loading
	•	normal project inspection

Required outcome:
	•	no contradictions remain across onboarding docs

⸻

Part 7 — Safer UX Language

Review all first-run copy for:
	•	ambiguity
	•	over-promising
	•	vague “magic” wording

Prefer:
	•	concrete
	•	observable
	•	deterministic language

Examples:
	•	“Load example and inspect architecture”
	•	“Copy example into current directory”
	•	“Run explain on the loaded example”

Avoid:
	•	“set up everything automatically” unless that is literally true
	•	“reference app” if what is loaded is actually a canonical runnable app
	•	“blog” if the loaded example is specifically blog-api

Required outcome:
	•	users understand exactly what will happen before they choose it

⸻

Part 8 — Test Cleanup and Coverage Expansion

Add or refine tests for:
	•	first-run menu option labeling
	•	mapping from option → actual example loader source
	•	deterministic default explain target
	•	no-args foundry onboarding flow
	•	docs/help metadata where already tested in the repo
	•	composed example path behavior, if composition remains part of the design

If possible, assert not just that commands succeed, but that the UX contract is truthful.

Required outcome:
	•	cleanup is protected from regression

⸻

Part 9 — Optional Structural Improvement

If the current composed onboarding path feels inherently awkward, you may do one of the following:

Option A

Keep the composition, but make it explicit and well-labeled

Option B

Create a cleaner dedicated onboarding example asset if that is smaller/clearer than ongoing explanatory hacks

Use the smallest truthful solution.
Do not expand scope unnecessarily.

⸻

Part 10 — Acceptance Criteria
	•	first-run option labels accurately describe what is loaded
	•	taxonomy is aligned with the rest of the repo
	•	ExampleLoader behavior is explicit and inspectable
	•	default foundry explain behavior is documented and treated as intentional
	•	CLI help and onboarding docs are consistent
	•	no misleading wording remains in the first-run flow
	•	current verification and tests still pass
	•	first-run experience is more trustworthy without becoming more complex

⸻

Implementation Bias

Prefer:
	•	truthfulness over marketing
	•	alignment over cleverness
	•	data-driven option definitions over hardcoded menu prose
	•	small honest fixes over broad redesign

This spec is about making the first-run experience feel like:
“this system knows exactly what it is doing”
instead of:
“this system is trying to be helpful, approximately.”

⸻

Done Means

A new user can:
	•	run foundry
	•	choose an option
	•	correctly understand what will happen
	•	get a deterministic result
	•	and see documentation/help that matches the actual behavior

without encountering subtle mismatches between labels, examples, and reality.



------------------------------------------------------------------------------------------

RESULTS

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