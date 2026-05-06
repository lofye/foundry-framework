# Execution Spec: 001-sqlite-layer

## Purpose

Introduce a deterministic local SQLite-backed state store for Foundry runtime and tooling state.

The state store gives Foundry a small, explicit, repository-local persistence layer for data that must survive across CLI invocations without being encoded into generated artifacts, source files, or ad hoc JSON blobs.

This spec establishes only the SQLite storage foundation. It must not introduce higher-level product behavior beyond the minimum APIs, schema management, diagnostics, and tests needed for future features to store and retrieve local state safely.

## Background

Foundry increasingly needs durable local state for workflows such as generation history, plan records, implementation quality gate results, approvals, diagnostics, feature runtime metadata, and future extension/runtime features.

Some existing features may already persist local data using scattered files or JSON records. This spec must not migrate all existing persistence systems unless doing so is necessary to introduce the state-store foundation safely. Existing behavior must continue to pass.

The store must be deterministic, inspectable, and safe for both framework development and downstream generated apps.

## Canonical Location

The default SQLite database path must be:

```bash
.foundry/state/foundry.sqlite
```

Rules:

- The path is relative to the active workspace root, not process CWD.
- Parent directories must be created only when a command or service actually needs the state store.
- The database file must not be committed to source control.
- The implementation must ensure `.foundry/state/` and/or `.foundry/state/*.sqlite` are ignored by the framework/app gitignore surfaces where applicable.
- Tests must use temp project roots and must not read from or write to the real repository `.foundry/state` database.

## Goals

1. Add a root-aware SQLite state-store service.
2. Add deterministic schema initialization and migration support for the local state database.
3. Add a minimal key-value state API suitable for framework/runtime use.
4. Add deterministic CLI inspection and verification surfaces.
5. Make the store safe for future feature-specific state without tying this spec to any one future feature.
6. Preserve existing runtime, graph, pipeline, context, spec, and coverage gates.

## Non-Goals

This spec must not:

- Replace application business databases.
- Replace generated app database/migration systems.
- Introduce ORM abstractions.
- Add external database support.
- Add remote synchronization.
- Migrate every existing JSON/plan/history store to SQLite.
- Store secrets or credentials.
- Add nondeterministic timestamps to JSON output contracts.
- Require SQLite state for commands that do not need it.

## Terms

- **Workspace root**: The active Foundry project root provided by `Paths`.
- **State database**: The SQLite database at `.foundry/state/foundry.sqlite`.
- **State store**: The service layer that owns state database connection, schema initialization, reads, writes, and verification.
- **State namespace**: A stable string grouping records by feature or subsystem.
- **State key**: A stable string identifying a value inside a namespace.

## Required Architecture

### 1. Root-aware path resolution

Implement state database path resolution through existing path/root infrastructure.

Requirements:

- Do not resolve `.foundry/state/foundry.sqlite` against `getcwd()` directly.
- All production services must use the active `Paths` root.
- Tests must prove two temp project roots produce isolated state databases.
- CLI commands must report the path relative to the workspace root in stable JSON output.

### 2. SQLite connection service

Add a small SQLite connection/service layer under an appropriate namespace, for example:

```text
src/State/SqliteStateStore.php
src/State/SqliteStateConnection.php
src/State/StateStore.php
src/State/StateStoreException.php
```

Exact names may vary if they better match existing Foundry conventions.

Requirements:

- Use PHP PDO SQLite unless the repository already has a stronger SQLite abstraction.
- Fail clearly if the SQLite PDO driver is unavailable.
- Use explicit exceptions or `FoundryError` surfaces consistent with existing command/service patterns.
- Ensure the database directory is created before opening the database.
- Use deterministic pragmas where appropriate, but do not add environment-sensitive output.
- Avoid long-lived global/static mutable connections.
- Keep the state store injectable/testable.

### 3. Schema initialization

The state store must initialize its own schema deterministically.

Minimum required tables:

```sql
foundry_state_meta
foundry_state_values
```

Minimum `foundry_state_meta` purpose:

- Track schema version.
- Track initialization state.

Minimum `foundry_state_values` purpose:

- Store namespaced key-value records.

