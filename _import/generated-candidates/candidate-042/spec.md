# Spec 19Q — Framework Docs Pipeline Deprecation and Cleanup

Purpose

Deprecate and clean up framework-repo docs publishing machinery that is now redundant with the website repo.

Keep framework docs as canonical source content.
Make the website repo the only canonical renderer/publisher of public docs.
Remove architectural confusion without breaking framework runtime or scaffolding.

⸻

Non-Negotiable Rules
	•	Do NOT remove framework/public/
	•	Do NOT remove framework/public/index.php
	•	Do NOT break local framework runtime or scaffolded app behavior
	•	Do NOT remove canonical authored docs from framework/docs/
	•	Do NOT break website docs build/publish flow
	•	Prefer deprecation first, removal later
	•	Do NOT modify anything in the website repository. All changes must be confined to the framework repository. Assume the website repo is already the canonical docs renderer and publisher.

⸻

Context

The framework repo still contains legacy docs-publishing artifacts:
	•	framework/scripts/build-docs.php
	•	framework/docs/versions/README.md
	•	framework-local docs builder code/tests that support a second docs publishing path

These are redundant with the website repo’s real docs rendering/versioning system and are now a source of confusion.

⸻

Goals
	1.	Stop presenting the framework repo as the place to render/publish public docs
	2.	Preserve framework docs as canonical authored source content
	3.	Deprecate framework-local docs publishing entrypoints
	4.	Narrow or retire legacy version-snapshot source behavior in the framework repo
	5.	Keep runtime/scaffold behavior intact

⸻

Required Tasks
	1.	Update framework README and docs

Find and update all framework-repo docs that currently imply or instruct that public docs should be rendered/published from the framework repo.

Required outcome:
	•	framework repo is documented as the source of canonical docs content
	•	website repo is documented as the source of docs rendering/publishing/version snapshots

Where relevant, replace old instructions like:
	•	run framework scripts/build-docs.php

with wording that makes clear:
	•	framework docs are authored here
	•	website repo imports/renders/publishes them

⸻

	2.	Deprecate framework/scripts/build-docs.php

Do NOT hard-delete it immediately unless it is clearly safe.

Preferred actions:
	•	add an explicit deprecation notice
	•	make its purpose narrow and explicit if kept temporarily
	•	stop presenting it as the canonical docs pipeline
	•	if retained, label it as internal/legacy/local preview only

If safe, convert it into a minimal helper or wrapper that clearly points developers to the website repo for actual docs publishing.

⸻

	3.	Deprecate framework-local version snapshot ownership

framework/docs/versions/ must no longer be treated as the authoritative source of published version snapshots.

Required direction:
	•	website repo owns versioned public docs publishing
	•	framework repo owns canonical authored docs only

Do not delete framework/docs/versions/ until:
	•	framework references are updated
	•	tests are updated
	•	no active code path depends on it as the intended publishing source

If retained temporarily:
	•	mark it deprecated
	•	explain that website repo is authoritative for published version snapshots

⸻

	4.	Audit and update framework-local docs builder code/tests

Identify code/tests related to framework-local docs publishing, including:
	•	docs builder classes
	•	tests around version snapshot publishing
	•	references that assume framework repo publishes public docs directly

Update them so they either:
	•	support only canonical docs preparation/source behavior, or
	•	are explicitly marked legacy/deprecated if temporarily retained

Do NOT leave tests enforcing the old worldview silently.

⸻

	5.	Preserve canonical authored docs

Keep these in the framework repo:
	•	philosophy
	•	execution model
	•	graph spec
	•	core concepts
	•	framework capabilities
	•	architecture/reference docs
	•	other canonical framework docs

This spec is about deprecating framework-side publishing, not removing framework-side documentation.

⸻

	6.	Add explicit boundary note in framework repo

Add/update framework AGENTS.md and/or contributor docs so they clearly state:
	•	framework/docs/ = canonical authored framework docs
	•	website repo = renderer/publisher of public docs
	•	do not use framework-local docs builder as the primary publishing path

Keep wording short and explicit.

⸻

	7.	Remove wrong commands/instructions from framework docs

Anywhere the framework repo still tells contributors/users to build public docs from the framework repo, update or remove that guidance.

Do not leave conflicting instructions in:
	•	README
	•	reference docs
	•	contributor docs
	•	scripts comments/help output

⸻

Acceptance Criteria
	•	framework runtime/scaffold behavior still works
	•	framework/public/ and framework/public/index.php remain intact
	•	framework README/docs no longer present the framework repo as the canonical docs renderer/publisher
	•	framework/scripts/build-docs.php is deprecated, narrowed, or removed safely
	•	framework/docs/versions/ is deprecated as a publishing source
	•	framework-local docs builder/tests are updated to match the new boundary
	•	framework docs remain the canonical authored source
	•	website repo remains the canonical renderer/publisher of public docs

⸻

Recommended Implementation Bias
	•	smallest safe diff
	•	deprecate before delete
	•	preserve canonical content
	•	remove publishing ambiguity
	•	do not disturb working website docs system

⸻

Done Means

The framework repo no longer implies:
“build and publish public docs here.”

It instead clearly means:
“author canonical framework docs here; render/publish them from the website repo.”

------------------------------------------------------------------------------------------

RESULT

Implemented Spec 19Q entirely inside the framework repo.

The main boundary change is now documented in [README.md](/Users/lo_fye/code/foundry-framework/README.md), [AGENTS.md](/Users/lo_fye/code/foundry-framework/AGENTS.md), [docs/intro.md](/Users/lo_fye/code/foundry-framework/docs/intro.md), [docs/quick-tour.md](/Users/lo_fye/code/foundry-framework/docs/quick-tour.md), [docs/reference.md](/Users/lo_fye/code/foundry-framework/docs/reference.md), [docs/how-it-works.md](/Users/lo_fye/code/foundry-framework/docs/how-it-works.md), and [docs/versions/README.md](/Users/lo_fye/code/foundry-framework/docs/versions/README.md): the framework repo is now described as the canonical source of authored docs, while the website repo is described as the only canonical renderer/publisher of public docs and version snapshots. Old “build and publish public docs here” wording was removed or rewritten as deprecated local preview guidance.

I kept `scripts/build-docs.php` and the framework-side builder, but deprecated them instead of treating them as canonical publishing machinery. [scripts/build-docs.php](/Users/lo_fye/code/foundry-framework/scripts/build-docs.php) is now explicitly marked as a legacy local preview helper, and [DocsSiteBuilder.php](/Users/lo_fye/code/foundry-framework/src/Documentation/DocsSiteBuilder.php) now emits `legacy_local_preview` metadata plus a visible preview banner so its output no longer silently looks like the authoritative public docs pipeline. I also updated [DocsSiteBuilderTest.php](/Users/lo_fye/code/foundry-framework/tests/Unit/DocsSiteBuilderTest.php) to assert that deprecated/legacy status instead of enforcing the old worldview.

`framework/public/` and `framework/public/index.php` were left untouched, and scaffold behavior was preserved.

Verified with `vendor/bin/phpunit tests/Unit/DocsSiteBuilderTest.php tests/Integration/CLIInitAppCommandTest.php tests/Integration/LocalFoundryExecutableTest.php`.

10 files changed
+116
-19
Undo
AGENTS.md
README.md
docs/how-it-works.md
docs/intro.md
docs/quick-tour.md
docs/reference.md
docs/versions/README.md
scripts/build-docs.php
src/Documentation/DocsSiteBuilder.php
tests/Unit/DocsSiteBuilderTest.php
