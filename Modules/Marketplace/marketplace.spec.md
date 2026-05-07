# Feature Spec: marketplace

## Purpose

Define deterministic framework-owned marketplace behavior for local pack metadata/artifact access, Marketplace identity/authentication flows, and entitlement/license activation contracts.

The framework Marketplace module defines marketplace contracts, protocol behavior, CLI integration, client-side resolution, and deterministic validation. It is not the canonical hosted marketplace application. The hosted marketplace service, account UI, payment flows, and production storage live in the website repo, which may consume the framework module via the pinned framework/ checkout.

## Goals

- Provide canonical local marketplace storage under `.foundry/marketplace`.
- Provide deterministic read-only marketplace inspection and verification surfaces.
- Provide deterministic backend handlers for `GET /packs`, `GET /packs/{name}`, and `GET /packs/{name}/{version}/download`.
- Provide deterministic CLI identity/authentication flows through `login`, `logout`, and `whoami`.
- Provide deterministic Marketplace token storage and authenticated-request construction.
- Preserve compatibility with existing extension pack metadata semantics.
- Provide deterministic entitlement cache, centralized entitlement resolution, and license-activation flows for Marketplace-hosted distribution access.

## Non-Goals

- No billing, search ranking, reviews, or hosted sync.
- No third-party upload APIs or background publishing workflows.
- No MCP marketplace tools in this module step.

## Constraints

- No absolute filesystem paths in user-facing JSON responses.
- Deterministic ordering for packs, versions, errors, and output fields.
- Artifact resolution must not allow path traversal outside `.foundry/marketplace`.
- Authentication output must not expose raw access tokens in inspectable identity payloads.
- Entitlement output must not expose raw license keys.
- Entitlement-required pack distribution must fail closed for malformed, missing, expired, or unknown entitlement state.
- Framework runtime implementation remains under `src/*` rather than `Modules/Marketplace/src/*`.

## Expected Behavior

- Missing `.foundry/marketplace/packs.json` is treated as an empty marketplace.
- `inspect marketplace --json` returns storage location, deterministic pack summaries, and totals.
- `verify marketplace --json` validates metadata/artifact integrity and returns deterministic stable error codes.
- Marketplace pack metadata includes deterministic distribution metadata (`distribution`, `entitlement_required`, optional `price`).
- Marketplace backend handlers expose deterministic payloads and error semantics for list/detail/download flows.
- `login --user=<id> --token=<token>` stores Marketplace identity under `.foundry/marketplace/identity.json`.
- `whoami --json` returns deterministic authenticated, unauthenticated, expired, and malformed-state shapes without exposing raw tokens.
- `logout --json` clears stored Marketplace identity credentials deterministically.
- `entitlements --json` returns deterministic entitlement cache state ordered by pack/type/status.
- `license activate KEY --json` refreshes Marketplace entitlements through a deterministic client abstraction and stores cache data under `.foundry/marketplace/entitlements.json`.
- Authenticated Marketplace request construction is available through deterministic runtime service behavior.
- `inspect marketplace --json` includes safe auth inspection (`configured`, `authenticated`, redacted token metadata, safe user metadata).
- `inspect marketplace --json` includes entitlement cache inspection (`configured`, `status`, `path`, ordered entitlements) without secrets.
- `verify marketplace --json` includes deterministic auth + entitlement cache verification and fails closed for malformed or expired credential/entitlement state.
- Entitlement-required marketplace downloads resolve through centralized entitlement decisions.

## Acceptance Criteria

- Marketplace repository/verifier/controller runtime exists under `src/Marketplace/*`.
- `inspect marketplace --json` and `verify marketplace --json` are registered and discoverable.
- `login`, `logout`, and `whoami` commands are registered, deterministic, and covered by tests.
- Marketplace identity storage handles missing, malformed, and valid credential states deterministically.
- Marketplace identity storage and auth runtime fail closed for malformed/expired credentials and never leak raw secret values in inspect/verify/whoami payloads.
- Marketplace entitlement cache handles missing, malformed, granted, and expired states deterministically without exposing raw license key values.
- Marketplace distribution metadata validation is deterministic and rejects missing/invalid distribution contract shape.
- Entitlement resolution is centralized via `PackEntitlementResolver` and reused by download/runtime seams.
- Invalid pack names, invalid artifact paths, missing artifacts, and checksum mismatches fail deterministically.
- Required quality and verification gates pass with no context or contract drift.

## Assumptions

- Later marketplace specs will add identity, entitlements, monetization, and MCP integration.
