# Execution Spec: 003-mcp-apply-layer-and-guard-enforcement

## Purpose

Introduce guarded MCP plan application for previously generated and validated plans.

This spec adds the mutation boundary that spec `002-mcp-plan-generation-and-validation` intentionally does not provide. MCP apply must be explicit, replay-backed, preflighted, entitlement-aware, and fail-closed.

## Feature

`mcp-server`

## Reasoning Target

This spec must be implementable by GPT-5.3-Codex at Medium reasoning.

Keep the implementation narrow: reuse persisted plans, Generate replay/apply, existing validation, and existing entitlement resolution. Do not create a second mutation pipeline.

## Depends On

- `Modules/McpServer/specs/001-read-layer.md`
- `Modules/McpServer/specs/002-mcp-plan-generation-and-validation.md`
- Existing Generate replay/apply behavior:
  - `src/Generate/GenerateEngine.php`
  - `src/Generate/PlanRecordStore.php`
  - `src/CLI/Commands/PlanReplayCommand.php`
- Existing MCP primitives:
  - `src/MCP/MCPServer.php`
  - `src/MCP/ToolRegistry.php`
  - `src/MCP/ToolHandler.php`
- Existing Marketplace entitlement resolver:
  - `src/Marketplace/PackEntitlementResolver.php`

## Goals

1. Register a canonical MCP apply tool.
2. Require explicit plan id input before any mutation.
3. Run deterministic dry-run preflight before live replay/apply.
4. Revalidate plan integrity, freshness, dependencies, policy state, and entitlements immediately before mutation.
5. Block unsafe or unauthorized plans with structured deterministic errors.
6. Preserve compatibility for any existing MCP apply-like tool while moving the canonical contract to `apply_plan`.
7. Ensure no mutation occurs when preflight fails.

## Non-Goals

- Do not implement planning; spec `002` owns planning and validation.
- Do not implement purchase or entitlement acquisition.
- Do not bypass Generate replay/apply with direct file writes.
- Do not add interactive approval prompts inside MCP.
- Do not weaken git safety, policy checks, replay drift checks, or coverage/verification gates.
- Do not apply inline plans that have not been persisted through the existing plan record contract.

## Tool Surface

Register this canonical MCP tool:

```text
apply_plan
```

If an existing tool named `generate_apply` is present, keep it as a backward-compatible alias in this spec. Both names must call the same handler/service and return the same data shape except for the outer MCP `tool` value.

The canonical manifest should include `apply_plan`. If `generate_apply` remains, tests must assert it is an alias, not a separate implementation path.

## Input Contract

Accepted input:

```json
{
  "plan_id": "plan_abc123",
  "strict": true,
  "dry_run": false
}
```

Required fields:

- `plan_id`: non-empty string.

Optional fields:

- `strict`: boolean; default `true`.
- `dry_run`: boolean; default `false`.

Rules:

- `strict: true` must map to strict replay/freshness behavior.
- `dry_run: true` runs preflight and returns what would happen without applying.
- `dry_run: false` requires successful preflight before live apply.
- Inline plan payloads are rejected by this spec.

Input validation failures must throw `FoundryError` with:

```text
MCP_INPUT_INVALID
```

## Apply Behavior

The handler must:

1. Load the persisted plan by `plan_id` through `PlanRecordStore` or the existing replay command/service.
2. Run a dry-run preflight using the same path that live replay/apply will use.
3. Revalidate integrity metadata and persisted-plan shape.
4. Revalidate graph/input freshness in strict mode.
5. Revalidate policy checks when the persisted plan includes policy state.
6. Revalidate Marketplace entitlements through existing Generate/Marketplace services.
7. Revalidate pack availability and dependency conflicts.
8. Return a blocked response if any preflight check fails.
9. Apply through existing Generate replay/apply only after preflight succeeds and `dry_run` is false.
10. Return the live replay/apply result without hiding verification failures.

No MCP handler may write application files directly.

## Output Contract

Successful dry run:

```json
{
  "status": "preflight_passed",
  "plan_id": "plan_abc123",
  "dry_run": true,
  "execution_state": "executable",
  "preflight": {
    "status": "passed",
    "execution_state": "executable",
    "entitlements": {
      "status": "complete",
      "required": [],
      "granted": [],
      "missing": [],
      "expired": [],
      "unknown": []
    },
    "validation": {
      "status": "valid",
      "errors": [],
      "warnings": []
    }
  },
  "result": null,
  "error": null
}
```

Successful apply:

```json
{
  "status": "applied",
  "plan_id": "plan_abc123",
  "dry_run": false,
  "execution_state": "executable",
  "preflight": {
    "status": "passed",
    "execution_state": "executable",
    "entitlements": {}
  },
  "result": {},
  "error": null
}
```

Blocked apply:

