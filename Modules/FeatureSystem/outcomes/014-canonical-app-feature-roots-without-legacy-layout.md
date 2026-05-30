# 014-canonical-app-feature-roots-without-legacy-layout

## Spec Implemented

`Modules/FeatureSystem/specs/014-canonical-app-feature-roots-without-legacy-layout.md`

## Implementation Summary

- Made `Features/<Feature>/` the canonical app feature source root for generation, context initialization, source scanning, graph compilation, feature execution, and feature verification.
- Moved the framework smoke app feature fixtures from `app/features/*` to `Features/<Feature>/`, with runtime actions under `src/` and tests under `tests/`.
- Ensured generated apps create visible top-level `Features/`, `Modules/`, and `Packs/` roots.
- Removed new-app `app/features/` usage from core compiler, generator, doctor, explain, inspect, and API-surface paths.
- Preserved `docs/` as authored public documentation consumed by foundryframework.org, while making legacy app context under `docs/features/<feature>/` a feature-boundary violation.

## Files Introduced

- `Features/ContextPersistence/context-persistence.spec.md`
- `Features/ContextPersistence/context-persistence.md`
- `Features/ContextPersistence/context-persistence.decisions.md`
- `Features/ContextPersistence/src/Action.php`
- `Features/PublishPost/publish-post.spec.md`
- `Features/PublishPost/publish-post.md`
- `Features/PublishPost/publish-post.decisions.md`
- `Features/PublishPost/src/Action.php`
- `Modules/FeatureSystem/outcomes/014-canonical-app-feature-roots-without-legacy-layout.md`

## Files Modified

- `src/Support/FeatureNaming.php`
- `src/Support/Paths.php`
- `src/Generation/FeatureGenerator.php`
- `src/Generation/ContextManifestGenerator.php`
- `src/Compiler/SourceScanner.php`
- `src/Compiler/GraphCompiler.php`
- `src/Compiler/Passes/DiscoveryPass.php`
- `src/Compiler/Passes/NormalizePass.php`
- `src/FeatureSystem/FeatureWorkspaceService.php`
- `src/CLI/Commands/InitAppCommand.php`
- `src/Compiler/Passes/EmitPass.php`
- `APP-AGENTS.md`
- `APP-README.md`
- `README.md`
- `docs/demos/blog-demo-short.md`
- public docs and example documentation that referenced obsolete app feature paths
- `tests/Fixtures/TempProject.php`
- focused generator, context, feature-system, and init-app tests

## Runtime Contracts

- Feature directory names are PascalCase under `Features/`, derived from canonical kebab-case feature slugs.
- Generated feature runtime code is written to `Features/<Feature>/src/Action.php`.
- Generated feature tests are written to `Features/<Feature>/tests/` with snake_case PHP-safe filenames.
- `context init <feature>` writes canonical context files under `Features/<Feature>/`.
- `verify features --json` fails when `app/features/` exists and when legacy app context exists under `docs/features/<feature>/`.
- Fresh app scaffolding writes `Features/.gitkeep`, `Modules/.gitkeep`, and `Packs/.gitkeep`.

## Deterministic Outputs

- Compile and inspect output now reports feature source paths such as `Features/PublishPost/feature.yaml` and base paths such as `Features/PublishPost`.
- Generated app Composer autoload includes a classmap for `Features/`.
- PHPUnit scaffold source/test configuration includes `Features/`.
- API surface metadata lists feature manifests and sibling declarations under `Features/*`.
- Generated PHP graph/projection files strip var_export trailing whitespace so regenerated projections remain diff-clean.

## Tests Added Or Updated

- `tests/Unit/FeatureGeneratorTest.php`
- `tests/Unit/ContextManifestGeneratorTest.php`
- `tests/Integration/CLIContextCommandsTest.php`
- `tests/Integration/CLIFeatureSystemCommandTest.php`
- `tests/Integration/CLIInitAppCommandTest.php`
- `tests/Integration/CLIExtendedCommandsTest.php`
- `tests/Integration/CLILicensedCommandsTest.php`
- `tests/Fixtures/TempProject.php`

## Verification Commands

- `find src -name '*.php' -print0 | xargs -0 -n 40 php -l`
- `php vendor/bin/phpunit tests/Unit/FeatureGeneratorTest.php tests/Unit/ContextManifestGeneratorTest.php tests/Integration/CLIContextCommandsTest.php tests/Integration/CLIFeatureSystemCommandTest.php tests/Integration/CLIInitAppCommandTest.php`
- `php vendor/bin/phpunit tests/Integration/CLIExtendedCommandsTest.php tests/Integration/CLILicensedCommandsTest.php` (remaining unrelated fixture assertions noted below)
- `./foundry compile graph --json`
- `./foundry inspect graph --json`
- `./foundry inspect pipeline --json`
- `./foundry verify graph --json`
- `./foundry verify pipeline --json`
- `./foundry verify contracts --json`
- `./foundry verify features --json`
- `./foundry verify context --feature=feature-system --json`
- `./foundry spec:validate --json`

## Decisions And Tradeoffs

- Kept framework modules under `Modules/` and did not move framework runtime source out of `src/*`.
- Kept `docs/` intact because it is important authored documentation and is consumed by foundryframework.org through a pinned git submodule checkout.
- Rebuilt public examples and demos to use canonical `Features/<Feature>/` paths once demo timing pressure was removed.
- Preserved generated outputs as generated projections from canonical `Features/<Feature>/` source rather than hand-authoring them.

## Reconstruction Notes

- Reconstruct the app feature root behavior by routing all feature source path construction through `FeatureNaming::directory()` and all PHP-safe test filenames through `FeatureNaming::codeSafe()`.
- Reconstruct the verifier behavior by scanning `Features/*` as app features, treating `app/features/` as a global hard violation, and treating only app context directories under `docs/features/<feature>/` as legacy context violations.
- Reconstruct smoke app layout by moving feature fixtures to PascalCase `Features/<Feature>/` directories, moving `action.php` to `src/Action.php`, and adding local context files for each smoke feature.

## Follow-Up Dependencies

- Keep historical execution specs and decision-ledger entries as historical records even when they mention obsolete paths.
- Decide later whether the root smoke app should keep both current fixtures or replace them with a smaller canonical fixture.
