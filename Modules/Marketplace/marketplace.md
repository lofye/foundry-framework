# Feature: marketplace

## Purpose

- Record the implemented Marketplace backend + identity/authentication state for deterministic pack distribution and authenticated Marketplace access contracts.

## Current State

- Missing `.foundry/marketplace/packs.json` is treated as an empty marketplace.
- `inspect marketplace --json` returns deterministic storage metadata, pack summaries, and aggregate totals.
- `verify marketplace --json` returns deterministic pass/fail status with stable error codes, checked counts, and non-zero exit status on failures.
- Marketplace backend handlers expose deterministic payloads and error semantics for list/detail/download flows.
- `login --user=<id> --token=<token>` stores Marketplace identity under `.foundry/marketplace/identity.json`.
- `whoami --json` returns deterministic authentication state and identity hints without exposing raw tokens.
- `logout --json` clears stored Marketplace identity credentials deterministically.
- Authenticated Marketplace request construction is available through deterministic runtime service behavior.
- Marketplace repository/verifier/controller runtime exists under `src/Marketplace/*`.
- `inspect marketplace --json`, `verify marketplace --json`, `login`, `logout`, and `whoami` are registered and discoverable.
- Marketplace identity storage handles missing, malformed, and valid credential states deterministically.
- Invalid pack names, invalid artifact paths, missing artifacts, and checksum mismatches fail deterministically.
- Required quality and verification gates pass with no context or contract drift.

## Open Questions

- Future specs still need entitlement, purchase, and MCP integration behavior.

## Next Steps

- Implement Marketplace spec `003-marketplace-entitlements-and-license-activation` after promotion from drafts.
- Extend Marketplace request execution to consume authenticated request construction in hosted/distributed registry flows.
