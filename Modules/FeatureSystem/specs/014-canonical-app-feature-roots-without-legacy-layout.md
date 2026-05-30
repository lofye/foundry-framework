# Execution Spec: 014-canonical-app-feature-roots-without-legacy-layout

## Purpose

Make `Features/<Feature>/` the only authored application feature root for new Foundry apps, remove obsolete `app/features/` source and `docs/features/<feature>/` application-context paths, and ensure every scaffolded app visibly contains the top-level `Features/`, `Modules/`, and `Packs/` directories from first run.

This spec intentionally makes a clean breaking alignment. No external applications depend on the legacy app layout yet, so Foundry should not carry migration commands, compatibility branches, or soft warnings for paths that should not exist.

## Background

Foundry's current app-facing guidance says application feature context, runtime code, and tests belong under localized feature roots such as:

```text
Features/Blog/
  blog.spec.md
  blog.md
  blog.decisions.md
  src/
  tests/
```

However, current app scaffolding and generation still leave important behavior tied to older paths:

```text
app/features/<feature>/
docs/features/<feature>/
```

That creates a confusing first-run experience. A developer building a Blog feature can see context files in `docs/features/blog/`, runtime manifests and actions in `app/features/blog/`, and feature-specific support classes in shared app folders such as `app/Support/`. That contradicts the modularity story and makes the feature boundary harder for humans and agents to inspect.

The new app story must be simple enough to demo and explain. `Blog` is only an illustrative feature name in this spec; the framework must implement a generic `<FeatureName>` contract and must not special-case Blog:

```text
Code for a Blog feature belongs at Features/Blog/src/.
```

## Core Principle

Application features are ownership-organized.

Framework internals may remain layer-organized under framework source directories, but downstream app feature work must be physically localized under the owning feature root.

For application features, authored source-of-truth files must live under:

```text
Features/<Feature>/
```

No authored application feature source or context may live under:

```text
app/features/
docs/features/<feature>/
```

## Goals

1. Scaffold `Features/`, `Modules/`, and `Packs/` in every new Foundry app, even when empty.
2. Remove `app/features/` from new app scaffolding, generated feature source, compiler source discovery, verifier expectations, examples, and app docs.
3. Remove `docs/features/<feature>/` from application context creation, context verification, execution-spec planning, examples, and app docs while preserving authored framework documentation already under `docs/`.
4. Treat `docs/` as an important public documentation source consumed by the foundryframework.org website through a pinned git submodule checkout; update docs where needed, but do not delete or move documentation wholesale as part of this refactor.
5. Make `Features/<Feature>/` the only application feature root.
6. Make `Features/<Feature>/src/` the only location for feature-owned application runtime code.
7. Make `Features/<Feature>/tests/` the only location for feature-owned application tests.
8. Ensure generated application feature code belongs under `Features/<Feature>/src/`; for example, Blog code belongs under `Features/Blog/src/`.
9. Fail hard when obsolete legacy app-layout source/context paths exist.
10. Keep `Modules/` present in app repositories as an empty top-level root, while documenting that app feature code does not belong there.
11. Keep `Packs/` present in app repositories as an empty top-level root for installed/local packs.
12. Preserve generated build/projection directories only as generated outputs, never as authored feature source.

## Non-Goals

- Do not add `foundry migrate features` or any migration command.
- Do not preserve compatibility with `app/features/`.
- Do not preserve compatibility with `docs/features/` for application context.
- Do not keep legacy read paths for new app feature source.
- Do not warn and continue when obsolete app-layout directories exist.
- Do not move framework module governance into app `Features/`.
- Do not require app feature code to live under `Modules/`.
- Do not redesign all generated projection/build output in this step unless required to remove `app/features/` as authored input.
- Do not hand-edit generated output as source of truth.

## Canonical App Layout

Every scaffolded Foundry app must contain these top-level directories:

```text
Features/
Modules/
Packs/
```

The directories may be empty, but they must exist in fresh scaffolds. Use deterministic placeholder files such as `.gitkeep` or short README files when needed so the directories survive source control.

Application features live under `Features/`:

