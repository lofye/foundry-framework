# Feature: marketplace

## Purpose

- Record the implemented Marketplace backend + identity/authentication + entitlement/license-activation + purchase-flow state for deterministic pack distribution and authenticated Marketplace access contracts.

## Decision Summary

Refreshed Through Spec: `005-marketplace-mcp-and-generate-entitlement-integration`

- Marketplace state is deterministic and fail-closed: missing metadata is empty, malformed metadata fails verification, and secret values are redacted.
- Identity, entitlement cache, license activation, and purchase flows use centralized services with stable inspect/verify/CLI payloads.
- Entitlement-required pack access flows through shared entitlement resolution so Marketplace, Generate, and MCP behavior do not duplicate policy logic.
- MCP planning/apply integration surfaces entitlement state and revalidates before mutation, while richer plan explainability remains future work.

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
- `pack purchase <vendor/pack> --json` executes deterministic purchase flow outcomes for free/not-purchasable, already-entitled, auth-required, pending checkout handoff, success, and partial entitlement-refresh failure states.
- Purchase flows use centralized `MarketplacePurchaseService` + purchase-client abstraction and refresh entitlements through shared entitlement runtime only.
- Authenticated Marketplace request construction is available through deterministic runtime service behavior.
- `inspect marketplace --json` includes safe auth inspection (`configured`, `authenticated`, redacted token metadata, safe user metadata).
- `inspect marketplace --json` includes entitlement cache inspection (`configured`, `status`, `path`, ordered entitlements) without secrets.
- `verify marketplace --json` includes deterministic auth + entitlement cache verification and fails closed for malformed or expired credential/entitlement state.
- `inspect marketplace --json` includes deterministic purchase capability metadata (`enabled`, `client`, auth requirement, handoff capability, live-mode requirement).
- `verify marketplace --json` includes deterministic purchase capability readiness checks without requiring live payment credentials by default.
- Generate planning/runtime now includes deterministic Marketplace entitlement context (`execution_state`, entitlement summary, pack requirement details) whenever Marketplace-dependent pack hints are evaluated.
- Generate auto-install and replay/apply flows fail closed on missing/expired/unknown/invalid Marketplace entitlements with deterministic error codes and structured details.
- MCP server now exposes `generate_plan` and `generate_apply` tools that surface blocked/applied status and enforce replay-time entitlement revalidation before mutation.
- Marketplace repository/verifier/controller runtime exists under `src/Marketplace/*`.
- `inspect marketplace --json` and `verify marketplace --json` are registered and discoverable.
- `login`, `logout`, `whoami`, and `entitlements` commands are registered, deterministic, and covered by tests.
- Marketplace identity storage handles missing, malformed, and valid credential states deterministically.
- Marketplace identity storage and auth runtime fail closed for malformed/expired credentials and never leak raw secret values in inspect/verify/whoami payloads.
- Marketplace entitlement cache inspection/verification is deterministic and fails closed for malformed cache state.
- Invalid pack names, invalid artifact paths, missing artifacts, and checksum mismatches fail deterministically.
- Required quality and verification gates pass with no context or contract drift.

## Open Questions

- Future specs still need hosted payment-provider fulfillment and deeper MCP explainability surfaces that render entitlement reasoning more richly for long-form plan/explain workflows.

## Next Steps

- Extend MCP/Generate workflows to consume purchase-aware entitlement state transitions at every entitlement-required ingress.
- Add website-backed checkout, purchase completion, and entitlement-refresh adapters when hosted Marketplace runtime is wired to production services.
