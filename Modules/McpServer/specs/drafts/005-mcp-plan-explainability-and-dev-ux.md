# Execution Spec: 005-mcp-plan-explainability-and-dev-ux

## Purpose

Add deterministic explainability and developer UX surfaces for MCP-generated plans so agents and developers can understand why a plan exists, why packs were selected, whether entitlements block execution, and what must happen before apply.

This spec does not change planning or apply semantics. It makes the already planned/applied workflow inspectable and easier to operate safely.

## Feature

`mcp-server`

## Reasoning Target

This spec must be implementable by GPT-5.3-Codex at Medium reasoning.

Reuse existing explain, plan, Generate, and Marketplace data. Do not create a parallel explanation model.

## Depends On

- `Modules/McpServer/specs/002-mcp-plan-generation-and-validation.md`
- `Modules/McpServer/specs/003-mcp-apply-layer-and-guard-enforcement.md`
- `Modules/McpServer/specs/004-mcp-marketplace-and-entitlement-integration.md`
- Existing explain contracts:
  - `src/Explain/*`
  - `src/CLI/Commands/PlanShowCommand.php`
  - `src/Generate/PlanRecordStore.php`
- Existing MCP wrapper and tool registry.

## Goals

1. Provide a deterministic MCP tool for plan explanation.
2. Provide a deterministic CLI explain path for persisted plans.
3. Explain why packs were selected.
4. Explain entitlement readiness and blockers.
5. Explain execution readiness.
6. Preserve transparent next actions for developer and agent workflows.
7. Keep output stable, sorted, and machine-readable.

## Non-Goals

- Do not generate new plans.
- Do not apply plans.
- Do not acquire entitlements.
- Do not open browsers or checkout flows.
- Do not infer private Marketplace account state beyond existing entitlement cache/runtime contracts.
- Do not add prose-only output without a JSON contract.
- Do not replace `plan:show`.

## Tool And CLI Surface

Register an MCP tool:

```text
explain_plan
```

Add or extend CLI support:

```bash
php bin/foundry explain plan <plan_id> --json
```

If the existing explain command architecture uses another command class or subject resolver, integrate with that architecture rather than adding an unrelated command path.

The MCP tool may delegate to the CLI/read bridge only if the output remains deterministic and follows the same normalized contract.

## `explain_plan` Input Contract

Accepted input:

```json
{
  "plan_id": "plan_abc123"
}
```

Required fields:

- `plan_id`: non-empty string.

Input validation failures must throw `FoundryError` with:

```text
MCP_INPUT_INVALID
```

Unknown or malformed persisted plans must return structured explain failures with deterministic codes. They must not trigger fresh planning.

## Output Contract

The MCP wrapper remains:

```json
{
  "tool": "explain_plan",
  "data": {}
}
```

The `data` object must use:

```json
{
  "plan_id": "plan_abc123",
  "status": "explainable",
  "intent": "add blog with authentication",
  "mode": "new",
  "execution_state": "blocked_missing_entitlement",
  "readiness": {
    "status": "blocked",
    "reasons": [
      {
        "code": "MISSING_ENTITLEMENT",
        "message": "Marketplace entitlement is missing.",
        "pack": "foundry/auth"
      }
    ],
    "next_actions": [
      {
        "type": "resolve_entitlement",
        "pack": "foundry/auth",
        "command": "php bin/foundry pack purchase foundry/auth --json"
      }
    ]
  },
  "packs": [
    {
      "name": "foundry/auth",
      "source": "marketplace",
      "version": "1.0.0",
      "distribution": "premium",
      "reason": "Requested authentication requires auth guards.",
      "entitlement": {
        "required": true,
        "status": "missing",
        "tier": "premium",
        "expires_at": null
      },
      "executable": false
    }
  ],
  "changes": [],
  "validation": {
    "status": "blocked",
    "errors": [],
    "warnings": []
  }
}
```

Required fields:

- `plan_id`
- `status`
- `intent`
- `mode`
- `execution_state`
- `readiness`
- `packs`
- `changes`
- `validation`

Allowed `status` values:

```text
explainable
missing
invalid
```

Allowed readiness `status` values:

```text
ready
blocked
stale
invalid
unknown
```

## Explanation Requirements

Plan explanation must include:

- persisted plan identity.
- original intent.
- mode.
- current execution state.
- plan readiness status.
- deterministic readiness reasons.
- deterministic next actions when known.
- pack selection reasons.
- pack source/distribution/version when known.
- entitlement state and blocker status.
- validation errors/warnings when available.
- ordered change summary derived from persisted plan data.

