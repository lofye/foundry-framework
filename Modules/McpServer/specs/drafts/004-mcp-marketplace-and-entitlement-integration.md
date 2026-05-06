# Execution Spec: 004-mcp-marketplace-and-entitlement-integration

## Purpose

Integrate Marketplace packs and entitlement awareness into MCP planning and execution.

---

## Goals

1. Support marketplace pack planning
2. Surface pack distribution type
3. Distinguish installed vs marketplace packs
4. Support entitlement-aware pack composition

---

## Marketplace Pack Metadata

Plans must distinguish:

- local installed packs
- free marketplace packs
- licensed marketplace packs
- premium marketplace packs

Example:

```json
{
  "pack": "foundry/auth",
  "source": "marketplace",
  "distribution": "premium"
}
```

---

## Plan Entitlement Summary

Every plan must include:

```json
{
  "entitlements": {
    "status": "complete|incomplete|unknown",
    "required": ["foundry/auth"],
    "granted": [],
    "missing": ["foundry/auth"]
  }
}
```

---

## Marketplace Integration Rules

MCP must:

- use Marketplace resolution logic
- use Generate pack resolution logic
- use centralized entitlement logic

No duplicate marketplace logic allowed.

---

## Entitlement Failure Rules

Entitlement failures are:

- structured validation failures
- not generic install errors

Plans with missing entitlements may still be generated but must not be executable.

---

## Testing

Must test:

- marketplace pack planning
- premium entitlement handling
- installed vs marketplace pack distinction
- missing entitlement summaries
- deterministic marketplace planning

---

## Acceptance Criteria

- marketplace packs appear in plans correctly
- entitlements summarized correctly
- plans remain deterministic
- missing entitlements block execution
