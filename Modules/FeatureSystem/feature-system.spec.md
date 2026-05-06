# Feature Spec: feature-system

## Purpose

Define canonical framework-module governance boundaries under `Modules/` with deterministic compatibility for legacy `Features/` and `docs/features/` paths.

## Goals

- Provide deterministic `feature:list`, `feature:inspect`, `feature:map`, and `verify features` CLI surfaces.
- Treat `Modules/implementation.log` as the canonical implementation ledger path.
- Support canonical `Modules/*/specs/` and `Modules/*/plans/` in spec validation.
- Keep migration-compatible behavior for legacy `docs/features/*` inputs.

## Non-Goals

- Do not physically migrate all framework runtime/test code in one step.
- Do not remove legacy `docs/features/` compatibility in this step.

## Constraints

- Output ordering must remain deterministic and stable.
- Canonical and legacy duplicate definitions must emit deterministic diagnostics.
- Boundary enforcement defaults to enabled and disabled mode must emit visible warnings.

## Expected Behavior

- `feature:list` returns deterministic feature rows from canonical and legacy sources.
- `feature:inspect <feature>` returns context and directory mapping with deterministic dependency order.
- `feature:map` returns deterministic owned path maps.
- `verify features` reports boundary/duplication issues and enforcement status.
- `spec:validate` validates canonical `Modules/*/specs` and `Modules/*/plans` paths.
- Active-spec implementation logging uses `Modules/implementation.log` when canonical module workspace is present.

## Acceptance Criteria

- Canonical `Modules/` workspace is discoverable and preferred for framework modules.
- New feature-system CLI surfaces are available and deterministic.
- Canonical/legacy duplicate detection reports `FEATURE_DUPLICATE_CANONICAL_AND_LEGACY`.
- Spec validation supports canonical `Modules/*` specs and plans.
- Canonical implementation ledger path is recognized as `Modules/implementation.log`.

## Assumptions

- Additional migration specs will progressively move more feature-owned code into localized feature directories.

## Framework Modules vs Application Features

Foundry distinguishes between:

### Framework Modules
- Implemented under `src/*`
- Organized by technical layer (Context, CLI, State, Quality, etc.)
- May span multiple capabilities
- Not required to live under `Modules/*/src/`

### Application Features
- Located under `Features/<Feature>/`
- Own:
    - context files (`<feature>.md`, `.spec.md`, `.decisions.md`)
    - `specs/`
    - `plans/`
    - optional `src/` and `docs/`
- May contain fully localized runtime code

### Rule

Framework Modules are layer-organized.
Application Features are ownership-organized.

Feature directories DO NOT imply that all runtime code must live within them.