Required logical columns for `foundry_state_values`:

- `namespace`
- `key`
- `value`
- `value_type`

Optional columns are allowed only if they are deterministic and justified by implementation needs.

Rules:

- Do not store wall-clock timestamps unless the repository already has a deterministic clock abstraction and this spec explicitly uses it. Prefer no timestamps in this first layer.
- `(namespace, key)` must be unique.
- Schema creation must be idempotent.
- Running initialization repeatedly must not alter existing values.
- Schema version must be inspectable.

### 4. Minimal StateStore API

Add a minimal API that supports:

- checking readiness/availability
- initializing schema
- setting a value
- getting a value
- checking whether a key exists
- deleting a value
- listing namespaces and/or keys deterministically

Required behavior:

- Namespaces and keys must be non-empty stable strings.
- Invalid namespace/key input must fail deterministically.
- Supported values must include at least:
  - string
  - int
  - float
  - bool
  - null
  - JSON-serializable arrays/objects
- Stored values must round-trip with type preservation for supported types.
- Listing must be sorted deterministically by namespace and key.
- Writes should use transactions when more than one statement is required.

### 5. CLI surfaces

Add deterministic CLI surfaces for state-store diagnostics.

Required commands:

```bash
php bin/foundry verify state-store --json
php bin/foundry inspect state-store --json
```

If the existing command architecture strongly prefers a different naming shape, keep it consistent with existing Foundry conventions, but the final command names must be documented and tested.

#### `verify state-store --json`

Purpose: confirm the local state store can be resolved, initialized, opened, and verified.

Required JSON shape:

```json
{
  "status": "pass",
  "store": "sqlite",
  "path": ".foundry/state/foundry.sqlite",
  "schema_version": 1,
  "checks": [
    {
      "name": "path_resolved",
      "status": "pass"
    },
    {
      "name": "directory_ready",
      "status": "pass"
    },
    {
      "name": "sqlite_available",
      "status": "pass"
    },
    {
      "name": "schema_ready",
      "status": "pass"
    },
    {
      "name": "round_trip",
      "status": "pass"
    }
  ]
}
```

Failure output must be deterministic and include:

```json
{
  "status": "fail",
  "store": "sqlite",
  "path": ".foundry/state/foundry.sqlite",
  "schema_version": null,
  "checks": [
    {
      "name": "sqlite_available",
      "status": "fail",
      "message": "..."
    }
  ]
}
```

Rules:

- Check order must be stable.
- Exit code must be `0` on pass and non-zero on fail.
- Messages must not include absolute temp paths unless the repository already permits absolute diagnostic paths for this command family. Prefer relative paths in stable output.
- The round-trip check must not leave user-visible test keys behind. Use a transaction rollback or a reserved internal key that is deleted before success.

#### `inspect state-store --json`

Purpose: show state-store metadata without dumping arbitrary user state values.

Required JSON shape:

```json
{
  "store": "sqlite",
  "path": ".foundry/state/foundry.sqlite",
  "exists": true,
  "schema_version": 1,
  "namespaces": [
    {
      "namespace": "example",
      "keys": 2
    }
  ]
}
```

Rules:

- Do not include raw stored values by default.
- Namespace rows must be sorted by namespace.
- If the database does not exist, the command must return deterministic output with `exists: false` and must not create the database merely by inspecting unless existing Foundry inspection conventions require initialization.
- If schema is missing or invalid, return a clear deterministic status/diagnostic shape consistent with existing inspect commands.

### 6. Integration with existing quality/doctor surfaces

Update existing registries/catalogs so the new commands are visible wherever Foundry command surfaces are documented or verified.

At minimum, update relevant command catalogs/API-surface registries so these pass:

```bash
php bin/foundry verify cli-surface --json
php bin/foundry verify contracts --json
```

If `doctor` has an existing modular check system, add a state-store check only if it can be done without expanding scope. If added, it must be deterministic and covered by tests.

### 7. Gitignore/scaffold surfaces

Ensure the local SQLite state database is not accidentally committed.

Requirements:

