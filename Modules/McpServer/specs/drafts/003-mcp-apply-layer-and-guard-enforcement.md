# Execution Spec: 003-mcp-apply-layer-and-guard-enforcement

## Purpose

Introduce safe MCP mutation execution via guarded apply behavior.

This spec introduces:

- apply_plan
- guard enforcement
- entitlement revalidation
- safe Generate integration

---

## Goals

1. Allow controlled plan application
2. Revalidate plans before mutation
3. Block invalid or unauthorized plans
4. Prevent silent execution

---

## MCP Tool

### apply_plan

Input:

```json
{
  "plan_id": "plan_abc123"
}
```

Behavior:

- validates plan integrity
- validates plan freshness
- validates entitlement state
- applies via Generate engine
- returns structured result

---

## Guard Rules

Before apply:

- conflicts must be checked
- dependencies validated
- graph validity enforced
- entitlements revalidated

---

## Required Failures

Must block:

- missing entitlement
- stale plans
- dependency conflicts
- invalid plans
- unavailable packs

---

## Structured Errors

Example:

```json
{
  "error": "missing_entitlement",
  "pack": "foundry/auth",
  "message": "This plan requires access to foundry/auth."
}
```

---

## Approval Model

Rules:

- plans never auto-execute
- plans must be explicitly applied
- missing entitlements must be surfaced before apply

---

## Shared Services

Must reuse:

```text
PackEntitlementResolver
```

No duplicate entitlement logic allowed.

---

## Testing

Must test:

- apply success
- apply blocking
- stale plan handling
- entitlement revalidation
- Generate integration
- deterministic errors

---

## Acceptance Criteria

- plans apply safely
- invalid plans are blocked
- entitlement failures are structured
- no silent mutations occur
