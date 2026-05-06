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

## Open Questions

- Shared-glue versus feature-owned runtime boundary classification depth remains open.
- Full physical migration sequencing for feature-owned source/test files remains open.

## Next Steps

- Expand boundary-violation classification depth in follow-up execution specs.
- Continue incremental source/test localization through promoted execution specs.
