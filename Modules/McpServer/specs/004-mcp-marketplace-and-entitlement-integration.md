# Execution Spec: 004-mcp-marketplace-and-entitlement-integration

## Purpose

Make MCP planning, validation, and apply behavior fully Marketplace-aware by exposing pack source, distribution, version, entitlement state, and entitlement blockers through stable MCP contracts.

This spec is the MCP-module contract for Marketplace integration. It must reuse Marketplace and Generate entitlement infrastructure that already exists or is introduced by earlier specs. It must not duplicate Marketplace policy logic.

## Feature

`mcp-server`

## Reasoning Target

This spec must be implementable by GPT-5.3-Codex at Medium reasoning.

The implementation should mostly normalize, expose, and test existing Marketplace/Generate data through MCP, not redesign Marketplace.

## Depends On

- `Modules/McpServer/specs/002-mcp-plan-generation-and-validation.md`
- `Modules/McpServer/specs/003-mcp-apply-layer-and-guard-enforcement.md`
- Marketplace entitlement and distribution contracts:
  - `Modules/Marketplace/specs/003-marketplace-entitlements-and-license-activation.md`
  - `Modules/Marketplace/specs/004-marketplace-purchase-and-monetization-flows.md`
  - `Modules/Marketplace/specs/005-marketplace-mcp-and-generate-entitlement-integration.md`
- Existing runtime services:
  - `src/Marketplace/MarketplaceRepository.php`
  - `src/Marketplace/MarketplaceEntitlementCache.php`
  - `src/Marketplace/PackEntitlementResolver.php`
  - `src/Packs/HostedPackRegistry.php`
  - `src/Generate/PackRequirementResolver.php`

## Goals

1. Expose Marketplace pack metadata consistently in MCP planning and validation responses.
2. Distinguish local installed packs from Marketplace packs.
3. Distinguish free, licensed, premium, and unknown Marketplace distributions.
4. Use centralized entitlement resolution for every entitlement decision.
5. Surface missing, expired, unknown, and invalid entitlement states as structured blockers.
6. Keep blocked Marketplace-dependent plans inspectable even when not executable.
7. Revalidate Marketplace entitlement state at apply time.

## Non-Goals

- Do not implement hosted Marketplace APIs.
- Do not implement purchase checkout, payment fulfillment, or account UI.
- Do not grant or refresh entitlements inside MCP planning/apply.
- Do not install Marketplace packs directly from MCP handlers.
- Do not block local user-owned source code that is already installed unless the requested operation requires Marketplace service access.
- Do not duplicate entitlement cache parsing in MCP.

## Required Shared Runtime

All entitlement decisions must route through:

```text
PackEntitlementResolver
```

or a lower-level Generate service that itself uses `PackEntitlementResolver`.

Forbidden:

- MCP-specific `if premium then block` logic.
- Reading `.foundry/marketplace/entitlements.json` directly from MCP handlers.
- Re-implementing Marketplace distribution metadata validation in MCP.
- Treating entitlement failures as generic install errors.

## Pack Requirement Contract

Every MCP planning/validation response that references packs must include ordered `pack_requirements` rows with this shape:

```json
{
  "pack": "foundry/auth",
  "source": "marketplace",
  "version": "1.0.0",
  "distribution": "premium",
  "entitlement_required": true,
  "entitlement": {
    "required": true,
    "status": "missing",
    "tier": "premium",
    "expires_at": null
  },
  "executable": false,
  "message": "Marketplace entitlement is missing."
}
```

Allowed `source` values:

```text
local
marketplace
unknown
```

Allowed `distribution` values:

```text
local
free
licensed
premium
unknown
```

Allowed entitlement `status` values:

```text
not_required
granted
missing
expired
unknown
invalid
```

Ordering:

- Sort rows by `pack` ascending.
- For duplicate pack names from invalid input, normalize to one row before output.

## Plan Entitlement Summary Contract

Every MCP planning/validation/apply preflight response must include:

```json
{
  "entitlements": {
    "status": "incomplete",
    "required": ["foundry/auth"],
    "granted": [],
    "missing": ["foundry/auth"],
    "expired": [],
    "unknown": [],
    "invalid": []
  }
}
```

Allowed summary `status` values:

```text
complete
incomplete
unknown
invalid
not_required
```

Mapping:

- No required entitlements -> `not_required`.
- All required entitlements granted -> `complete`.
- Any missing or expired entitlement -> `incomplete`.
- Any unknown entitlement state -> `unknown` unless invalid is also present.
- Any malformed distribution/cache state -> `invalid`.

Arrays must be sorted ascending and contain pack names only.

## Execution State Mapping

MCP must map Marketplace conditions to execution states consistently:

```text
missing entitlement -> blocked_missing_entitlement
expired entitlement -> blocked_expired_entitlement
unknown entitlement -> blocked_unknown_entitlement
invalid entitlement metadata -> invalid
Marketplace pack unavailable -> blocked_pack_unavailable
all required entitlements granted -> executable
```

When multiple blockers exist, precedence is:

1. `invalid`
2. `blocked_pack_unavailable`
3. `blocked_expired_entitlement`
4. `blocked_missing_entitlement`
5. `blocked_unknown_entitlement`
6. existing non-Marketplace blockers from specs `002` and `003`

## MCP Planning Requirements

`generate_plan` must:

