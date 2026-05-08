Implemented Spec 23 inside the framework repo only.

The new static CLI index lives in [CliIndexPage.php](/Users/lo_fye/code/foundry-framework/src/Documentation/CliIndexPage.php#L12) and is wired into the docs build in [DocsSiteBuilder.php](/Users/lo_fye/code/foundry-framework/src/Documentation/DocsSiteBuilder.php#L42) and [DocsSiteBuilder.php](/Users/lo_fye/code/foundry-framework/src/Documentation/DocsSiteBuilder.php#L1145). It renders a deterministic `cli-index.html` from framework CLI metadata, with full-text search plus category, pipeline stage, extension, and command-type filters. Selecting a command shows usage, related docs, a command-playground link, explain targets, related commands, and related graph nodes.

To keep the new index consistent with actual CLI help output, I added the new metadata fields to [ApiSurfaceRegistry.php](/Users/lo_fye/code/foundry-framework/src/Support/ApiSurfaceRegistry.php#L646) and extracted shared command enrichment into [CommandCatalog.php](/Users/lo_fye/code/foundry-framework/src/Documentation/CommandCatalog.php#L14), which is now also used by [CommandPlaygroundPage.php](/Users/lo_fye/code/foundry-framework/src/Documentation/CommandPlaygroundPage.php#L12). I also linked the feature into generated and curated docs via [GraphDocsGenerator.php](/Users/lo_fye/code/foundry-framework/src/Documentation/GraphDocsGenerator.php#L108), [reference.md](/Users/lo_fye/code/foundry-framework/docs/reference.md), and [quick-tour.md](/Users/lo_fye/code/foundry-framework/docs/quick-tour.md).

Coverage was updated in [ApiSurfaceRegistryTest.php](/Users/lo_fye/code/foundry-framework/tests/Unit/ApiSurfaceRegistryTest.php), [CLIApplicationTest.php](/Users/lo_fye/code/foundry-framework/tests/Integration/CLIApplicationTest.php), [DocsSiteBuilderTest.php](/Users/lo_fye/code/foundry-framework/tests/Unit/DocsSiteBuilderTest.php), and [GraphDocsGeneratorTest.php](/Users/lo_fye/code/foundry-framework/tests/Unit/GraphDocsGeneratorTest.php).

Verified with `vendor/bin/phpunit tests/Unit/ApiSurfaceRegistryTest.php tests/Unit/DocsSiteBuilderTest.php tests/Unit/GraphDocsGeneratorTest.php tests/Integration/CLIApplicationTest.php tests/Integration/CLIInitAppCommandTest.php tests/Integration/LocalFoundryExecutableTest.php`.

12 files changed
+1466
-595
Undo
docs/quick-tour.md
docs/reference.md
src/Documentation/CliIndexPage.php
src/Documentation/CommandCatalog.php
src/Documentation/CommandPlaygroundPage.php
src/Documentation/DocsSiteBuilder.php
src/Documentation/GraphDocsGenerator.php
src/Support/ApiSurfaceRegistry.php
tests/Integration/CLIApplicationTest.php
tests/Unit/ApiSurfaceRegistryTest.php
tests/Unit/DocsSiteBuilderTest.php
tests/Unit/GraphDocsGeneratorTest.php


------------------------------------------------------------------------------------------



------------------------------------------------------------------------------------------



------------------------------------------------------------------------------------------
