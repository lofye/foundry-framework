# Feature: marketplace

## Purpose

- Record the implemented Marketplace backend + identity/authentication state for deterministic pack distribution and authenticated Marketplace access contracts.

## Current State

- Missing `.foundry/marketplace/packs.json` is treated as an empty marketplace.
- `inspect marketplace --json` returns storage location, deterministic pack summaries, and totals.
- `verify marketplace --json` validates metadata/artifact integrity and returns deterministic stable error codes.
- Marketplace backend handlers expose deterministic payloads and error semantics for list/detail/download flows.
- `login --user=<id> --token=<token>` stores Marketplace identity under `.foundry/marketplace/identity.json`.
- `whoami --json` returns deterministic authenticated, unauthenticated, expired, and malformed-state shapes without exposing raw tokens.
- `logout --json` clears stored Marketplace identity credentials deterministically.
- Authenticated Marketplace request construction is available through deterministic runtime service behavior.
- Marketplace repository/verifier/controller runtime exists under `src/Marketplace/*`.
- `inspect marketplace --json` and `verify marketplace --json` are registered and discoverable.
- `login`, `logout`, and `whoami` commands are registered, deterministic, and covered by tests.
- Marketplace identity storage handles missing, malformed, and valid credential states deterministically.
- Marketplace identity storage and auth runtime fail closed for malformed/expired credentials and never leak raw secret values in inspect/verify/whoami payloads.
- Invalid pack names, invalid artifact paths, missing artifacts, and checksum mismatches fail deterministically.
- Required quality and verification gates pass with no context or contract drift.

## Open Questions

- Future specs still need entitlement, purchase, and MCP integration behavior.

## Next Steps

- Implement Marketplace spec `003-marketplace-entitlements-and-license-activation` after promotion from drafts.
- Extend Marketplace request execution to consume authenticated request construction in hosted/distributed registry flows.
