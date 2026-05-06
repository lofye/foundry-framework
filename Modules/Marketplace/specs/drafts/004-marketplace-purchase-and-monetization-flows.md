# 004-marketplace-purchase-and-monetization-flows

## Purpose

Introduce deterministic framework-side Marketplace purchase and monetization flow contracts.

This spec builds on:

- Marketplace backend
- Marketplace auth
- Marketplace entitlements/license activation

This spec does not implement the production hosted payment system. It defines the framework-side CLI/client/runtime behavior needed to initiate purchases and refresh entitlement state.

---

## Core Principle

Purchasing is a server-side Marketplace action.

The framework may initiate, inspect, and synchronize purchase results, but it must not independently grant paid access without server-side confirmation.

---

## Scope

Framework repo responsibilities:

- purchase command contract
- Marketplace purchase client abstraction
- deterministic purchase result handling
- entitlement refresh after purchase
- purchase failure handling
- safe browser/API handoff behavior
- tests for purchase client/runtime/CLI behavior

Website repo responsibilities:

- payment provider integration
- checkout pages
- hosted purchase sessions
- billing records
- receipt/invoice UI
- server-side entitlement grants

---

## Goals

1. Introduce `foundry pack purchase vendor/pack`.
2. Support purchase initiation.
3. Support deterministic purchase results.
4. Refresh entitlements after successful purchase.
5. Ensure failed purchases do not grant entitlements.
6. Provide stable integration surface for website-hosted checkout.
7. Prepare MCP/Generate to see updated entitlements after purchase.

---

## Non-Goals

- No Stripe/PayPal/etc direct implementation in the framework.
- No invoices.
- No subscriptions unless represented as Marketplace API metadata.
- No website checkout UI.
- No DRM.
- No hidden entitlement escalation.

---

## CLI Command

```bash
foundry pack purchase vendor/pack
foundry pack purchase vendor/pack --json
```

Optional flags may include existing project conventions, but behavior must stay deterministic.

---

## Purchase Flow

Required flow:

1. Validate pack identifier.
2. Resolve pack metadata from Marketplace.
3. Confirm pack is purchasable.
4. Require authentication if needed.
5. Initiate purchase through Marketplace client.
6. If browser handoff is returned, surface deterministic instructions.
7. If API completion is returned, refresh entitlements.
8. Return deterministic structured result.

---

## Purchase Result Contracts

### Browser Handoff

```json
{
  "status": "pending",
  "pack": "vendor/premium-pack",
  "checkout_url": "https://marketplace.example/checkout/session_123",
  "entitlement_refreshed": false
}
```

### Completed Purchase

```json
{
  "status": "success",
  "pack": "vendor/premium-pack",
  "entitlement_refreshed": true,
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

### Failure

```json
{
  "status": "error",
  "code": "MARKETPLACE_PURCHASE_FAILED",
  "pack": "vendor/premium-pack"
}
```

---

## Pack Eligibility

The purchase flow must distinguish:

- free pack
- licensed pack
- premium pack
- unavailable pack
- already owned pack
- installed local pack

Suggested handling:

- free pack: return deterministic “not purchasable / free” response.
- already owned: return deterministic “already entitled” response.
- missing pack: deterministic missing-pack failure.
- premium/licensed: initiate purchase if Marketplace allows.

---

## Entitlement Refresh

After purchase success:

- call the same entitlement refresh/cache mechanism from Marketplace 003.
- do not write entitlement state directly in duplicate code.
- updated entitlement must be visible to:
  - CLI entitlement listing
  - Marketplace verify/inspect
  - MCP planning
  - Generate planning/execution

If entitlement refresh fails after purchase completion, return structured partial state:

```json
{
  "status": "partial",
  "code": "MARKETPLACE_PURCHASE_COMPLETED_ENTITLEMENT_REFRESH_FAILED",
  "pack": "vendor/premium-pack"
}
```

---

## Marketplace Client Abstraction

Introduce or harden a purchase client operation.

Suggested responsibility:

```text
MarketplacePurchaseClient::purchase(pack)
```

or equivalent.

It must not directly depend on website implementation details.

It should consume:

- auth service
- Marketplace repository/API client
- entitlement refresh service/resolver

---

## Security Rules

- purchase validation is server-side.
- failed purchases never grant entitlements.
- checkout URLs must be treated as untrusted output from the server.
- auth tokens must not leak.
- purchase errors must not include secrets.
- local entitlement cache cannot manufacture paid access.

---

## Error Codes

Required deterministic codes or equivalents:

```text
MARKETPLACE_PURCHASE_FAILED
MARKETPLACE_PURCHASE_AUTH_REQUIRED
MARKETPLACE_PURCHASE_PACK_NOT_FOUND
MARKETPLACE_PURCHASE_PACK_NOT_PURCHASABLE
MARKETPLACE_PURCHASE_ALREADY_ENTITLED
MARKETPLACE_PURCHASE_ENTITLEMENT_REFRESH_FAILED
MARKETPLACE_PURCHASE_MARKETPLACE_UNAVAILABLE
```

---

## Determinism Requirements

- stable JSON key ordering.
- stable pack ordering in any entitlement arrays.
- no timestamps unless returned by Marketplace and persisted.
- no random checkout IDs generated locally in tests.
- test all client responses with fixed fixtures.

---

## Inspect / Verify Integration

`inspect marketplace --json` may include purchase capability metadata.

`verify marketplace --json` should validate purchase configuration/client readiness where applicable without requiring live network access.

Verification must not fail merely because production payment credentials are absent unless explicitly configured to require live Marketplace mode.

---

## Testing Requirements

Test:

- purchase command success.
- purchase command browser handoff.
- purchase command failure.
- unauthenticated purchase attempt.
- already-entitled purchase attempt.
- free pack purchase attempt.
- missing pack purchase attempt.
- entitlement refresh after purchase.
- entitlement refresh failure after purchase.
- no token leakage.
- deterministic JSON output.

---

## Acceptance Criteria

- `foundry pack purchase vendor/pack --json` exists.
- purchase flow is deterministic.
- successful purchase refreshes entitlements through shared entitlement runtime.
- failures do not grant entitlements.
- unauthenticated states fail deterministically.
- free/already-owned/missing pack cases are explicit.
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
