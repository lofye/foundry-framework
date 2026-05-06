# Execution Spec: 005-marketplace-mcp-and-generate-entitlement-integration

## Purpose

Integrate Marketplace entitlement enforcement into MCP planning/apply flows and Generate planning/execution flows.

This spec depends on:

- Marketplace backend
- Marketplace auth
- Marketplace entitlement resolver
- Marketplace purchase/refresh behavior
- MCP server read layer
- Generate system planning/runtime

---

## Core Principle

No entitlement-dependent mutation or Marketplace pack application may occur without transparent entitlement validation.

LLMs may propose plans that require missing entitlements, but those plans must be clearly marked as blocked/non-executable until the entitlement issue is resolved.

---

## Scope

This spec is framework-side.

It defines how Marketplace entitlement state is surfaced to:

- MCP planning
- MCP apply
- Generate planning
- Generate execution
- plan records
- explain output where available

It does not implement:

- website account UI
- website payments
- production entitlement database
- DRM
- hidden code restriction

---

## Goals

1. Ensure MCP planning includes entitlement state.
2. Ensure MCP apply revalidates entitlement state.
3. Ensure Generate planning includes entitlement state.
4. Ensure Generate execution blocks missing entitlement operations.
5. Ensure all entitlement decisions use `PackEntitlementResolver`.
6. Ensure entitlement failures are structured and deterministic.
7. Ensure no duplicate entitlement logic is introduced.

---

## Non-Goals

- No silent premium-to-free substitution.
- No hidden installs.
- No implicit purchase.
- No entitlement escalation.
- No local execution blocking for already-installed user code unless the operation requires Marketplace service access.

---

## Required Shared Runtime

All entitlement decisions must route through:

```text
PackEntitlementResolver
```

or the exact equivalent introduced by Marketplace 003.

Forbidden:

- MCP-specific entitlement rules.
- Generate-specific entitlement rules.
- repeated “if premium then block” logic outside the resolver.
- direct entitlement cache parsing from MCP/Generate.

---

## MCP Planning Integration

MCP planning must include entitlement state for all Marketplace-dependent plan steps.

Step example:

```json
{
  "type": "install_pack",
  "pack": "foundry/auth",
  "source": "marketplace",
  "version": "1.0.0",
  "distribution": "premium",
  "entitlement": {
    "required": true,
    "status": "missing",
    "tier": "premium"
  }
}
```

Plan-level summary:

```json
{
  "entitlements": {
    "status": "incomplete",
    "required": ["foundry/auth"],
    "granted": [],
    "missing": ["foundry/auth"],
    "expired": []
  }
}
```

---

## MCP Execution State

Plans must expose execution state.

Allowed values:

```text
executable
blocked_missing_entitlement
blocked_expired_entitlement
blocked_conflict
stale
invalid
```

If any required entitlement is missing:

```json
{
  "execution_state": "blocked_missing_entitlement"
}
```

If any required entitlement is expired:

```json
{
  "execution_state": "blocked_expired_entitlement"
}
```

---

## MCP Apply Integration

Before applying a plan, MCP apply must:

1. reload plan.
2. validate plan integrity.
3. validate plan freshness.
4. re-run entitlement resolution.
5. block mutation if entitlement state is not currently valid.
6. return deterministic structured error.

Missing entitlement error:

```json
{
  "status": "blocked",
  "code": "MISSING_ENTITLEMENT",
  "pack": "foundry/auth"
}
```

Expired entitlement error:

```json
{
  "status": "blocked",
  "code": "EXPIRED_ENTITLEMENT",
  "pack": "foundry/auth"
}
```

---

## Generate Planning Integration

Generate planning must:

- call `PackEntitlementResolver` for Marketplace pack requirements.
- include entitlement state in generated plans/plan records.
- mark plan as blocked when required entitlements are missing.
- avoid silent substitution unless explicitly planned and surfaced.

---

## Generate Execution Integration

Generate execution must:

- revalidate entitlement state before applying Marketplace-dependent operations.
- fail closed when entitlement is missing/expired/unknown.
- return deterministic structured failures.
- never install/download entitlement-required Marketplace packs without granted entitlement.

---

## Marketplace Pack Distinction

Plan payloads must distinguish:

- local installed pack
- free Marketplace pack
- licensed Marketplace pack
- premium Marketplace pack
- unknown Marketplace pack

Example:

```json
{
  "pack": "foundry/blog",
  "source": "marketplace",
  "distribution": "free",
  "entitlement": {
    "required": false,
    "status": "not_required",
    "tier": "free"
  }
}
```

---

## Entitlement State Changes

If a plan was generated when entitlement was granted but entitlement is missing/expired at apply time:

- apply must block.
- result must say entitlement state changed or is no longer valid.
- mutation must not occur.

Suggested error:

```json
{
  "status": "blocked",
  "code": "ENTITLEMENT_STATE_CHANGED",
  "pack": "foundry/auth"
}
```

---

## Explain Integration

If plan explain output exists, include entitlement status.

Example:

```json
{
  "packs": [
    {
      "name": "foundry/auth",
      "reason": "Provides auth guards required by requested flow.",
      "distribution": "premium",
      "entitlement": "missing",
      "executable": false
    }
  ]
}
```

If explain integration belongs to a later MCP explainability spec, add the seam and tests necessary to ensure entitlement data is available to explain.

---

## Error Codes

Required deterministic codes or equivalents:

```text
MISSING_ENTITLEMENT
EXPIRED_ENTITLEMENT
UNKNOWN_ENTITLEMENT
ENTITLEMENT_STATE_CHANGED
ENTITLEMENT_VALIDATION_FAILED
MARKETPLACE_PACK_NOT_AVAILABLE
```

---

## Determinism Rules

Same:

- prompt
- graph
- pack index
- entitlement state
- installed pack state

must produce the same plan and same entitlement summary.

Different entitlement state may produce different execution state, but the difference must be explicit in the plan.

---

## Testing Requirements

Test:

- MCP plan with free pack.
- MCP plan with premium pack granted.
- MCP plan with premium pack missing.
- MCP plan with expired entitlement.
- MCP apply revalidates entitlement.
- MCP apply blocks when entitlement changes after planning.
- Generate planning includes entitlement state.
- Generate execution blocks missing entitlement.
- no duplicate resolver bypass.
- deterministic output ordering.
- explain output includes entitlement data or exposes entitlement data to explain layer.

---

## Acceptance Criteria

- MCP surfaces entitlement state in plans.
- MCP blocks unauthorized apply.
- Generate surfaces entitlement state in plans.
- Generate blocks unauthorized execution.
- entitlement failures are structured validation failures.
- entitlement logic remains centralized.
- plan execution state reflects entitlement status.
- all tests and gates pass.

---

## Required Verification

```bash
php bin/foundry spec:validate --json
php bin/foundry verify context --json
php bin/foundry verify features --json
php bin/foundry verify contracts --json
php bin/foundry inspect marketplace --json
php bin/foundry verify marketplace --json
php vendor/bin/phpunit
XDEBUG_MODE=coverage php vendor/bin/phpunit --coverage-clover build/coverage/clover.xml
php bin/foundry verify coverage --min=90 --clover=build/coverage/clover.xml --json
```

All commands must exit `0`.
