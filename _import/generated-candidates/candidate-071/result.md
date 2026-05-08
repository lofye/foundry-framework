Implemented Spec 34 across the pack pipeline. The core changes are in [PackManifest.php](/Users/lo_fye/code/foundry-framework/src/Packs/PackManifest.php), [PackChecksum.php](/Users/lo_fye/code/foundry-framework/src/Packs/PackChecksum.php), [HostedPackRegistry.php](/Users/lo_fye/code/foundry-framework/src/Packs/HostedPackRegistry.php), [HostedPackRegistryEntry.php](/Users/lo_fye/code/foundry-framework/src/Packs/HostedPackRegistryEntry.php), [PackManager.php](/Users/lo_fye/code/foundry-framework/src/Packs/PackManager.php), [PackArchiveExtractor.php](/Users/lo_fye/code/foundry-framework/src/Packs/PackArchiveExtractor.php), [InstalledPackRegistry.php](/Users/lo_fye/code/foundry-framework/src/Packs/InstalledPackRegistry.php), and [LocalPackLoader.php](/Users/lo_fye/code/foundry-framework/src/Packs/LocalPackLoader.php). Packs now require `checksum` and `signature`, installs verify package checksums, hosted registry entries use the `/packs` contract with `checksum`/`signature`/`verified`, exact hosted versions resolve via `vendor/pack@1.2.0`, archive extraction requires root `foundry.json` plus `src/`, and registry writes are atomic.

The CLI and docs were aligned too. [PackCommand.php](/Users/lo_fye/code/foundry-framework/src/CLI/Commands/PackCommand.php) now reports local vs remote source and includes explain-backed pack summaries in `pack info`, while [ApiSurfaceRegistry.php](/Users/lo_fye/code/foundry-framework/src/Support/ApiSurfaceRegistry.php), [README.md](/Users/lo_fye/code/foundry-framework/README.md), [extensions-and-migrations.md](/Users/lo_fye/code/foundry-framework/docs/extensions-and-migrations.md), and [extension-author-guide.md](/Users/lo_fye/code/foundry-framework/docs/extension-author-guide.md) now describe the stricter manifest and hosted registry contract. Test coverage was updated in [CLIPackCommandsTest.php](/Users/lo_fye/code/foundry-framework/tests/Integration/CLIPackCommandsTest.php), [HostedPackRegistryTest.php](/Users/lo_fye/code/foundry-framework/tests/Unit/HostedPackRegistryTest.php), [PackArchiveExtractorTest.php](/Users/lo_fye/code/foundry-framework/tests/Unit/PackArchiveExtractorTest.php), [PackManifestTest.php](/Users/lo_fye/code/foundry-framework/tests/Unit/PackManifestTest.php), plus the pack fixtures under [tests/Fixtures/Packs](/Users/lo_fye/code/foundry-framework/tests/Fixtures/Packs).

Verification passed:
- `php bin/foundry verify cli-surface --json` -> `128` valid, `0` invalid, `0` ambiguous, coverage `1`
- `php bin/foundry verify graph --json` -> `ok: true`
- `php bin/foundry compile graph --json` -> completed successfully
- `php vendor/bin/phpunit` -> `390` tests passed, `3103` assertions

Assumption I kept intentionally: pack storage stays under `.foundry/packs/...` to preserve the repository’s existing deterministic project-state convention, while aligning the manifest, hosted registry, install semantics, explain surface, and CLI behavior with Spec 34.

21 files changed
+690
-42
Undo
README.md
docs/extension-author-guide.md
docs/extensions-and-migrations.md
src/CLI/Commands/PackCommand.php
src/Packs/HostedPackRegistry.php
src/Packs/HostedPackRegistryEntry.php
src/Packs/InstalledPackRegistry.php
src/Packs/LocalPackLoader.php
src/Packs/PackArchiveExtractor.php
src/Packs/PackChecksum.php
src/Packs/PackManager.php
src/Packs/PackManifest.php
src/Support/ApiSurfaceRegistry.php
tests/Fixtures/Packs/acme-zeta/foundry.json
tests/Fixtures/Packs/foundry-blog-duplicate/foundry.json
tests/Fixtures/Packs/foundry-blog-tools/foundry.json
tests/Fixtures/Packs/foundry-blog/foundry.json
tests/Integration/CLIPackCommandsTest.php
tests/Unit/HostedPackRegistryTest.php
tests/Unit/PackArchiveExtractorTest.php
tests/Unit/PackManifestTest.php


------------------------------------------------------------------------------------------



------------------------------------------------------------------------------------------



------------------------------------------------------------------------------------------
