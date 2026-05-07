# Feature: feature-system

## Purpose

- Record the implemented state of canonical feature workspace boundaries and migration-safe compatibility behavior.

## Current State

- `feature:list` returns deterministic feature rows from canonical and legacy sources.
- `feature:inspect <feature>` returns context and directory mapping with deterministic dependency order.
- `feature:map` returns deterministic owned path maps.
- `verify features` reports boundary and duplication issues with explicit enforcement status.
- `spec:validate` validates canonical `Modules/*/specs` and `Modules/*/plans` paths.
- Active-spec implementation logging uses `Modules/implementation.log` when canonical module workspace is present.
- Canonical `Modules/` workspace is discoverable and preferred for framework modules.
- New feature-system CLI surfaces are available and deterministic.
- Canonical and legacy duplicate detection reports `FEATURE_DUPLICATE_CANONICAL_AND_LEGACY`.
- Spec validation supports canonical `Modules/*` specs and plans.
- Canonical implementation ledger path is recognized as `Modules/implementation.log`.
- Application feature layout validation enforces executable `Features/<Feature>/src/` and `Features/<Feature>/tests/` ownership by default.
- Optional `specs/`, `plans/`, and `docs/` directories are validated when present and may be omitted when absent.
- Application feature legacy ownership leaks are reported deterministically (`app/features/<slug>` runtime artifacts and `docs/features/<slug>` context files).
- Framework module duplication under both `Modules/<Module>/` and `Features/<Module>/` is reported deterministically without misclassifying application features that exist only under `Features/`.
- Framework and scaffold documentation now consistently distinguish Framework Modules (`Modules/*`) from Application Features (`Features/*`).
- Agent guides and implementation skills now route framework execution-spec work to `Modules/*` and `Modules/implementation.log` while keeping app feature-local guidance under `Features/*`.
- Documentation and agent/skill contracts no longer describe framework module governance as canonical under `Features/*`.
- `spec:validate` now enforces reconstruction-note coverage for active module specs with deterministic violations for missing notes, invalid reconstruction headings, missing required sections, and section-order drift.
- Legacy module `plans/` files using `# Implementation Plan:` remain deterministic grandfathered records while new or updated module notes use strict reconstruction-note sections.
- Missing module reconstruction-note files for active FeatureSystem, Marketplace, and StateStore specs now exist under `Modules/<Module>/plans/` with canonical section layout.

## Open Questions

- Shared-glue versus feature-owned runtime boundary classification depth remains open.
- Full physical migration sequencing for feature-owned source/test files remains open.

## Next Steps

- Expand boundary-violation classification depth in follow-up execution specs.
- Continue incremental source/test localization through promoted execution specs.
- Evaluate follow-up specs to migrate grandfathered legacy module plan documents into strict reconstruction-note format.