- include `pack_requirements` whenever pack hints or Generate pack requirements exist.
- include `entitlements` summary on every response, even when no entitlements are required.
- preserve Marketplace pack rows for blocked plans.
- produce `status: "blocked"` when Marketplace blockers prevent execution.
- avoid hidden substitution of Marketplace packs.
- include exact Marketplace version when the hosted registry resolves one.

Example blocked plan response section:

```json
{
  "status": "blocked",
  "execution_state": "blocked_missing_entitlement",
  "entitlements": {
    "status": "incomplete",
    "required": ["foundry/auth"],
    "granted": [],
    "missing": ["foundry/auth"],
    "expired": [],
    "unknown": [],
    "invalid": []
  },
  "pack_requirements": [
    {
      "pack": "foundry/auth",
      "source": "marketplace",
      "version": "1.0.0",
      "distribution": "premium",
      "entitlement_required": true,
      "entitlement": {
        "required": true,
        "status": "missing",
        "tier": "premium",
        "expires_at": null
      },
      "executable": false,
      "message": "Marketplace entitlement is missing."
    }
  ]
}
```

## MCP Validation Requirements

`validate_plan` must:

- reload persisted entitlement and pack requirement context when validating by `plan_id`.
- re-evaluate current Marketplace entitlement state when enough pack metadata is available.
- report planned entitlement state and current entitlement state when they differ.
- mark validation blocked when current state no longer satisfies required entitlements.
- never regenerate a fresh plan silently to hide missing metadata.

If a persisted plan lacks enough Marketplace metadata for current entitlement revalidation, return a warning and preserve the most conservative execution state available. Do not claim entitlement readiness from incomplete data.

## MCP Apply Requirements

`apply_plan` must:

- revalidate Marketplace entitlement state immediately before live mutation.
- block if any required entitlement is missing, expired, unknown, invalid, or changed from granted to not granted.
- return structured Marketplace blocker codes.
- never download or install entitlement-required Marketplace packs without a granted entitlement.

Required codes:

```text
MISSING_ENTITLEMENT
EXPIRED_ENTITLEMENT
UNKNOWN_ENTITLEMENT
ENTITLEMENT_STATE_CHANGED
ENTITLEMENT_VALIDATION_FAILED
MARKETPLACE_PACK_NOT_AVAILABLE
```

## Determinism Rules

For identical:

- normalized MCP input
- installed pack registry
- hosted Marketplace index
- Marketplace entitlement cache
- Generate policy state
- app graph state

MCP planning, validation, and apply preflight output must be deterministic.

Marketplace metadata must not be fetched from a network in this spec. Use repository-local hosted registry/index contracts only.

## Backward Compatibility

- Keep existing Generate and Marketplace JSON fields stable.
- Add MCP fields by normalizing existing data where possible.
- Preserve existing `generate_apply` alias behavior if spec `003` kept it.
- Do not rename existing Marketplace error codes.

## Required Tests

Add or update tests covering:

- local installed pack requirement reports `source: "local"` and `distribution: "local"`.
- free Marketplace pack reports `source: "marketplace"`, `distribution: "free"`, and `entitlement.status: "not_required"`.
- premium Marketplace pack with grant reports `entitlement.status: "granted"` and executable state.
- premium Marketplace pack missing grant reports `blocked_missing_entitlement`.
- expired entitlement reports `blocked_expired_entitlement`.
- unknown entitlement state reports `blocked_unknown_entitlement`.
- unavailable Marketplace pack reports `blocked_pack_unavailable`.
- malformed Marketplace distribution metadata reports `invalid`.
- `validate_plan` surfaces planned vs current entitlement changes.
- `apply_plan` blocks when entitlement was granted at planning time but missing at apply time.
- output ordering is deterministic for multiple pack hints.
- MCP handlers do not parse entitlement cache files directly.

Test locations should follow existing patterns:

- `tests/Integration/CLIMcpServeCommandTest.php`
- `tests/Unit/MCPServerTest.php`
- `tests/Unit/PackRequirementResolverTest.php`
- `tests/Unit/PackEntitlementResolverTest.php`

## Acceptance Criteria

- MCP plan, validation, and apply preflight responses include stable entitlement summaries.
- MCP pack requirement rows distinguish local, free Marketplace, licensed Marketplace, premium Marketplace, and unknown Marketplace packs.
- Missing/expired/unknown/invalid entitlement states block execution deterministically.
- Apply revalidates current entitlement state before mutation.
- Entitlement logic remains centralized in Marketplace/Generate services.
- No network access is introduced.
- Existing Marketplace and Generate tests remain green.

## Required Verification

Run:

```bash
php bin/foundry spec:validate --json
php bin/foundry verify context --feature=mcp-server --json
php bin/foundry verify context --feature=marketplace --json
php bin/foundry verify features --json
php bin/foundry verify contracts --json
php bin/foundry inspect marketplace --json
php bin/foundry verify marketplace --json
php vendor/bin/phpunit --filter 'CLIMcpServeCommandTest|MCPServerTest|PackRequirementResolverTest|PackEntitlementResolverTest'
php vendor/bin/phpunit
XDEBUG_MODE=coverage php vendor/bin/phpunit --coverage-clover build/coverage/clover.xml
php bin/foundry verify coverage --min=90 --clover=build/coverage/clover.xml --json
```

All commands must exit `0` before this spec is reported complete.