```text
Features/
  Blog/
    blog.spec.md
    blog.md
    blog.decisions.md
    feature.yaml
    input.schema.json
    output.schema.json
    context.manifest.json
    prompts.md
    src/
    tests/
    specs/
    outcomes/
    docs/
```

`specs/`, `outcomes/`, and `docs/` may be omitted when empty if validators explicitly permit omission. `src/` and `tests/` are required for executable application features.

`Modules/` exists in app repositories as a visible reserved root. It must not be used for app feature code. It may remain empty unless a future app-level module concept is explicitly specified.

`Packs/` exists in app repositories as a visible reserved root for installed or local packs. Pack files remain pack-owned and must not be edited as app feature source.

## Illustrative Feature Placement Contract

`Blog` is an example feature name only. This section illustrates the generic contract for any `<FeatureName>` and must not be implemented as Blog-specific framework behavior.

For any application feature, feature-owned runtime code belongs under:

```text
Features/<Feature>/src/
```

Illustrative Blog examples:

```text
Features/Blog/src/BlogService.php
Features/Blog/src/BlogStorage.php
Features/Blog/src/Action.php
Features/Blog/src/AdminPostController.php
Features/Blog/src/RssFeedRenderer.php
```

For any application feature, feature-owned tests belong under:

```text
Features/<Feature>/tests/
```

Illustrative Blog examples:

```text
Features/Blog/tests/BlogServiceTest.php
Features/Blog/tests/BlogStorageTest.php
Features/Blog/tests/BlogFeatureTest.php
```

Feature context belongs directly under the feature root:

```text
Features/<Feature>/<feature>.spec.md
Features/<Feature>/<feature>.md
Features/<Feature>/<feature>.decisions.md
```

For example:

```text
Features/Blog/blog.spec.md
Features/Blog/blog.md
Features/Blog/blog.decisions.md
```

Feature execution specs and reconstruction notes belong under:

```text
Features/<Feature>/specs/
Features/<Feature>/outcomes/
```

For example, the following locations are invalid for Blog feature-owned code or context:

```text
app/features/blog/
docs/features/blog/
app/Support/BlogService.php
app/Support/BlogStorage.php
```

Shared app directories may contain only genuinely shared cross-feature glue. They must not contain feature-specific services, storage, policy logic, validators, handlers, renderers, or workflows. For example, they must not contain Blog-specific services such as `BlogService` or `BlogStorage`.

## Source-Of-Truth Rules

Application feature source of truth:

```text
Features/<Feature>/
```

Generated/build output may exist under deterministic generated-output roots such as:

```text
app/.foundry/build/
app/generated/
docs/generated/
docs/inspect-ui/
```

Generated/build output is not authored feature source. It must be regenerated from `Features/<Feature>/` inputs.

No source-of-truth rule may identify `app/features/` or `docs/features/` as an application feature source path after this spec is implemented.

## Scaffold Requirements

Fresh app creation must produce the top-level reserved roots:

```text
Features/
Modules/
Packs/
```

Fresh app creation must not produce app feature source or app feature context at:

```text
app/features/
docs/features/<feature>/
```

Scaffolded `AGENTS.md`, `README.md`, `.gitignore`, Composer scripts, examples, starter docs, generated docs text, and first-run instructions must consistently describe `Features/<Feature>/` as the application feature source root.

App docs must explicitly include this sentence or equivalent deterministic wording as an example of the generic feature placement rule:

```text
Code for a Blog feature belongs at Features/Blog/src/.
```

## Context Command Requirements

Application context commands must use `Features/<Feature>/` paths only.

For example:

```bash
foundry context init blog --json
```

must create or report files at:

```text
Features/Blog/blog.spec.md
Features/Blog/blog.md
Features/Blog/blog.decisions.md
```

It must not create or read application context from:

```text
docs/features/blog/blog.spec.md
docs/features/blog/blog.md
docs/features/blog/blog.decisions.md
```

Context inspection, context doctor, context repair, context alignment, execution-spec planning, execution-spec promotion, implementation entry points, and implementation-log helpers must resolve app feature context/spec paths from `Features/<Feature>/` for application features.

## Feature Generation Requirements

Generated application feature source must be written under `Features/<Feature>/`.

For any generated application feature, generation must target paths shaped like:

