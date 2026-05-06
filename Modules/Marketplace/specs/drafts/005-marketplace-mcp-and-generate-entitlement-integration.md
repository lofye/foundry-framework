# Execution Spec: 005-marketplace-mcp-and-generate-entitlement-integration

## Purpose

Integrate Marketplace entitlement enforcement into MCP and Generate flows.

## MCP Planning

Example:

```json
{
  "entitlements": {
    "status": "incomplete",
    "missing": [
      "vendor/premium-pack"
    ]
  }
}
```

## Acceptance Criteria

- MCP surfaces entitlement state
- MCP blocks apply when required
- Generate respects entitlement rules
- entitlement logic remains centralized
