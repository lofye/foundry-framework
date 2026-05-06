# Execution Spec: 005-mcp-plan-explainability-and-dev-ux

## Purpose

Introduce explainability and developer UX flows for MCP planning and execution.

---

## Goals

1. Explain why plans chose packs
2. Explain entitlement state
3. Explain execution readiness
4. Support transparent AI-assisted workflows

---

## Explain Integration

CLI:

```bash
foundry explain plan plan_abc123
```

Explain output must include:

- why each pack was chosen
- pack distribution type
- entitlement state
- execution readiness

---

## Example Explain Output

```json
{
  "packs": [
    {
      "name": "foundry/blog",
      "reason": "Provides blog schemas.",
      "entitlement": "not_required"
    },
    {
      "name": "foundry/auth",
      "reason": "Provides auth guards.",
      "entitlement": "missing"
    }
  ]
}
```

---

## Developer UX Rules

Flow:

1. generate_plan
2. inspect plan
3. inspect entitlements
4. acquire entitlement if needed
5. apply_plan

---

## Important Rules

- plans must remain transparent
- entitlement gaps must remain visible
- no silent premium substitution
- execution readiness must be explicit

---

## Testing

Must test:

- explain output determinism
- entitlement explainability
- plan readiness reporting
- stable ordering
- developer-facing structured output

---

## Acceptance Criteria

- plans are explainable
- entitlement reasoning visible
- execution readiness visible
- outputs deterministic
