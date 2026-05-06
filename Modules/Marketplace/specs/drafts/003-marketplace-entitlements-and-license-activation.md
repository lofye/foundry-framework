# Execution Spec: 003-marketplace-entitlements-and-license-activation

## Purpose

Introduce deterministic Marketplace entitlement and license activation infrastructure.

## Entitlement Model

```json
{
  "pack": "foundry/auth",
  "type": "free",
  "status": "granted",
  "expires_at": null
}
```

## CLI Commands

```bash
foundry entitlements
foundry license activate KEY
```

## Acceptance Criteria

- packs declare distribution metadata
- entitlements resolve deterministically
- offline cache functions correctly
- expired entitlements invalidate correctly
