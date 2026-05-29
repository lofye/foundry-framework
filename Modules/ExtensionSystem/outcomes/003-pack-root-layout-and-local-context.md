# 003-pack-root-layout-and-local-context

## Spec Implemented

- `Modules/ExtensionSystem/specs/003-pack-root-layout-and-local-context.md`

## Implementation Summary

- Changed new pack installs to copy local and hosted archive roots into `Packs/{vendor}/{pack}/`.
- Kept `.foundry/packs/installed.json` as the sole activation registry and source of active version metadata.
- Added legacy-root resolution so `.foundry/packs/{vendor}/{pack}/{version}/` remains readable when no canonical `Packs/` root exists.
- Made canonical roots win deterministically when both canonical and legacy roots exist.
- Added canonical path and local-context metadata to pack list, info, inspect, explain-backed extension rows, source scanning, and API surface classification.

## Files Introduced

- `Modules/ExtensionSystem/outcomes/003-pack-root-layout-and-local-context.md`

## Files Modified

- `src/Packs/InstalledPackRegistry.php`
- `src/Packs/PackManager.php`
- `src/Packs/LocalPackLoader.php`
- `src/Packs/InstalledPackExtension.php`
- `src/Compiler/Extensions/PackDefinition.php`
- `src/Compiler/SourceScanner.php`
- `src/Config/ConfigSchemaCatalog.php`
- `src/Explain/ExplainOrigin.php`
- `src/Support/ApiSurfaceRegistry.php`
- `tests/Unit/InstalledPackRegistryTest.php`
- `tests/Unit/LocalPackLoaderTest.php`
- `tests/Unit/PackManagerTest.php`
- `tests/Unit/PackCommandRenderInternalsTest.php`
- `tests/Unit/ApiSurfaceRegistryTest.php`
- `tests/Unit/ExplainArchitectureCoverageTest.php`
- `tests/Integration/CLIPackCommandsTest.php`
- `tests/Integration/CLIGenerateCommandTest.php`
- `Modules/ExtensionSystem/extension-system.spec.md`
- `Modules/ExtensionSystem/extension-system.md`
- `Modules/ExtensionSystem/extension-system.decisions.md`
- `docs/extensions-and-migrations.md`
- `docs/extension-author-guide.md`
- `APP-AGENTS.md`
- `APP-README.md`

## Runtime Contracts

- `InstalledPackRegistry::installPath()` returns the canonical installed root `Packs/{vendor}/{pack}`.
- `InstalledPackRegistry::legacyInstallPath()` and `resolveInstallPath()` preserve deterministic compatibility with versioned legacy roots.
- `PackManager::install()` rejects an existing canonical target and never replaces `Packs/{vendor}/{pack}` implicitly.
- `LocalPackLoader` validates canonical roots for `foundry.json`, `src/`, manifest name/version alignment, and checksum before provider activation.
- `pack remove` deactivates the registry entry without deleting canonical or legacy pack files.

## Deterministic Outputs

- New `pack install`, `pack list`, and `pack info` payloads report `install_path` as `Packs/{vendor}/{pack}`.
- `inspect packs` rows include `install_path` and `local_context_paths` when available.
- Explain origin detection recognizes `Packs/{vendor}/{pack}/...` and legacy `.foundry/packs/{vendor}/{pack}/{version}/...` source paths.
- Source scanning includes active canonical pack files so generate and verification snapshots notice pack-owned changes.

## Tests Added Or Updated

- Added registry coverage for canonical and legacy install path resolution.
- Added pack-manager coverage for canonical local and hosted install paths, remove-without-delete behavior, and preservation of local context directories.
- Added loader coverage for canonical activation, missing canonical `foundry.json`, missing canonical `src/`, manifest mismatch diagnostics, canonical-over-legacy precedence, and legacy fallback loading.
- Updated CLI pack, generate, explain, API surface, and render tests to assert canonical paths.

## Verification Commands

- `php vendor/bin/phpunit tests/Unit/InstalledPackRegistryTest.php`
- `php vendor/bin/phpunit tests/Unit/LocalPackLoaderTest.php`
- `php vendor/bin/phpunit tests/Unit/PackManagerTest.php`
- `php vendor/bin/phpunit tests/Integration/CLIPackCommandsTest.php`
- `php vendor/bin/phpunit tests/Integration/CLIGenerateCommandTest.php --filter pack`
- `php vendor/bin/phpunit tests/Unit/PackArchiveExtractorTest.php tests/Unit/ExtensionRegistryTest.php tests/Unit/PackCommandRenderInternalsTest.php`
- `php vendor/bin/phpunit tests/Unit/ExplainArchitectureCoverageTest.php tests/Unit/ApiSurfaceRegistryTest.php`
- `php bin/foundry verify extensions --json`

## Decisions And Tradeoffs

- The activation registry remains in `.foundry/packs/installed.json` to avoid a second activation source.
- Legacy roots remain readable for compatibility but are not used for new installs.
- Canonical roots require `src/`; legacy roots keep their existing compatibility behavior during the transition.

## Reconstruction Notes

- Rebuild canonical installation by resolving the manifest from the source root, validating its checksum, copying the source tree to `Packs/{vendor}/{pack}/`, then activating the version in `.foundry/packs/installed.json`.
- Rebuild compatibility loading by resolving `Packs/{vendor}/{pack}/` first and falling back to `.foundry/packs/{vendor}/{pack}/{version}/` only when the canonical root is absent.
- Rebuild inspect integration by threading relative install paths and local context directory paths from `LocalPackLoader` into `InstalledPackExtension` and `PackDefinition`.

## Follow-Up Dependencies

- Future uninstall or replacement specs should define explicit deletion/replacement semantics for `Packs/{vendor}/{pack}/`.
- Future pack-local spec validation can enforce the pack-local execution spec naming contract introduced by the spec.
