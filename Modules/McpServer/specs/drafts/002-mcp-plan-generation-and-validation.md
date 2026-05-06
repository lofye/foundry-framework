# Execution Spec: 002-mcp-plan-generation-and-validation

## Purpose

Introduce deterministic MCP planning infrastructure for safe AI-assisted code modification.

This spec introduces:

- generate_plan
- validate_plan
- deterministic plan payloads
- entitlement-aware planning metadata
- structured execution states

This spec does NOT apply mutations.

---

## Goals

1. Allow MCP clients to generate plans
2. Allow MCP clients to validate plans
3. Surface entitlement requirements
4. Produce deterministic plan payloads
5. Integrate with Generate and Marketplace logic

---

## New MCP Tools

### generate_plan

Input:

```json
{
  "prompt": "add blog with authentication"
}
```

Optional:

```json
{
  "prompt": "add blog with authentication",
  "allow_pack_install": true,
  "allow_premium_packs": true
}
```

### validate_plan

Responsibilities:

- validate graph freshness
- validate entitlement state
- validate pack availability

---

## Plan Requirements

Plans must:

- be deterministic
- reference exact pack versions
- include explicit diffs
- include entitlement state
- include execution_state

---

## Required Entitlement Metadata

```json
{
  "entitlement": {
    "required": true,
    "status": "granted|missing|not_required|unknown",
    "tier": "free|licensed|premium|null"
  }
}
```

---

## Plan Execution States

Allowed states:

- executable
- blocked_missing_entitlement
- blocked_conflict
- stale
- invalid

---

## Determinism Rules

Same:

- prompt
- graph
- entitlement state

must produce the same plan.

---

## Testing

Must test:

- deterministic plan generation
- entitlement-aware planning
- stale plan detection
- missing entitlement handling
- plan validation

---

## Acceptance Criteria

- plans are deterministic
- plans expose entitlement state
- plans expose execution state
- validation works correctly
