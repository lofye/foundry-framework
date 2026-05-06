# Feature Spec: marketplace

## Purpose

Define deterministic framework-owned marketplace behavior for local pack metadata/artifact access plus Marketplace identity and authentication flows.

The framework Marketplace module defines marketplace contracts, protocol behavior, CLI integration, client-side resolution, and deterministic validation. It is not the canonical hosted marketplace application. The hosted marketplace service, account UI, payment flows, and production storage live in the website repo, which may consume the framework module via the pinned framework/ checkout.

## Goals

- Provide canonical local marketplace storage under `.foundry/marketplace`.
- Provide deterministic read-only marketplace inspection and verification surfaces.
- Provide deterministic backend handlers for `GET /packs`, `GET /packs/{name}`, and `GET /packs/{name}/{version}/download`.
- Provide deterministic CLI identity/authentication flows through `login`, `logout`, and `whoami`.
- Provide deterministic Marketplace token storage and authenticated-request construction.
- Preserve compatibility with existing extension pack metadata semantics.

## Non-Goals

- No billing, licensing, search ranking, reviews, or hosted sync.
- No third-party upload APIs or background publishing workflows.
- No MCP marketplace tools in this module step.

## Constraints

- No absolute filesystem paths in user-facing JSON responses.
- Deterministic ordering for packs, versions, errors, and output fields.
- Artifact resolution must not allow path traversal outside `.foundry/marketplace`.
- Authentication output must not expose raw access tokens in inspectable identity payloads.
- Framework runtime implementation remains under `src/*` rather than `Modules/Marketplace/src/*`.

## Expected Behavior

- Missing `.foundry/marketplace/packs.json` is treated as an empty marketplace.
- `inspect marketplace --json` returns storage location, deterministic pack summaries, and totals.
- `verify marketplace --json` validates metadata/artifact integrity and returns deterministic stable error codes.
- Marketplace backend handlers expose deterministic payloads and error semantics for list/detail/download flows.
- `login --user=<id> --token=<token>` stores Marketplace identity under `.foundry/marketplace/identity.json`.
- `whoami --json` returns deterministic authentication state and identity hints without exposing raw tokens.
- `logout --json` clears stored Marketplace identity credentials deterministically.
- Authenticated Marketplace request construction is available through deterministic runtime service behavior.

## Acceptance Criteria

- Marketplace repository/verifier/controller runtime exists under `src/Marketplace/*`.
- `inspect marketplace --json` and `verify marketplace --json` are registered and discoverable.
- `login`, `logout`, and `whoami` commands are registered, deterministic, and covered by tests.
- Marketplace identity storage handles missing, malformed, and valid credential states deterministically.
- Invalid pack names, invalid artifact paths, missing artifacts, and checksum mismatches fail deterministically.
- Required quality and verification gates pass with no context or contract drift.

## Assumptions

- Later marketplace specs will add identity, entitlements, monetization, and MCP integration.