- Update framework `.gitignore` if needed.
- Update app scaffold/stub gitignore surfaces if generated apps would otherwise track `.foundry/state`.
- Do not ignore all `.foundry/` content, because existing `.foundry` metadata may be intentional source-controlled project configuration.
- Add or update tests if scaffold output is contract-tested.

### 8. Documentation updates

Update only the documentation needed for the new state-store contract.

Required docs:

- Feature docs for the state-store feature in the new localized feature layout.
- Command catalog/docs if the repository has generated or authored command documentation.
- Any relevant README/AGENTS references only if they must mention the state-store command or final quality gate.

Do not rewrite unrelated documentation.

### 9. Implementation log

After implementation completes and all gates pass, append an implementation entry to the canonical implementation log according to the repository’s current convention.

Do not log completion before gates pass.

## Expected File Placement

Use the new localized feature layout for this spec and related feature docs.

Expected spec path:

```bash
Features/StateStore/specs/001-sqlite-layer.md
```

If the repository’s finalized localized layout uses a different exact feature directory name or casing convention, follow the existing implemented convention and keep the feature name stable everywhere.

Expected feature-owned docs should live under the same localized feature area, not under legacy `docs/features` or `docs/specs` paths.

## Testing Requirements

Add unit and integration tests for the state store and CLI surfaces.

Minimum required test coverage:

1. Path resolution
   - resolves against active workspace root
   - does not use process CWD
   - isolates multiple temp project roots

2. Schema initialization
   - creates database and schema idempotently
   - preserves existing values across repeated initialization
   - exposes schema version

3. Value API
   - set/get/delete/exists
   - deterministic listing
   - invalid namespace/key failures
   - round-trip supported scalar and JSON values

4. CLI verify
   - pass output shape
   - fail output shape for at least one controlled failure path
   - stable check ordering
   - correct exit codes

5. CLI inspect
   - database missing
   - database exists with valid schema
   - sorted namespace summaries
   - does not dump values by default

6. Registry/catalog integration
   - command appears in CLI/application command lists where required
   - command-surface verification remains passing

7. Gitignore/scaffold behavior
   - `.foundry/state` database is ignored where applicable
   - source-controlled `.foundry` configuration remains allowed

## Required Verification Commands

Implementation is not complete until all of these commands exit `0`:

```bash
php bin/foundry compile graph --json
php bin/foundry inspect graph --json
php bin/foundry inspect pipeline --json
php bin/foundry verify graph --json
php bin/foundry verify graph-integrity --json
php bin/foundry verify pipeline --json
php bin/foundry verify contracts --json
php bin/foundry verify cli-surface --json
php bin/foundry verify features --json
php bin/foundry verify context --json
php bin/foundry spec:validate --json
php bin/foundry verify state-store --json
php bin/foundry inspect state-store --json
php vendor/bin/phpunit
XDEBUG_MODE=coverage php vendor/bin/phpunit --coverage-clover build/coverage/clover.xml
php bin/foundry verify coverage --min=90 --clover=build/coverage/clover.xml --json
```

If repo-wide PHPStan/style/doctor checks are already failing before this spec, do not claim they are fixed. However, this spec must not introduce new PHPStan/style/doctor failures in touched files.

## Completion Criteria

This spec is complete only when:

- The SQLite state store exists and is root-aware.
- Schema initialization is idempotent and deterministic.
- Minimal typed key-value operations work and are tested.
- `verify state-store --json` and `inspect state-store --json` exist, are deterministic, and are tested.
- CLI surface/contract verification passes.
- The database path is ignored and not committed.
- No command that should be read-only creates or mutates state accidentally.
- PHPUnit exits `0`.
- Clover generation exits `0`.
- `verify coverage --min=90` exits `0`.
- Implementation log is updated after all gates pass.

## Guardrails for Codex

- Do not lower coverage threshold.
- Do not weaken PHPUnit warning/risky/deprecation settings.
- Do not hand-edit generated artifacts unless the repository convention explicitly requires it.
- Do not use process CWD for workspace-sensitive paths.
- Do not introduce timestamps/random IDs into stable JSON output.
- Do not dump stored state values from inspect output by default.
- Do not make this a general app database abstraction.
- Do not migrate unrelated persistence systems unless required for compatibility, and explain any such migration in the implementation summary.
