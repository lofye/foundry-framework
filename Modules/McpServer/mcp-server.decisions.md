### Decision: initialize canonical context for a planned but unimplemented mcp-server feature

Timestamp: 2026-05-01T12:05:00-04:00

**Context**

- The repository already contained a draft execution spec for `mcp-server`, but it had no canonical feature spec, state, or decisions files.
- Placeholder-only context caused `verify context --json` to report the feature as pass-but-not-consumable.
- The current repository still does not implement an MCP server runtime.

**Decision**

- Create canonical feature context for `mcp-server`.
- Describe the feature truthfully as planned work with draft execution guidance, not as implemented runtime behavior.

**Reasoning**

- Canonical context is required so future MCP work can proceed through normal Foundry feature workflows.
- Truthful state is better than placeholder text because it avoids confusing pass-with-warning outcomes.
- Recording the feature as unimplemented preserves contract honesty without weakening warning semantics globally.

**Alternatives Considered**

- Leave placeholder text in place.
- Change context warning semantics so placeholder-only state would still count as clean.
- Claim planned MCP behavior as if it were already implemented.

**Impact**

- `mcp-server` is now represented by consumable canonical feature context.
- Future MCP implementation can start from aligned docs instead of repairing context first.
- Global context verification can treat this feature as proceed-safe once the state remains aligned.

**Spec Reference**

- Purpose
- Expected Behavior
- Acceptance Criteria

### Decision: keep mcp-server in a draft-planning-only state until active implementation is promoted

Timestamp: 2026-05-01T12:12:00-04:00

**Context**

- `docs/mcp-server/specs/drafts/001-read-layer.md` exists and describes a future deterministic, read-only MCP server surface.
- The repository does not yet implement an `mcp:serve` command or a shipped MCP runtime.
- The feature state needs to say clearly that current work is limited to canonical context plus draft planning artifacts.

**Decision**

- Treat the current `mcp-server` feature state as documentation and planning only.
- Keep the feature unimplemented until an active execution spec is promoted and completed.

**Reasoning**

- This matches the actual repository state and avoids overstating partially planned work as shipped behavior.
- The current-state document can now mention the draft read-layer spec directly without implying runtime support.

**Alternatives Considered**

- Present the draft read-layer plan as if runtime implementation already existed.
- Remove mention of the draft spec from current state entirely.

**Impact**

- The current state may explicitly report that one draft execution spec exists at `docs/mcp-server/specs/drafts/001-read-layer.md`.
- The current state may explicitly report that no implemented `mcp:serve` command or runtime MCP server exists yet.
- Future MCP work remains blocked on promotion to an active execution spec instead of being inferred from draft planning files.

**Spec Reference**

- Expected Behavior
- Acceptance Criteria
- Assumptions

### Decision: implement a deterministic read-only MCP runtime that delegates to canonical CLI read surfaces

Timestamp: 2026-05-03T09:59:00-04:00

**Context**

- The active execution spec `001-read-layer` required a shipped MCP read surface, but the repository had no `mcp:serve` command and no MCP runtime classes.
- Existing Foundry read behavior already existed through deterministic CLI surfaces (`explain`, `inspect`, `pack`, `doctor`, `examples:list`).

**Decision**

- Implement `mcp:serve` with a deterministic MCP runtime composed of `MCPServer`, `ToolRegistry`, and stateless tool handlers.
- Implement MCP tools `explain_target`, `inspect_graph`, `list_packs`, `explain_pack`, `doctor`, and `list_examples`.
- Reuse existing CLI read behavior through a dedicated `CliReadBridge` instead of duplicating explain/inspect/doctor logic.

**Reasoning**

- CLI delegation preserves parity and avoids divergence between MCP and user-facing read behavior.
- A registry + handler model provides stable tool identities and deterministic registration order.
- Restricting V1 to read-only tools preserves safety and aligns with the execution spec.

**Alternatives Considered**

- Build a parallel MCP-specific explain/inspect implementation.
- Ship only planning docs and defer runtime implementation.
- Introduce write-capable tools in V1.

**Impact**

- Foundry now exposes a machine-consumable, deterministic MCP read layer through `mcp:serve`.
- MCP tool responses are stable wrappers around canonical read outputs.
- The feature state transitions from planning-only to implemented read-layer behavior.

**Spec Reference**

- Purpose
- Tool Surface
- CLI Parity Rules
- Safety Model
- Acceptance Criteria

### Decision: add deterministic MCP plan-generation and plan-validation contracts without introducing a parallel planning engine

Timestamp: 2026-05-27T16:45:00-04:00

