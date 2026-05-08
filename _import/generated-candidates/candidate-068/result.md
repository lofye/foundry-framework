Spec 31 is implemented. Foundry now has a real offline pack foundation: local install/remove/list/info CLI support via [PackCommand.php](/Users/lo_fye/code/foundry-framework/src/CLI/Commands/PackCommand.php), manifest validation in [PackManifest.php](/Users/lo_fye/code/foundry-framework/src/Packs/PackManifest.php), deterministic storage and activation state in [InstalledPackRegistry.php](/Users/lo_fye/code/foundry-framework/src/Packs/InstalledPackRegistry.php), and pack boot/loading through [LocalPackLoader.php](/Users/lo_fye/code/foundry-framework/src/Packs/LocalPackLoader.php), [PackContext.php](/Users/lo_fye/code/foundry-framework/src/Packs/PackContext.php), and [PackServiceProvider.php](/Users/lo_fye/code/foundry-framework/src/Packs/PackServiceProvider.php). Packs install into `.foundry/packs/{vendor}/{pack}/{version}/`, activate through `.foundry/packs/installed.json`, load in deterministic name/version order, and fail explicitly on command/schema collisions or duplicate graph node ids.

I wired the pack layer into graph/compiler flows in [ExtensionRegistry.php](/Users/lo_fye/code/foundry-framework/src/Compiler/Extensions/ExtensionRegistry.php), [SourceScanner.php](/Users/lo_fye/code/foundry-framework/src/Compiler/SourceScanner.php), [ApplicationGraph.php](/Users/lo_fye/code/foundry-framework/src/Compiler/ApplicationGraph.php), and [ConfigSchemaCatalog.php](/Users/lo_fye/code/foundry-framework/src/Config/ConfigSchemaCatalog.php), then updated the public surface/docs in [README.md](/Users/lo_fye/code/foundry-framework/README.md), [APP-README.md](/Users/lo_fye/code/foundry-framework/APP-README.md), [APP-AGENTS.md](/Users/lo_fye/code/foundry-framework/APP-AGENTS.md), [extension-author-guide.md](/Users/lo_fye/code/foundry-framework/docs/extension-author-guide.md), [extensions-and-migrations.md](/Users/lo_fye/code/foundry-framework/docs/extensions-and-migrations.md), and [CommandCatalog.php](/Users/lo_fye/code/foundry-framework/src/Documentation/CommandCatalog.php). Coverage was added in [CLIPackCommandsTest.php](/Users/lo_fye/code/foundry-framework/tests/Integration/CLIPackCommandsTest.php), [PackManifestTest.php](/Users/lo_fye/code/foundry-framework/tests/Unit/PackManifestTest.php), and [ExtensionRegistryTest.php](/Users/lo_fye/code/foundry-framework/tests/Unit/ExtensionRegistryTest.php).

Verification passed:
- `php bin/foundry compile graph --json`
- `php bin/foundry verify graph --json`
- `php bin/foundry verify cli-surface --json` -> `127` valid, `0` invalid, `0` ambiguous, `0` orphan handlers, coverage `1`
- `php vendor/bin/phpunit` -> `373` tests passed, `3009` assertions

40 files changed
+2313
-55
Undo
APP-AGENTS.md
APP-README.md
README.md
docs/extension-author-guide.md
docs/extensions-and-migrations.md
src/CLI/Application.php
src/CLI/Commands/PackCommand.php
src/Compiler/ApplicationGraph.php
src/Compiler/Extensions/ExtensionMetadataValidator.php
src/Compiler/Extensions/ExtensionRegistry.php
src/Compiler/Passes/EnrichPass.php
src/Compiler/SourceScanner.php
src/Config/ConfigSchemaCatalog.php
src/Documentation/CommandCatalog.php
src/Packs/InstalledPackExtension.php
src/Packs/InstalledPackRegistry.php
src/Packs/LocalPackLoader.php
src/Packs/PackContext.php
src/Packs/PackManager.php
src/Packs/PackManifest.php
src/Packs/PackServiceProvider.php
src/Support/ApiSurfaceRegistry.php
tests/Fixtures/Packs/acme-zeta/foundry.json
tests/Fixtures/Packs/acme-zeta/src/AcmeZetaPackServiceProvider.php
tests/Fixtures/Packs/foundry-blog-duplicate/foundry.json
tests/Fixtures/Packs/foundry-blog-duplicate/src/FoundryBlogDuplicateExtension.php
tests/Fixtures/Packs/foundry-blog-duplicate/src/FoundryBlogDuplicateInterceptorPass.php
tests/Fixtures/Packs/foundry-blog-duplicate/src/FoundryBlogDuplicatePackServiceProvider.php
tests/Fixtures/Packs/foundry-blog-tools/foundry.json
tests/Fixtures/Packs/foundry-blog-tools/src/FoundryBlogToolsPackServiceProvider.php
tests/Fixtures/Packs/foundry-blog/foundry.json
tests/Fixtures/Packs/foundry-blog/src/FoundryBlogExtension.php
tests/Fixtures/Packs/foundry-blog/src/FoundryBlogInterceptorPass.php
tests/Fixtures/Packs/foundry-blog/src/FoundryBlogPackServiceProvider.php
tests/Integration/CLIApplicationTest.php
tests/Integration/CLIPackCommandsTest.php
tests/Unit/ApiSurfaceRegistryTest.php
tests/Unit/CLICommandMatchesTest.php
tests/Unit/ExtensionRegistryTest.php
tests/Unit/PackManifestTest.php


------------------------------------------------------------------------------------------



------------------------------------------------------------------------------------------



------------------------------------------------------------------------------------------