```text
Features/<Feature>/feature.yaml
Features/<Feature>/input.schema.json
Features/<Feature>/output.schema.json
Features/<Feature>/context.manifest.json
Features/<Feature>/prompts.md
Features/<Feature>/src/Action.php
Features/<Feature>/tests/
```

Illustrative Blog paths:

```text
Features/Blog/feature.yaml
Features/Blog/input.schema.json
Features/Blog/output.schema.json
Features/Blog/context.manifest.json
Features/Blog/prompts.md
Features/Blog/src/Action.php
Features/Blog/tests/
```

Feature generation must not create:

```text
app/features/blog/feature.yaml
app/features/blog/action.php
app/features/blog/input.schema.json
app/features/blog/output.schema.json
app/features/blog/tests/
```

Predicted files, dry-run output, approval plans, generated workflow plans, AI generation appliers, and docs examples must report the canonical `Features/<Feature>/` paths.

## Compiler And Runtime Requirements

The graph compiler, feature loader, feature verifier, runtime factory, explain tools, inspect tools, and graph documentation must treat `Features/<Feature>/feature.yaml` and sibling files as authored feature source.

The compiler may emit generated projections or indexes if runtime execution needs them, but those outputs must be derived from `Features/<Feature>/` and must not require an `app/features/` authored input directory.

If implementation needs an autoload or runtime bridge for classes under `Features/<Feature>/src/`, add the smallest deterministic bridge needed for generated apps and tests.

The bridge must preserve the ownership rule: `Features/<Feature>/src/` remains authored source; generated bridge files are projections only.

## Verification Requirements

`foundry verify features --json` must fail if obsolete `app/features/` source exists or if legacy application context exists under `docs/features/<feature>/`.

```text
app/features/
docs/features/<feature>/
```

Required deterministic violation codes:

```text
APP_FEATURES_LEGACY_DIRECTORY_PRESENT
DOCS_FEATURES_LEGACY_CONTEXT_PRESENT
```

Required message intent:

```text
Legacy app/features directory is not part of the current Foundry app layout. App feature source belongs under Features/<Feature>/.
```

```text
Legacy docs/features application context is not part of the current Foundry app layout. App feature context belongs under Features/<Feature>/.
```

`foundry verify features --json` must also enforce that executable application features have:

```text
Features/<Feature>/src/
Features/<Feature>/tests/
```

Feature-specific support code outside `Features/<Feature>/src/` must fail when attribution is deterministic. For example, if the feature is Blog, the verifier should report Blog-owned code in `app/Support/BlogService.php` or `app/Support/BlogStorage.php` as invalid if such attribution logic is implemented in this spec. If full attribution depth cannot be implemented safely in this step, the spec must still ensure docs and generation never place feature-specific code there, and record attribution-depth follow-up clearly.

## Documentation Requirements

Update framework and app-facing documentation so the following are consistent:

- `Features/<Feature>/` is the only app feature root.
- `Features/<Feature>/src/` is the only authored app feature runtime-code root.
- `Features/<Feature>/tests/` is the only authored app feature test root.
- `app/features/` is obsolete and must not appear as a new-app source path.
- `docs/features/<feature>/` is obsolete for app feature context and must not appear as a new-app context path.
- `Modules/` exists in generated apps but is not where user feature code goes.
- `Packs/` exists in generated apps for installed/local packs.
- Code for a feature belongs at `Features/<Feature>/src/`; for example, code for a Blog feature belongs at `Features/Blog/src/`.
- Feature-specific services and storage classes belong at `Features/<Feature>/src/`; for example, `BlogService` and `BlogStorage` belong at `Features/Blog/src/`, not `app/Support/`.

Update at least these surfaces when applicable:

- `APP-AGENTS.md`
- `APP-README.md`
- framework `README.md`
- scaffold/init-app tests and fixtures
- demo docs, especially docs that use Blog as an example feature
- command catalog examples
- generated graph docs text
- stubs that mention feature paths
- any test fixtures that model app feature source paths

## Test Requirements

Add or update meaningful tests that would fail if the old layout remains active.

Required coverage includes:

