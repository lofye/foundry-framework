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