Pack selection reasons may come from existing Generate plan metadata. If the persisted plan lacks an explicit reason, use a deterministic fallback:

```text
Pack was requested explicitly.
```

or:

```text
Pack requirement was inferred by Generate.
```

Do not invent detailed explanations that are not supported by persisted plan data.

## CLI JSON Contract

`php bin/foundry explain plan <plan_id> --json` must return the same `data` object as `explain_plan`, without the outer MCP wrapper.

Example:

```json
{
  "plan_id": "plan_abc123",
  "status": "explainable",
  "execution_state": "executable",
  "readiness": {
    "status": "ready",
    "reasons": [],
    "next_actions": [
      {
        "type": "apply_plan",
        "tool": "apply_plan",
        "plan_id": "plan_abc123"
      }
    ]
  },
  "packs": [],
  "changes": [],
  "validation": {
    "status": "valid",
    "errors": [],
    "warnings": []
  }
}
```

Human output may be added, but JSON is the canonical contract.

## Developer UX Flow

The supported transparent flow is:

1. `generate_plan`
2. `validate_plan`
3. `explain_plan`
4. resolve entitlement or plan blockers outside MCP if needed
5. `apply_plan`

The explanation output must make the next safe action obvious through structured `readiness.next_actions`.

Allowed `next_actions.type` values:

```text
apply_plan
validate_plan
resolve_entitlement
inspect_marketplace
repair_plan
regenerate_plan
none
```

Rules:

- Ready executable plans should include `apply_plan`.
- Missing entitlement blockers should include `resolve_entitlement` and may include `inspect_marketplace`.
- Stale plans should include `regenerate_plan`.
- Invalid plans should include `repair_plan` or `regenerate_plan`.
- Unknown state should include `validate_plan`.
- Completed/applied plans should include `none`.

## Determinism Rules

- Sort packs by `name` ascending.
- Sort readiness reasons by `code`, then `pack`, then `message`.
- Sort next actions by `type`, then `pack`, then `command`.
- Sort change summaries by path/action when the underlying plan permits.
- Do not include timestamps except those already present in persisted plan metadata.
- Do not include raw license keys, raw tokens, or secrets.

## Backward Compatibility

- Preserve `plan:show` behavior.
- Preserve existing `explain_target` behavior.
- Do not remove any existing MCP tools.
- Do not rename Marketplace or Generate error codes.
- If plan records lack new explain metadata, explain them with deterministic fallbacks and warnings instead of rejecting otherwise valid historical records.

## Required Tests

Add or update tests covering:

- MCP manifest includes `explain_plan`.
- `explain_plan` rejects missing `plan_id`.
- `explain_plan` explains an executable plan with ready status and `apply_plan` next action.
- `explain_plan` explains missing entitlement with blocker reason and `resolve_entitlement` next action.
- `explain_plan` explains expired entitlement deterministically.
- `explain_plan` explains stale plan with `regenerate_plan` next action.
- unknown plan id returns deterministic missing/invalid output.
- `php bin/foundry explain plan <plan_id> --json` matches MCP `explain_plan` data.
- pack ordering, reason ordering, and next action ordering are stable.
- explanations never expose raw tokens or license keys.

Test locations should follow existing patterns:

- `tests/Integration/CLIMcpServeCommandTest.php`
- existing explain command integration/unit tests, or a new focused test class if no suitable class exists.
- `tests/Unit/MCPServerTest.php`

## Acceptance Criteria

- `explain_plan` is registered and callable through `php bin/foundry mcp:serve --tool=explain_plan --json`.
- `php bin/foundry explain plan <plan_id> --json` returns the same normalized explanation data.
- Plan explanations include pack reasoning, entitlement state, execution readiness, and next actions.
- Explain output is deterministic and secret-safe.
- Existing plan inspection and MCP read tools keep passing.
- No planning, apply, entitlement acquisition, or source mutation occurs in this spec.

## Required Verification

Run:

```bash
php bin/foundry spec:validate --json
php bin/foundry verify context --feature=mcp-server --json
php bin/foundry verify features --json
php bin/foundry verify contracts --json
php vendor/bin/phpunit --filter 'CLIMcpServeCommandTest|MCPServerTest|Explain'
php vendor/bin/phpunit
XDEBUG_MODE=coverage php vendor/bin/phpunit --coverage-clover build/coverage/clover.xml
php bin/foundry verify coverage --min=90 --clover=build/coverage/clover.xml --json
```

All commands must exit `0` before this spec is reported complete.
