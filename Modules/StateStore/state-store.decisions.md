### Decision: initialize canonical state-store context before implementation

Timestamp: 2026-05-04T11:54:00-04:00

**Context**

- The active execution spec `Features/StateStore/specs/001-sqlite-layer.md` existed, but canonical context files were missing before work started.
- `php bin/foundry verify context --feature=state-store --json` could not proceed cleanly without canonical feature spec/state/decisions files.

**Decision**

- Initialize the `state-store` feature context first, then implement runtime behavior only after context verification became proceed-safe.

**Reasoning**

- Foundry context policy requires canonical feature context to exist before non-trivial implementation proceeds.
- Repairing context first prevents spec/code drift and keeps downstream agents able to resume with deterministic anchors.

**Alternatives Considered**

- Implement runtime/state-store code first and repair context later.
- Keep placeholder context and proceed despite context warnings.

**Impact**

- The feature gained canonical context anchors under `Features/StateStore/`.
- Implementation work could proceed through the strict validation/verification workflow.

**Spec Reference**

- Purpose
- Expected File Placement

### Decision: implement a root-aware SQLite state-store foundation with deterministic CLI diagnostics

Timestamp: 2026-05-04T12:06:13-04:00

**Context**

- The `001-sqlite-layer` execution spec required a deterministic local SQLite state store at `.foundry/state/foundry.sqlite`.
- The framework lacked a dedicated state-store service and lacked `inspect state-store` / `verify state-store` command surfaces.

**Decision**

- Implement `SqliteStateStore` with root-aware path resolution via `Paths`, idempotent schema initialization, typed namespaced key/value operations, deterministic listings, and round-trip verification.
- Register new CLI commands `inspect state-store` and `verify state-store` through the existing application command surface and API-surface registry.
- Update framework and scaffold gitignore surfaces to ignore `/.foundry/state/`.

**Reasoning**

- A single SQLite-backed foundational service provides deterministic local persistence without introducing app-database coupling.
- Deterministic inspect/verify outputs align with Foundry CLI contract expectations and make state-store health machine-checkable.
- Root-aware path handling and temp-root tests ensure repository isolation and prevent accidental CWD-coupled writes.

**Alternatives Considered**

- Use ad hoc JSON files instead of SQLite for foundational state.
- Introduce state-store CLI behavior without a reusable service layer.
- Resolve state-store paths from process CWD rather than the active `Paths` root.

**Impact**

- Foundry now has a deterministic local SQLite state-store contract and command surface.
- Existing verification/catalog/scaffold contracts now include state-store behavior and ignore rules.
- Future feature migrations can reuse a shared namespaced state-store API instead of creating new local persistence formats.

**Spec Reference**

- Canonical Location
- Required Architecture
- CLI surfaces
- Gitignore/scaffold surfaces

### Decision: capture state-store command-surface registration details as current-state implementation evidence

Timestamp: 2026-05-04T12:12:00-04:00

**Context**

- The canonical spec defines state-store behavior and acceptance criteria, while the current state records concrete repository implementation details.
- Current state includes explicit mention that `inspect state-store` and `verify state-store` are registered in CLI/application API-surface catalogs and wired through command context.
- Context verification required an explicit decision entry explaining this spec-to-state detail level difference.

**Decision**

- Keep these command-surface registration details in `state-store.md` as implementation evidence and treat them as valid elaboration of the canonical acceptance criteria.

**Reasoning**

- The additional detail does not change feature intent; it documents where the implemented contract is wired so future work can safely evolve command surfaces.
- Recording this explicitly in decisions preserves continuity and removes ambiguity for future alignment checks.

**Alternatives Considered**

- Remove the registration details from current state and keep only higher-level summaries.
- Expand the feature spec with implementation-wiring specifics that are better suited for current state.

**Impact**

- Alignment checks can treat the current-state implementation details as intentional and decision-backed.
- Future contributors have a clear rationale for why command-surface wiring details are retained in state documentation.

**Spec Reference**

- Expected Behavior
- Acceptance Criteria
