# Feature Spec: mcp-server

## Purpose

Define the deterministic MCP surface for Foundry introspection, planning, validation, and guarded apply diagnostics.

## Goals

- Expose stable MCP tooling for canonical read operations.
- Expose deterministic plan-generation and plan-validation MCP tools that reuse existing Generate and Marketplace runtime logic.
- Expose a deterministic, guarded plan-apply MCP tool that reuses existing replay/apply runtime logic.
- Preserve CLI parity by delegating to existing read models and command surfaces.
- Keep MCP planning deterministic and side-effect free unless an explicit apply tool is invoked.

## Non-Goals

- No replacement of existing CLI behavior.
- No non-deterministic or hidden-state tool behavior.
- No implicit or silent mutation pathways.

## Constraints

- MCP tools must return deterministic JSON for identical inputs.
- MCP tools must map to existing CLI read commands or deterministic runtime services.
- Planning and validation tools must not mutate source files.
- Any mutation-capable tool must require explicit invocation and preserve preflight/guard checks.

## Expected Behavior

- `foundry mcp:serve` is available as the MCP entrypoint.
- MCP startup manifest includes canonical read tool list:
- `explain_target`
- `inspect_graph`
- `list_packs`
- `explain_pack`
- `doctor`
- `list_examples`
- MCP startup manifest includes planning tool list:
- `generate_plan`
- `validate_plan`
- `apply_plan`
- `generate_apply` (backward-compatible alias of `apply_plan`)
- MCP tool responses use canonical wrapper shape:
- `{"tool":"<name>","data":{...}}`
- MCP `explain_target` reuses canonical explain behavior.
- MCP `inspect_graph` reuses canonical graph inspection behavior.
- MCP `list_packs` reflects installed pack state deterministically.
- MCP `explain_pack` reuses canonical pack explain behavior.
- MCP `doctor` matches read-only doctor diagnostics behavior.
- MCP `list_examples` reflects canonical example catalog behavior.
- MCP `generate_plan` returns deterministic planning payloads including execution state, validation summary, entitlements, and pack requirements.
- MCP `validate_plan` validates persisted-plan ids and inline plan payloads without source mutation and reports deterministic `valid|blocked|stale|invalid` status.
- MCP `apply_plan` accepts explicit persisted `plan_id` input only, runs replay dry-run preflight before live mutation, and fail-closes with deterministic guard codes/execution-state mapping.
- MCP `generate_apply` delegates to the same implementation and response contract as `apply_plan`.

## Acceptance Criteria

- MCP server command is implemented and callable locally.
- All V1 tools return valid deterministic JSON.
- Tool outputs preserve parity with existing CLI read surfaces.
- Pack-aware data is visible through MCP read tools.
- Planning/validation MCP tools return deterministic payloads and do not mutate source files.
- Apply MCP tools run explicit preflight guards and never mutate source files when preflight fails or when `dry_run` is true.

## Assumptions

- Future MCP expansion will continue to use promoted execution specs.
- Mutation-capable MCP surfaces require explicit promoted specs and guard contracts.
