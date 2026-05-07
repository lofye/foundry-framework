# Feature: marketplace

## Purpose

- Record the implemented Marketplace backend + identity/authentication + entitlement/license-activation state for deterministic pack distribution and authenticated Marketplace access contracts.

## Current State

- Missing `.foundry/marketplace/packs.json` is treated as an empty marketplace.
- `inspect marketplace --json` returns storage location, deterministic pack summaries, and totals.
- `verify marketplace --json` validates metadata/artifact integrity and returns deterministic stable error codes.
- Marketplace pack metadata enforces deterministic distribution contract validation (`distribution`, `entitlement_required`, optional `price`) at load time.
- Marketplace backend handlers expose deterministic payloads and error semantics for list/detail/download flows.
- Marketplace download flows fail closed through centralized entitlement decisions (`PackEntitlementResolver`) for entitlement-required distributions.
- `login --user=<id> --token=<token>` stores Marketplace identity under `.foundry/marketplace/identity.json`.
- `whoami --json` returns deterministic authenticated, unauthenticated, expired, and malformed-state shapes without exposing raw tokens.
- `logout --json` clears stored Marketplace identity credentials deterministically.
- `entitlements --json` exposes deterministic cached entitlement listings from `.foundry/marketplace/entitlements.json`, ordered by pack/type/status.
- `license activate KEY --json` refreshes Marketplace entitlements via a deterministic Marketplace license-client abstraction and stores redacted activation output.
- Authenticated Marketplace request construction is available through deterministic runtime service behavior.
- `inspect marketplace --json` includes safe auth inspection (`configured`, `authenticated`, redacted token metadata, safe user metadata).
- `inspect marketplace --json` includes entitlement cache inspection (`configured`, `status`, `path`, ordered entitlements) without secrets.
- `verify marketplace --json` includes deterministic auth + entitlement cache verification and fails closed for malformed or expired credential/entitlement state.
- Marketplace repository/verifier/controller runtime exists under `src/Marketplace/*`.
- `inspect marketplace --json` and `verify marketplace --json` are registered and discoverable.
- `login`, `logout`, `whoami`, and `entitlements` commands are registered, deterministic, and covered by tests.
- Marketplace identity storage handles missing, malformed, and valid credential states deterministically.
- Marketplace identity storage and auth runtime fail closed for malformed/expired credentials and never leak raw secret values in inspect/verify/whoami payloads.
- Marketplace entitlement cache inspection/verification is deterministic and fails closed for malformed cache state.
- Invalid pack names, invalid artifact paths, missing artifacts, and checksum mismatches fail deterministically.
- Required quality and verification gates pass with no context or contract drift.

## Open Questions

- Future specs still need purchase flow fulfillment and MCP/Generate enforcement breadth beyond the currently added entitlement integration seams.

## Next Steps

- Extend hosted-registry and Generate/MCP pack workflows to consume the same entitlement decision service at every entitlement-required ingress.
- Add website-backed activation and entitlement refresh adapters when hosted Marketplace runtime is wired to production services.
