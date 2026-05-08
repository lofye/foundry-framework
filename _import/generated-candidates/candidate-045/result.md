The new static command playground is now generated as `command-playground.html` and wired into the docs preview build in [DocsSiteBuilder.php](/Users/lo_fye/code/foundry-framework/src/Documentation/DocsSiteBuilder.php#L43) and [DocsSiteBuilder.php](/Users/lo_fye/code/foundry-framework/src/Documentation/DocsSiteBuilder.php#L1122). The page itself lives in [CommandPlaygroundPage.php](/Users/lo_fye/code/foundry-framework/src/Documentation/CommandPlaygroundPage.php#L21) and builds a browser-only command explorer from `ApiSurfaceRegistry` plus pre-generated preview data: every command is listed with name, description, and usage; selecting one shows usage examples, deterministic JSON preview data, related docs, related explain targets, related commands, and graph links when they apply. No commands are executed at runtime.

I also linked the feature into the generated docs surfaces in [GraphDocsGenerator.php](/Users/lo_fye/code/foundry-framework/src/Documentation/GraphDocsGenerator.php#L104) and [GraphDocsGenerator.php](/Users/lo_fye/code/foundry-framework/src/Documentation/GraphDocsGenerator.php#L410), plus the curated docs entry points in [reference.md](/Users/lo_fye/code/foundry-framework/docs/reference.md#L5) and [quick-tour.md](/Users/lo_fye/code/foundry-framework/docs/quick-tour.md#L7). Coverage was updated in [DocsSiteBuilderTest.php](/Users/lo_fye/code/foundry-framework/tests/Unit/DocsSiteBuilderTest.php#L87) and [GraphDocsGeneratorTest.php](/Users/lo_fye/code/foundry-framework/tests/Unit/GraphDocsGeneratorTest.php#L72).

Verified with `vendor/bin/phpunit tests/Unit/ApiSurfaceRegistryTest.php tests/Unit/DocsSiteBuilderTest.php tests/Unit/GraphDocsGeneratorTest.php tests/Integration/CLIApplicationTest.php tests/Integration/CLIInitAppCommandTest.php tests/Integration/LocalFoundryExecutableTest.php`.

7 files changed
+1148
-2
Undo
docs/quick-tour.md
docs/reference.md
src/Documentation/CommandPlaygroundPage.php
src/Documentation/DocsSiteBuilder.php
src/Documentation/GraphDocsGenerator.php
tests/Unit/DocsSiteBuilderTest.php
tests/Unit/GraphDocsGeneratorTest.php


------------------------------------------------------------------------------------------



------------------------------------------------------------------------------------------



------------------------------------------------------------------------------------------
