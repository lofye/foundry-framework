# Execution Spec: 003-separate-framework-modules-from-application-features

## Purpose

Separate Foundry framework governance workspaces from downstream application feature workspaces.

Foundry currently uses `Features/<Name>/` for framework-owned capability context, specs, decisions, plans, and implementation logs. That creates ambiguity because downstream applications should also use `Features/<Feature>/` for application/business features. A newly initialized app should not appear to already contain framework features such as `StateStore`, `FeatureSystem`, or `ContextPersistence`.

This spec introduces `Modules/<Module>/` as the canonical root for Foundry framework-owned capability governance, while reserving `Features/<Feature>/` for application-level feature ownership.

## Core Rule

- `Modules/<Module>/` is for Foundry framework modules.
- `Features/<Feature>/` is for application/business features.
- Framework runtime source may remain layer-organized under `src/*` unless a future spec explicitly localizes it.
- Application feature-owned runtime code must live under that feature's own `Features/<Feature>/src/` tree after application feature layout enforcement is implemented.

## Definitions

### Framework Module

A Framework Module is a Foundry-owned capability or subsystem, such as:

- `FeatureSystem`
- `StateStore`
- `ContextPersistence`
- `GenerateEngine`
- `ExtensionSystem`
- `McpServer`

Framework Modules are governed by module-local context/spec/planning files, but their runtime implementation may span shared framework namespaces such as `src/CLI`, `src/Context`, `src/State`, `src/Quality`, and `src/Support`.

### Application Feature

An Application Feature is a downstream app capability owned by the generated application, such as:

- `Blog`
- `Checkout`
- `PodcastEpisodes`
- `AdminDashboard`

Application Features are feature-owned units and must not be confused with Foundry framework modules.

## Required Layout

Framework module governance files must live under:

```text
Modules/<Module>/
  <module-slug>.md
  <module-slug>.spec.md
  <module-slug>.decisions.md
  specs/
  plans/
```

The framework-global implementation log must move from:

```text
Features/implementation.log
```

to:

```text
Modules/implementation.log
```

`Features/` must no longer contain framework module directories after migration.

## Migration Requirements

Move existing framework-owned directories from `Features/<Name>/` to `Modules/<Name>/` without changing their meaning.

At minimum, migrate any existing framework module directories such as:

```text
Features/FeatureSystem      -> Modules/FeatureSystem
Features/StateStore         -> Modules/StateStore
```

Also migrate any other framework-owned `Features/<Name>/` directories present in the repository at implementation time.

The migration must preserve:

- filenames
- spec IDs
- spec headings unless explicitly required by an existing validator
- context content
- decisions ledger content
- plans
- implementation history

No spec IDs may be renumbered.
No decision history may be rewritten or compacted.
No unrelated cleanup may be performed.

## Resolver And Validator Updates

Update framework services so framework module context/spec/planning discovery uses `Modules/<Module>/`.

Required updates include, but are not limited to:

- context file resolution
- context inspection
- context planning
- context repair
- context doctor
- spec validation
- feature/module verification commands
- graph/pipeline/context verifiers that inspect localized governance files
- implementation-log discovery

Any legacy compatibility must be explicit and deterministic.

## Legacy Compatibility Rule

During this migration, the framework may detect legacy `Features/<Module>/` directories only to emit deterministic migration guidance or validation failures.

It must not silently treat framework-owned `Features/<Module>/` as canonical.

If both exist for the same framework module, validation must fail with a deterministic duplicate-location error.

## CLI Surface Requirements

Existing commands must remain deterministic and backward-compatible where their names refer to the conceptual operation rather than the storage root.

For this spec, do not rename public commands unless strictly required.

Examples:

```bash
php bin/foundry verify context --json
php bin/foundry verify context --feature=state-store --json
php bin/foundry spec:validate --json
```

If `--feature` currently refers to framework module context, preserve the option for compatibility but internally resolve framework modules from `Modules/`.

If new `--module` aliases are added, they must be documented and tested, but not required by this spec.

## Deterministic Error Requirements

Validation failures for misplaced framework module directories must include deterministic JSON fields such as:

```json
{
  "status": "fail",
  "code": "framework_module_in_features_root",
  "path": "Features/StateStore",
  "expected_path": "Modules/StateStore"
}
```

Exact shape may follow existing Foundry error conventions, but it must be stable and covered by tests.

## Tests Required

Add or update tests for:

- module context resolution from `Modules/<Module>/`
- duplicate detection when both `Modules/<Module>/` and legacy `Features/<Module>/` exist
- validation failure when framework modules remain under `Features/`
- implementation-log discovery from `Modules/implementation.log`
- `spec:validate --json` with module-local specs
- `verify context --feature=<module>` or equivalent existing module lookup
- graph/pipeline/context verification after migration
- no framework module directories remain under root `Features/`

## Required Verification Commands

All must exit `0`:

```bash
php bin/foundry compile graph --json
php bin/foundry inspect graph --json
php bin/foundry inspect pipeline --json
php bin/foundry verify graph --json
php bin/foundry verify graph-integrity --json
php bin/foundry verify pipeline --json
php bin/foundry verify contracts --json
php bin/foundry verify features --json
php bin/foundry verify context --json
php bin/foundry spec:validate --json
php vendor/bin/phpunit
XDEBUG_MODE=coverage php vendor/bin/phpunit --coverage-clover build/coverage/clover.xml
php bin/foundry verify coverage --min=90 --clover=build/coverage/clover.xml --json
```

If PHPStan/style/doctor are already repo-wide failing for pre-existing unrelated reasons, report them separately and do not claim repo-clean completion.

## Acceptance Criteria

- Framework-owned governance directories are under `Modules/`, not `Features/`.
- `Modules/implementation.log` is canonical.
- No framework module directory remains under root `Features/`.
- Context/spec/planning discovery works from `Modules/`.
- Legacy framework module locations under `Features/` fail deterministically.
- Existing runtime source under `src/*` is not moved by this spec.
- All required runtime, PHPUnit, Clover, and coverage verifier gates pass.

