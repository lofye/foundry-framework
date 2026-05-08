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
