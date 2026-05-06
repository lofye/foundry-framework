# Feature Spec: state-store

## Purpose

Define Foundry's deterministic, repository-local SQLite state-store contract for runtime and tooling state.

## Goals

- Persist small namespaced key/value state under a root-aware local SQLite database.
- Keep state-store initialization, inspect, and verify behavior deterministic.
- Provide minimal typed read/write/list/delete primitives for framework-owned state use cases.
- Expose stable CLI diagnostics through `inspect state-store` and `verify state-store`.

## Non-Goals

- Replacing application business databases or migration systems.
- Adding remote database/state backends.
- Storing secrets or credentials.
- Migrating every existing local persistence surface in one step.

## Constraints

- Canonical database path is `.foundry/state/foundry.sqlite` resolved from the active `Paths` root.
- State-store directories/files are created lazily only when state-store functionality is invoked.
- CLI JSON output must be deterministic and stable for identical inputs.
- Supported value types must preserve round-trip semantics (`string`, `int`, `float`, `bool`, `null`, arrays, objects).

## Expected Behavior

- `SqliteStateStore` exposes deterministic path resolution, schema initialization, typed state read/write, and metadata inspection.
- Schema initialization is idempotent and creates `foundry_state_meta` plus `foundry_state_values` with version tracking.
- `verify state-store --json` reports stable ordered checks and fails non-zero when readiness checks fail.
- `inspect state-store --json` reports store metadata without dumping raw values and does not create a missing database.
- Workspace root isolation guarantees one state database per project root.

## Acceptance Criteria

- `src/State/SqliteStateStore.php` exists and is wired into CLI command context.
- `php bin/foundry verify state-store --json` and `php bin/foundry inspect state-store --json` are available, deterministic, and tested.
- Framework and scaffold gitignore surfaces exclude `.foundry/state/`.
- Unit and integration tests cover root-awareness, typed round-trip behavior, deterministic ordering, and command surfaces.

## Assumptions

- SQLite PDO is available in supported local development/test environments.
- Future features may add additional namespaces/records without changing this foundational contract.