```json
{
  "status": "blocked",
  "plan_id": "plan_abc123",
  "dry_run": false,
  "execution_state": "blocked_missing_entitlement",
  "preflight": {
    "status": "failed",
    "execution_state": "blocked_missing_entitlement",
    "entitlements": {}
  },
  "result": null,
  "error": {
    "code": "MISSING_ENTITLEMENT",
    "pack": "foundry/auth",
    "message": "Marketplace entitlement is missing.",
    "details": {}
  }
}
```

Required top-level fields:

- `status`
- `plan_id`
- `dry_run`
- `execution_state`
- `preflight`
- `result`
- `error`

## Blocking Codes

Apply must fail closed with deterministic codes for known blockers:

```text
PLAN_RECORD_NOT_FOUND
PLAN_RECORD_INVALID
PLAN_INTEGRITY_INVALID
PLAN_STALE
PLAN_CONFLICT
POLICY_VIOLATION
MISSING_ENTITLEMENT
EXPIRED_ENTITLEMENT
UNKNOWN_ENTITLEMENT
ENTITLEMENT_STATE_CHANGED
ENTITLEMENT_VALIDATION_FAILED
MARKETPLACE_PACK_NOT_AVAILABLE
DEPENDENCY_CONFLICT
VERIFY_FAILED
```

Existing lower-level codes may be preserved when already stable, but MCP output must map them to the `execution_state` contract from spec `002`.

## Guard Rules

Before any live mutation:

- persisted plan must exist.
- plan integrity hash must be valid when present.
- strict freshness checks must pass when `strict` is true.
- dependency conflicts must be absent.
- required Marketplace entitlements must be currently granted.
- entitlement-required packs must be available.
- Generate policy checks must pass or retain their existing explicit override semantics.
- verification preconditions required by Generate replay/apply must pass.

If entitlement state differs from the generated plan and now blocks execution, return:

```text
ENTITLEMENT_STATE_CHANGED
```

Do not mutate files after any guard failure.

## Approval Model

MCP apply is explicit because the client must call `apply_plan`.

Rules:

- `generate_plan` never auto-applies.
- `validate_plan` never auto-applies.
- `apply_plan` never runs without a `plan_id`.
- `apply_plan` never ignores preflight failure.
- `apply_plan` never acquires entitlements on behalf of the caller.
- `apply_plan` never performs hidden pack substitution.

## Backward Compatibility

If `generate_apply` already exists:

- Preserve it as an alias.
- Route it through the same implementation as `apply_plan`.
- Keep existing tests passing while adding new tests for the canonical name.
- Do not maintain separate entitlement, replay, or error mapping logic for the alias.

## Required Tests

Add or update tests covering:

- MCP manifest includes `apply_plan`.
- Existing `generate_apply`, if present, delegates to the same result contract.
- Missing `plan_id` returns `MCP_INPUT_INVALID`.
- Unknown plan id returns blocked/invalid output with deterministic code.
- `dry_run: true` runs preflight and does not mutate files.
- Successful apply uses existing replay/apply behavior and returns `status: "applied"`.
- Stale plan is blocked before mutation.
- Missing entitlement is blocked before mutation.
- Entitlement granted at planning but missing at apply returns `ENTITLEMENT_STATE_CHANGED` or the existing equivalent code with matching execution state.
- Dependency conflict is blocked before mutation.
- Verification failure is surfaced, not swallowed.
- No direct file writes occur from MCP handler code.

Test locations should follow existing patterns:

- `tests/Unit/MCPServerTest.php`
- `tests/Integration/CLIMcpServeCommandTest.php`
- Generate replay/apply tests only when existing lower-level behavior needs a contract adjustment.

## Acceptance Criteria

- `apply_plan` is registered and callable through `php bin/foundry mcp:serve --tool=apply_plan --json`.
- Apply is impossible without explicit `plan_id`.
- Apply always runs preflight before live mutation.
- Apply reuses existing Generate replay/apply behavior.
- Apply revalidates entitlement state immediately before mutation.
- Guard failures produce deterministic structured responses.
- No files are mutated when preflight fails or `dry_run` is true.
- Backward compatibility for existing `generate_apply` is preserved through a shared implementation path.

## Required Verification

Run:

```bash
php bin/foundry spec:validate --json
php bin/foundry verify context --feature=mcp-server --json
php bin/foundry verify features --json
php bin/foundry verify contracts --json
php vendor/bin/phpunit --filter 'MCPServerTest|CLIMcpServeCommandTest|GenerateEngineUndoTest|PlanCommandsTest'
php vendor/bin/phpunit
XDEBUG_MODE=coverage php vendor/bin/phpunit --coverage-clover build/coverage/clover.xml
php bin/foundry verify coverage --min=90 --clover=build/coverage/clover.xml --json
```

All commands must exit `0` before this spec is reported complete.
