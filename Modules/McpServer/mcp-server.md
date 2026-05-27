# Feature: mcp-server

## Purpose

- Record the current repository state for Foundry's deterministic MCP server surface across read, planning, validation, and guarded apply paths.

## Decision Summary

Refreshed Through Spec: `003-mcp-apply-layer-and-guard-enforcement`

- MCP V1 is a deterministic structured read interface over existing CLI/read models, not a divergent implementation path.
- The read-layer tool wrapper is stable as `{"tool":"<name>","data":{...}}`, with tools registered in deterministic order.
- Read tools reuse canonical explain, graph, pack, doctor, examples, and event inspection behavior.
- MCP planning and validation tools must reuse existing Generate/Marketplace runtime logic and must stay deterministic.
- MCP apply behavior must be explicit (`apply_plan`), persisted-plan-only, preflight-guarded, and fail-closed before live mutation.

## Current State

- `foundry mcp:serve` is implemented and callable locally.
- MCP startup manifest includes the canonical tool list:
- `explain_target`
- `inspect_graph`
- `list_packs`
- `explain_pack`
- `doctor`
- `list_examples`
- `generate_plan`
- `validate_plan`
- `apply_plan`
- `generate_apply` (alias to `apply_plan`)
- MCP tool responses use the canonical wrapper shape:
- `{"tool":"<name>","data":{...}}`
- `explain_target` reuses canonical explain behavior.
- `inspect_graph` reuses canonical graph inspection behavior.
- `list_packs` reflects installed pack state deterministically.
- `explain_pack` reuses canonical pack explain behavior.
- `doctor` matches read-only doctor diagnostics behavior.
- `list_examples` reflects canonical example catalog behavior.
- `generate_plan` returns deterministic planning payloads (`status`, `plan_id`, `plan_record_path`, `execution_state`, `validation`, `entitlements`, `pack_requirements`, `plan`, `error`).
- `validate_plan` validates by persisted `plan_id` and inline `plan` payloads, returns deterministic `valid|blocked|stale|invalid` status, and does not mutate source files.
- `validate_plan` reuses strict replay dry-run validation for persisted plans to surface drift as `stale` and entitlement/pack blockers as deterministic blocked states.
- `apply_plan` requires explicit persisted `plan_id`, runs deterministic preflight (`plan:replay --dry-run`) before live replay/apply, and blocks/invalidates with deterministic guard payloads.
- `apply_plan` returns a stable contract (`status`, `plan_id`, `dry_run`, `execution_state`, `preflight`, `result`, `error`) and surfaces verification failures instead of swallowing them.
- `generate_apply` remains available for backward compatibility and routes through the same handler/contract as `apply_plan`.
- MCP apply dry-run mode performs preflight only and does not mutate planned files.
- MCP apply stale/entitlement/policy/verification blockers are surfaced through deterministic error codes and execution-state mapping.
- All V1 tools return valid deterministic JSON.
- Tool outputs preserve parity with existing CLI read surfaces.
- Pack-aware data is visible through MCP read tools.

## Open Questions

- Whether future MCP transport support should include explicit TCP mode in addition to stdio is unresolved.

## Next Steps

- Expand MCP tool coverage only through deterministic CLI/runtime parity mappings.
- Preserve explicit guard-gating for any mutation-capable MCP behavior.
