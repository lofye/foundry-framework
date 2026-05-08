Specs Spec 18 — Documentation System (Site + Generation + Structure)

Preface

Foundry’s documentation is not just explanatory—it is a reflection of the architecture itself.

The docs system must:
	•	align with Foundry’s graph and structure
	•	be partially generated from source metadata
	•	remain human-readable and curated

All new code must maintain ≥ 90% automated test coverage.

⸻

Goals

Spec 18 must:
	•	unify documentation structure
	•	support generated + hand-written content
	•	integrate with graph and schema metadata
	•	support static site output

⸻

Requirements

1. Documentation sources

Docs must support:
	•	hand-written pages (HTML/Markdown)
	•	generated content from:
	•	schemas
	•	features
	•	graph
	•	CLI commands

⸻

2. Build process

Provide a docs build process:

php scripts/build-docs.php

This must:
	•	generate versioned docs
	•	merge static + generated content
	•	output to /public or equivalent

⸻

3. Versioning

Docs must support:
	•	versioned snapshots
	•	mapping to framework tags

Example structure:

docs/
  versions/
    v0.4.0/
    v0.4.1/


⸻

4. Navigation structure

Docs must include:
	•	main navigation (top-level pages)
	•	side navigation (section-based)
	•	consistent linking between:
	•	intro
	•	how it works
	•	quick tour
	•	API/docs

⸻

5. Graph integration

Docs should be able to include:
	•	graph snapshots
	•	architecture explanations
	•	CLI outputs

⸻

6. CLI reference generation

CLI commands should be:
	•	discoverable
	•	documented automatically where possible

⸻

7. Static-first design

The docs system must:
	•	produce static output
	•	require no runtime backend
	•	be deployable to simple hosting (e.g., CDN)

⸻

8. Clean separation from app code

Docs generation must not:
	•	pollute application runtime
	•	interfere with framework execution

⸻

Deliverables
	•	unified docs structure
	•	build pipeline
	•	versioned docs output
	•	navigation system
	•	generated CLI/reference docs
	•	integration with graph and schemas

⸻

Testing Requirements

Tests must cover:
	•	docs generation
	•	version snapshot handling
	•	navigation consistency
	•	generated content correctness

Coverage must remain ≥ 90%.

⸻⸻⸻⸻⸻⸻⸻⸻⸻⸻⸻⸻⸻⸻⸻⸻⸻⸻⸻⸻---

RESULT (Spec 18)

Implemented Spec 18 as a framework docs build system.

The main pieces are in [DocsSiteBuilder.php](/Users/lo_fye/code/foundry-framework/src/Documentation/DocsSiteBuilder.php), [GraphDocsGenerator.php](/Users/lo_fye/code/foundry-framework/src/Documentation/GraphDocsGenerator.php), and the new entrypoint [build-docs.php](/Users/lo_fye/code/foundry-framework/scripts/build-docs.php). The builder now merges curated docs with generated graph/schema/CLI content, renders a static site with top nav + section nav, writes current docs to `public/docs`, and writes versioned snapshots to `public/docs/versions/<version>`. It also scans `docs/versions/*` for archived snapshot sources and rewrites example README links into site-local pages so the static output stays deployable.

I added curated landing pages at [intro.md](/Users/lo_fye/code/foundry-framework/docs/intro.md), [quick-tour.md](/Users/lo_fye/code/foundry-framework/docs/quick-tour.md), [how-it-works.md](/Users/lo_fye/code/foundry-framework/docs/how-it-works.md), and [reference.md](/Users/lo_fye/code/foundry-framework/docs/reference.md), plus snapshot guidance at [docs/versions/README.md](/Users/lo_fye/code/foundry-framework/docs/versions/README.md). Tests for generation, version handling, navigation, and content correctness are in [DocsSiteBuilderTest.php](/Users/lo_fye/code/foundry-framework/tests/Unit/DocsSiteBuilderTest.php) and the updated [GraphDocsGeneratorTest.php](/Users/lo_fye/code/foundry-framework/tests/Unit/GraphDocsGeneratorTest.php).

Verified with:
- `vendor/bin/phpunit tests/Unit/GraphDocsGeneratorTest.php tests/Unit/DocsSiteBuilderTest.php tests/Unit/CLICommandsTest.php tests/Unit/CLICommandMatchesTest.php tests/Integration/CLIIntegrationCommandsTest.php tests/Integration/CLIInitAppCommandTest.php`
- `php bin/foundry compile graph --json`

I did not run `php scripts/build-docs.php` in the repo worktree, so `public/docs` has not been materialized here yet.

12 files changed
+1613
-39
Undo
README.md
docs/how-it-works.md
docs/intro.md
docs/quick-tour.md
docs/reference.md
docs/versions/README.md
scripts/build-docs.php
src/Documentation/DocsSiteBuilder.php
src/Documentation/GraphDocsGenerator.php
src/Documentation/MarkdownPageRenderer.php
tests/Unit/DocsSiteBuilderTest.php
tests/Unit/GraphDocsGeneratorTest.php