1. Fresh app scaffolding creates `Features/`, `Modules/`, and `Packs/`.
2. Fresh app scaffolding does not create `app/features/`.
3. Fresh app scaffolding does not create `docs/features/<feature>/`.
4. `context init <feature> --json` writes `Features/<Feature>/<feature>.spec.md`, `Features/<Feature>/<feature>.md`, and `Features/<Feature>/<feature>.decisions.md`; use Blog as one concrete test fixture if useful.
5. `context init <feature> --json` does not write `docs/features/<feature>/*`.
6. Feature generation writes under `Features/<Feature>/`.
7. Feature generation writes runtime code under `Features/<Feature>/src/`.
8. Feature generation writes tests under `Features/<Feature>/tests/`.
9. Feature generation does not create `app/features/<feature>/`.
10. Compiler/inspect/verify commands consume feature manifests from `Features/<Feature>/`.
11. `verify features --json` fails with `APP_FEATURES_LEGACY_DIRECTORY_PRESENT` when `app/features/` exists.
12. `verify features --json` fails with `DOCS_FEATURES_LEGACY_CONTEXT_PRESENT` when legacy app context exists under `docs/features/<feature>/`.
13. `verify features --json` fails when executable `Features/<Feature>/src/` is missing.
14. `verify features --json` fails when executable `Features/<Feature>/tests/` is missing.
15. App docs/scaffold tests assert the generic feature placement rule and may include the Blog sentence as an example.

Do not add tautological tests. Tests must assert observable behavior and fail if the implementation still writes or reads the obsolete app-layout paths.

## Required Verification Commands

Focused iteration should use relevant command/test subsets first.

Before reporting implementation completion, all must pass:

```bash
./foundry compile graph --json
./foundry inspect graph --json
./foundry inspect pipeline --json
./foundry verify graph --json
./foundry verify pipeline --json
./foundry verify contracts --json
./foundry verify features --json
./foundry verify context --feature=feature-system --json
./foundry spec:validate --json
php vendor/bin/phpunit
bin/phpunit-coverage --coverage-clover build/coverage/clover.xml
./foundry verify coverage --min=90 --clover=build/coverage/clover.xml --json
```

## Acceptance Criteria

- New Foundry apps always include top-level `Features/`, `Modules/`, and `Packs/` roots.
- New Foundry apps do not include `app/features/`.
- New Foundry apps do not include app feature context under `docs/features/<feature>/`.
- Application feature context is created under `Features/<Feature>/` only.
- Application feature manifests/schemas/prompts/context manifests are authored under `Features/<Feature>/` only.
- Application feature runtime code is authored under `Features/<Feature>/src/` only.
- Application feature tests are authored under `Features/<Feature>/tests/` only.
- Generic feature-owned code placement is documented as `Features/<Feature>/src/`.
- Blog is documented only as an example: `BlogService` and `BlogStorage` are feature-owned code under `Features/Blog/src/`, not `app/Support/`.
- Generation, planning, dry-run, explain, inspect, and verification output no longer present `app/features/` or `docs/features/<feature>/` as app source/context paths.
- `verify features --json` fails hard when obsolete `app/features/` source or `docs/features/<feature>/` app context exists.
- Compiler/runtime behavior works from `Features/<Feature>/` source files without requiring `app/features/`.
- Documentation, stubs, fixtures, and examples are aligned with the no-legacy app feature layout.
- Required tests and verification commands pass.

## Implementation Notes

Likely implementation areas include, but are not limited to:

- `src/Support/Paths.php`
- `src/Generation/FeatureGenerator.php`
- `src/Generation/ContextManifestGenerator.php`
- `src/Testing/FeatureTestGenerator.php`
- `src/Pro/Generation/GeneratedFeatureApplier.php`
- `src/Generate/FeaturePlanBuilder.php`
- `src/Feature/FeatureLoader.php`
- graph compiler and collectors that scan feature manifests
- context resolver/init/doctor/repair/planning services
- feature workspace verifier
- app scaffold command and templates
- docs and examples that mention `app/features` or `docs/features`
- tests and fixtures that create old app feature roots

Implement the smallest cohesive change that satisfies the contract. Do not leave compatibility read/write branches for the obsolete app feature layout.
