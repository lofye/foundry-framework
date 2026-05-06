# 003-marketplace-entitlements-and-license-activation

## Purpose

Introduce deterministic Marketplace entitlement resolution, entitlement caching, and license activation for Foundry.

This spec builds on:

- `001-marketplace-backend-minimal-viable.md`
- `002-marketplace-identity-and-authentication.md`
- `002.001-marketplace-auth-runtime-contracts.md`

This spec establishes the framework-side runtime contracts used by CLI, MCP, Generate, and the website-hosted Marketplace implementation.

---

## Core Principle

Foundry does not restrict local code execution through DRM.

Foundry governs access to Marketplace-hosted distribution, services, downloads, updates, and entitlement-aware automation.

Entitlement checks must be transparent, deterministic, and centralized.

---

## Scope

Framework repo responsibilities:

- entitlement model
- pack distribution metadata model
- entitlement resolver contract/runtime
- local entitlement cache
- license activation CLI contract
- entitlement inspection CLI contract
- deterministic verifier behavior
- integration points for MCP and Generate

Website repo responsibilities:

- production account database
- hosted license database
- payment-provider fulfillment
- server-side license validation
- account/license UI

---

## Goals

1. Introduce canonical entitlement data model.
2. Introduce pack distribution metadata requirements.
3. Introduce `PackEntitlementResolver`.
4. Introduce local entitlement cache.
5. Introduce license activation command.
6. Introduce entitlement inspection command.
7. Ensure expired/malformed entitlement state fails closed.
8. Prepare MCP and Generate to consume a single entitlement decision service.

---

## Non-Goals

- No DRM.
- No code obfuscation.
- No local code execution blocking for already-installed code.
- No production payment provider.
- No website UI.
- No hosted license database implementation.
- No duplicate entitlement logic inside MCP or Generate.

---

## Entitlement Model

Canonical entitlement object:

```json
{
  "pack": "foundry/auth",
  "type": "licensed",
  "status": "granted",
  "expires_at": null,
  "source": "marketplace",
  "granted_at": "2026-01-01T00:00:00Z"
}
```

Required fields:

- `pack`
- `type`
- `status`

Optional fields:

- `expires_at`
- `source`
- `granted_at`

Allowed `type` values:

```text
free
licensed
premium
```

Allowed `status` values:

```text
granted
missing
expired
unknown
```

Rules:

- expired entitlements must not resolve as granted.
- missing entitlement must be explicit.
- unknown state must fail closed for premium/entitlement-required packs.
- serialization must be deterministic.

---

## Pack Distribution Metadata

Marketplace pack metadata must support:

```json
{
  "distribution": "premium",
  "price": {
    "currency": "CAD",
    "amount": "49.00"
  },
  "entitlement_required": true
}
```

Allowed `distribution` values:

```text
free
licensed
premium
```

Rules:

- `free` packs do not require entitlement.
- `licensed` packs require granted entitlement unless explicitly configured otherwise.
- `premium` packs require granted active entitlement.
- missing metadata must produce deterministic validation errors.
- metadata must be available to CLI, MCP, and Generate.

---

## PackEntitlementResolver

Introduce or harden:

```text
PackEntitlementResolver
```

Responsibilities:

- determine whether a pack requires entitlement.
- determine entitlement tier/type.
- determine current entitlement status.
- read local cache.
- call Marketplace client when online and appropriate.
- handle offline mode explicitly.
- return deterministic structured result.

Canonical result:

```json
{
  "pack": "vendor/premium-pack",
  "required": true,
  "status": "missing",
  "tier": "premium",
  "source": "marketplace",
  "offline": false,
  "expires_at": null
}
```

No duplicate entitlement logic is allowed outside this resolver.

---

## Local Entitlement Cache

Recommended path:

```text
.foundry/marketplace/entitlements.json
```

Rules:

- root-aware.
- deterministic serialization.
- malformed cache fails closed.
- expired entitlements are detectable.
- secret/license key values are not exposed through normal inspection.
- cache can be refreshed after license activation or purchase.

---

## Offline Behavior

Offline behavior must be explicit.

Allowed:

- cached granted entitlements may be used temporarily if not expired.
- free packs remain usable.

Required:

- expired entitlements fail closed.
- malformed cache fails closed.
- unknown entitlement state blocks premium/required operations.
- output must clearly indicate offline/cached state where relevant.

---

## License Activation

CLI:

```bash
foundry license activate KEY
```

JSON:

```bash
foundry license activate KEY --json
```

Behavior:

1. requires Marketplace auth when the activation flow needs identity.
2. sends license key to Marketplace backend/client abstraction.
3. receives entitlement grants.
4. updates local entitlement cache.
5. returns deterministic result.
6. never echoes full license key in output.

Success example:

```json
{
  "status": "ok",
  "activated": true,
  "entitlements": [
    {
      "pack": "vendor/premium-pack",
      "type": "premium",
      "status": "granted",
      "expires_at": null
    }
  ]
}
```

Failure example:

```json
{
  "status": "error",
  "code": "MARKETPLACE_LICENSE_INVALID"
}
```

---

## Entitlement Listing

CLI:

```bash
foundry entitlements
foundry entitlements --json
```

JSON example:

```json
{
  "status": "ok",
  "entitlements": [
    {
      "pack": "vendor/premium-pack",
      "type": "premium",
      "status": "granted",
      "expires_at": null
    }
  ]
}
```

Ordering:

- by pack name ascending
- then type
- then status

---

## Error Codes

Use deterministic error codes such as:

```text
MARKETPLACE_ENTITLEMENT_MISSING
MARKETPLACE_ENTITLEMENT_EXPIRED
MARKETPLACE_ENTITLEMENT_UNKNOWN
MARKETPLACE_ENTITLEMENT_CACHE_INVALID
MARKETPLACE_LICENSE_INVALID
MARKETPLACE_LICENSE_ACTIVATION_FAILED
MARKETPLACE_AUTH_REQUIRED
```

---

## Security Rules

- full license keys must not be logged.
- full license keys must not appear in normal CLI output.
- entitlement decisions for licensed/premium packs must fail closed.
- client-side cache is not the source of truth for granting new entitlements.
- license validation must be server-side when online.

---

## Integration Requirements

### CLI Install / Download

Marketplace pack install/download flows must call `PackEntitlementResolver` before accessing entitlement-required distribution.

### MCP

MCP plans must eventually call the same resolver.

### Generate

Generate/pack composition must eventually call the same resolver.

This spec may add integration seams even if full MCP/Generate enforcement is completed in later specs.

---

## Verify / Inspect Integration

`inspect marketplace --json` should include entitlement cache state without secrets.

`verify marketplace --json` should validate:

- entitlement cache shape.
- expired entitlement detection.
- malformed cache behavior.
- distribution metadata shape.

---

## Testing Requirements

Test:

- free pack resolution.
- licensed pack with granted entitlement.
- licensed pack missing entitlement.
- premium pack with granted entitlement.
- premium pack expired.
- unknown/malformed entitlement cache.
- offline mode with valid cache.
- offline mode with expired cache.
- license activation success.
- license activation failure.
- license key redaction.
- deterministic entitlement listing.
- verifier behavior.

---

## Acceptance Criteria

- `PackEntitlementResolver` exists or equivalent centralized service exists.
- entitlement resolution is deterministic.
- license activation updates entitlement cache.
- entitlement listing is deterministic.
- expired entitlements fail closed.
- malformed entitlement cache fails closed.
- no duplicate entitlement decision logic is introduced.
- CLI, inspect, and verify behavior remain deterministic.
- all required gates pass.

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
