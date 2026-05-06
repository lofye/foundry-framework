# Feature: state-store

## Purpose

- Record the current repository state for Foundry's deterministic local SQLite state-store foundation.

## Current State

- `SqliteStateStore` exposes deterministic path resolution, schema initialization, typed state read/write, and metadata inspection.
- Schema initialization is idempotent and creates `foundry_state_meta` plus `foundry_state_values` with version tracking.
- `verify state-store --json` reports stable ordered checks and fails non-zero when readiness checks fail.
- `inspect state-store --json` reports store metadata without dumping raw values and does not create a missing database.
- Workspace root isolation guarantees one state database per project root.
- `src/State/SqliteStateStore.php` exists and is wired into CLI command context.
- `php bin/foundry verify state-store --json` and `php bin/foundry inspect state-store --json` are available, deterministic, and tested.
- Framework and scaffold gitignore surfaces exclude `.foundry/state/`.
- Unit and integration tests cover root-awareness, typed round-trip behavior, deterministic ordering, and command surfaces.

## Open Questions

- Which existing persistence surfaces should migrate to the state-store first is not yet decided.
- Whether future schema versions should add optional metadata columns (for example deterministic provenance fields) remains open.

## Next Steps

- Incrementally migrate qualifying framework-owned persistence records to namespaced SQLite state entries through promoted execution specs.
- Preserve deterministic inspect/verify JSON contracts as future schema versions are introduced.
