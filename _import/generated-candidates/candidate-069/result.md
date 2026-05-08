Spec 32 is implemented. Foundry now has a minimal hosted pack registry client in [HostedPackRegistry.php](/Users/lo_fye/code/foundry-framework/src/Packs/HostedPackRegistry.php) and [HostedPackRegistryEntry.php](/Users/lo_fye/code/foundry-framework/src/Packs/HostedPackRegistryEntry.php), plus secure zip extraction in [PackArchiveExtractor.php](/Users/lo_fye/code/foundry-framework/src/Packs/PackArchiveExtractor.php). `pack install` now resolves local paths first and valid `vendor/pack` names second through the hosted registry inside [PackManager.php](/Users/lo_fye/code/foundry-framework/src/Packs/PackManager.php), so remote installs still reuse the same local install/activation pipeline from Spec 31 after download and extraction. The CLI now supports `foundry pack search <query>` and hosted installs in [PackCommand.php](/Users/lo_fye/code/foundry-framework/src/CLI/Commands/PackCommand.php), with help/discovery/docs surfaced through [ApiSurfaceRegistry.php](/Users/lo_fye/code/foundry-framework/src/Support/ApiSurfaceRegistry.php), [Application.php](/Users/lo_fye/code/foundry-framework/src/CLI/Application.php), and [CommandCatalog.php](/Users/lo_fye/code/foundry-framework/src/Documentation/CommandCatalog.php).

The hosted layer stays read-only and optional: it reads a public `registry.json`, chooses the highest semver deterministically, requires HTTPS `download_url` values, caches registry metadata at `.foundry/cache/registry.json`, and leaves local/offline pack installs unchanged when the registry is unavailable. I also updated the framework/app docs in [README.md](/Users/lo_fye/code/foundry-framework/README.md), [APP-README.md](/Users/lo_fye/code/foundry-framework/APP-README.md), [APP-AGENTS.md](/Users/lo_fye/code/foundry-framework/APP-AGENTS.md), [extension-author-guide.md](/Users/lo_fye/code/foundry-framework/docs/extension-author-guide.md), and [extensions-and-migrations.md](/Users/lo_fye/code/foundry-framework/docs/extensions-and-migrations.md), and added coverage in [HostedPackRegistryTest.php](/Users/lo_fye/code/foundry-framework/tests/Unit/HostedPackRegistryTest.php), [PackArchiveExtractorTest.php](/Users/lo_fye/code/foundry-framework/tests/Unit/PackArchiveExtractorTest.php), and [CLIPackCommandsTest.php](/Users/lo_fye/code/foundry-framework/tests/Integration/CLIPackCommandsTest.php).

Verification passed:
- `php bin/foundry verify cli-surface --json` -> `128` valid, `0` invalid, `0` ambiguous, `0` orphan handlers, coverage `1`
- `php bin/foundry verify graph --json` -> `ok: true`
- `php vendor/bin/phpunit` -> `386` tests passed, `3062` assertions

19 files changed
+1372
-33
Undo
APP-AGENTS.md
APP-README.md
README.md
docs/extension-author-guide.md
docs/extensions-and-migrations.md
src/CLI/Application.php
src/CLI/Commands/PackCommand.php
src/Documentation/CommandCatalog.php
src/Packs/HostedPackRegistry.php
src/Packs/HostedPackRegistryEntry.php
src/Packs/PackArchiveExtractor.php
src/Packs/PackManager.php
src/Support/ApiSurfaceRegistry.php
tests/Integration/CLIApplicationTest.php
tests/Integration/CLIPackCommandsTest.php
tests/Unit/ApiSurfaceRegistryTest.php
tests/Unit/CLICommandMatchesTest.php
tests/Unit/HostedPackRegistryTest.php
tests/Unit/PackArchiveExtractorTest.php

------------------------------------------------------------------------------------------



------------------------------------------------------------------------------------------



------------------------------------------------------------------------------------------