**Context**

- Active execution spec `002-mcp-plan-generation-and-validation` requires MCP plan generation and plan validation contracts, including deterministic status and entitlement visibility.
- Existing MCP runtime already exposed `generate_plan`, but the payload contract was incomplete and no `validate_plan` tool existed.
- Existing Generate and Marketplace runtime already provided plan persistence, replay dry-run validation, entitlement evaluation, and deterministic pack requirement resolution.

**Decision**

- Register MCP tool `validate_plan` and route persisted-plan validation through strict replay dry-run behavior.
- Tighten MCP `generate_plan` output to include deterministic `plan_record_path`, `validation`, normalized entitlement summaries, and normalized pack-requirement ordering.
- Keep planning/validation logic delegated to existing Generate and Marketplace runtime contracts rather than adding MCP-local planning or entitlement engines.

**Reasoning**

- Reusing existing runtime paths preserves behavior parity and avoids drift between MCP and CLI workflows.
- Strict replay dry-run already models plan validity, drift detection, and entitlement revalidation without source mutation.
- Normalized payload shape and ordering improve determinism and machine-consumable reliability for MCP clients.

**Alternatives Considered**

- Implement a new MCP-only validation engine.
- Keep `generate_plan` minimal and omit validation summary fields.
- Validate persisted plans by raw record parsing only instead of replay dry-run.

**Impact**

- MCP now exposes deterministic planning and validation tools for non-mutating plan workflows.
- Persisted plan ids can be validated through MCP with explicit `valid|blocked|stale|invalid` status.
- Inline plan payloads can be validated through existing `GenerationPlan` and `PlanValidator` contracts.
- MCP tool manifests and integration tests now cover `generate_plan` and `validate_plan` as first-class planning surfaces.

**Spec Reference**

- Tool Surface
- `generate_plan` Input Contract
- `generate_plan` Output Contract
- `validate_plan` Input Contract
- `validate_plan` Output Contract
- Determinism Requirements

### Decision: add canonical MCP `apply_plan` with strict preflight guards and retain `generate_apply` as an alias

Timestamp: 2026-05-27T18:20:00-04:00

**Context**

- Active execution spec `003-mcp-apply-layer-and-guard-enforcement` requires a canonical MCP mutation boundary that applies persisted plans explicitly and fail-closes on guard violations.
- Existing MCP runtime had only `generate_apply`, and its contract did not include dry-run preflight status, deterministic guard mapping, or the full apply response shape required by the spec.
- Existing Generate replay/apply runtime already enforced strict drift checks, entitlement revalidation, policy/precondition checks, verification rollback, and deterministic error codes.

**Decision**

- Register canonical MCP tool `apply_plan` and keep `generate_apply` as a backward-compatible alias that routes to the same handler implementation.
- Require explicit `plan_id` input for MCP apply, reject inline plan payloads, and require boolean `strict`/`dry_run` flags when provided.
- Always run replay dry-run preflight before live apply and return deterministic guard payloads (`blocked`/`invalid`) when preflight or apply fails.
- Normalize replay/apply failures into deterministic MCP execution-state values and guard codes (`PLAN_STALE`, `PLAN_CONFLICT`, `POLICY_VIOLATION`, `VERIFY_FAILED`, and entitlement/pack blockers) without introducing a parallel mutation pipeline.

**Reasoning**

- Reusing `plan:replay` for both preflight and apply preserves parity with existing Generate contracts and prevents drift between MCP and CLI behavior.
- Shared handler logic between `apply_plan` and `generate_apply` avoids duplicated guard mapping paths and keeps backward compatibility deterministic.
- Explicit preflight and fail-closed output makes MCP mutation safe for automation clients while preserving existing rollback and verification semantics.

**Alternatives Considered**

- Keep `generate_apply` only and avoid introducing a canonical `apply_plan` name.
- Implement a new MCP-local apply executor instead of delegating to replay.
- Return raw replay errors without deterministic MCP mapping.

**Impact**

- MCP startup manifest now includes canonical `apply_plan` plus `generate_apply` alias wired to the same implementation path.
- MCP apply now enforces persisted-plan-only input, strict preflight-before-mutation behavior, and deterministic blocked/invalid responses.
- Integration and unit tests now cover canonical apply name, alias behavior, dry-run non-mutation behavior, stale-plan blocking, invalid plan-id handling, and surfaced verification failures.

**Spec Reference**

- Tool Surface
- Input Contract
- Apply Behavior
- Output Contract
- Guard Rules
- Backward Compatibility
- Required Tests
