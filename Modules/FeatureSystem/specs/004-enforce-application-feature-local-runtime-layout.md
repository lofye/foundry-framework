# Execution Spec: 004-enforce-application-feature-local-runtime-layout

## Purpose

Reserve `Features/<Feature>/` for downstream application/business features and enforce a hard feature-local runtime convention for application-owned code.

After framework modules move to `Modules/<Module>/`, application features must have a clear, deterministic, non-optional structure. Feature-owned application runtime code must live inside the feature directory rather than scattered across shared app-level source folders.

## Core Rule

Application feature-owned runtime code must be localized under:

```text
Features/<Feature>/src/
```

Application feature-owned tests must be localized under:

```text
Features/<Feature>/tests/
```

Application feature-owned specs and plans must be localized under:

```text
Features/<Feature>/specs/
Features/<Feature>/outcomes/
```

Application feature-owned extra documentation must be localized under:

```text
Features/<Feature>/docs/
```

Canonical feature context files live directly under the feature root:

```text
Features/<Feature>/<feature-slug>.md
Features/<Feature>/<feature-slug>.spec.md
Features/<Feature>/<feature-slug>.decisions.md
```

## Required Application Feature Layout

A generated or developer-created application feature must use:

```text
Features/<Feature>/
  <feature-slug>.md
  <feature-slug>.spec.md
  <feature-slug>.decisions.md
  src/
  tests/
  specs/
  plans/
  docs/
```

`src/` and `tests/` are mandatory for executable application features.

`specs/`, `plans/`, and `docs/` may be empty or omitted only if the feature has no files of that class and the verifier explicitly permits omission.

For non-executable planning-only features, `src/` and `tests/` may be omitted only if the feature manifest/context marks the feature as non-executable using an explicit deterministic field or convention already supported by Foundry. If no such convention exists, executable feature layout is the default.

## Hard Convention

Do not make runtime code location optional for application features.

Application feature-owned runtime code must not be created in shared app-level source directories except when Foundry creates deterministic compatibility projections/adapters.

Allowed projection examples may include generated routes, manifests, indexes, or autoload bridges if required by the framework.

Projection files are not the source of truth.

## Scaffold Requirements

Update app initialization/scaffolding so new applications do not receive framework module directories under `Features/`.

A newly initialized application should either:

- contain no `Features/` directory until the first application feature is created, or
- contain an empty `Features/` root with clear placeholder policy, if the existing scaffold convention requires it.

It must not contain:

```text
Features/StateStore
Features/FeatureSystem
Features/ContextPersistence
Features/GenerateEngine
```

or any other framework module governance directory.

## Validator Requirements

Application feature validation must enforce:

- feature root is under `Features/<Feature>/`
- feature-owned runtime code is under `Features/<Feature>/src/`
- feature-owned tests are under `Features/<Feature>/tests/`
- canonical context files live directly under `Features/<Feature>/`
- feature specs live under `Features/<Feature>/specs/`
- feature plans live under `Features/<Feature>/outcomes/`
- extra feature docs live under `Features/<Feature>/docs/`

The verifier must reject application feature-owned source files located in legacy/global app feature paths when they are attributable to a feature.

If attribution is ambiguous, emit deterministic guidance rather than guessing.

## App Runtime Integration

If the application runtime currently expects app code under shared directories, introduce deterministic integration support such as:

- autoload registration
- generated bridges
- route discovery
- command discovery
- manifest-based indexing

Only implement the minimal integration needed for existing app behavior and tests.

Do not redesign the application runtime in this spec.

## CLI Requirements

Existing feature verification commands should validate application feature layout separately from framework module layout.

Required command behavior:

```bash
foundry verify features --json
```

must validate application feature layout.

If framework repo tests use `php bin/foundry verify features --json`, it must remain deterministic and must not confuse `Modules/` with app `Features/`.

If a feature-specific command exists:

```bash
foundry verify feature <feature> --json
```

or:

```bash
foundry verify feature --feature=<feature> --json
```

it must resolve application features from `Features/`, not framework modules from `Modules/`.

## Tests Required

Add or update tests for:

- app scaffold does not include framework modules in `Features/`
- valid application feature layout passes
- executable feature without `src/` fails deterministically
- executable feature without `tests/` fails deterministically
- feature-owned source outside `Features/<Feature>/src/` fails when attributable
- canonical feature context files directly under feature root pass
- specs/plans/docs directories validate when present
- omitted specs/plans/docs directories are accepted only when empty/no files exist for that class
- deterministic JSON error output for each failure mode

## Required Verification Commands

All must exit `0`:

```bash
php bin/foundry compile graph --json
php bin/foundry verify graph --json
php bin/foundry verify pipeline --json
php bin/foundry verify contracts --json
php bin/foundry verify features --json
php bin/foundry verify context --json
php bin/foundry spec:validate --json
php vendor/bin/phpunit
XDEBUG_MODE=coverage php vendor/bin/phpunit --coverage-clover build/coverage/clover.xml
php bin/foundry verify coverage --min=90 --clover=build/coverage/clover.xml --json
```

## Acceptance Criteria

- `Features/` is reserved for application features.
- New apps are not scaffolded with framework module directories under `Features/`.
- Application feature runtime code location is mandatory, not optional.
- Application feature tests location is mandatory for executable features.
- Verifiers distinguish app features from framework modules.
- All required tests and coverage gates pass.

