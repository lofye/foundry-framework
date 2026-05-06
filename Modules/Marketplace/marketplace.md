# Feature: marketplace

## Purpose

- Record the implemented minimal marketplace backend state for deterministic pack distribution contracts.

## Current State

- Missing `.foundry/marketplace/packs.json` is treated as an empty marketplace.
- Framework-owned marketplace runtime now exists under `src/Marketplace/*` with deterministic repository, verifier, index, model, and HTTP-style controller classes.
- Marketplace storage contract uses `.foundry/marketplace/packs.json` plus relative artifact paths under `.foundry/marketplace/artifacts/*`.
- `inspect marketplace --json` returns deterministic storage metadata, pack summaries, and aggregate totals.
- `verify marketplace --json` returns deterministic pass/fail status with stable error codes, checked counts, and non-zero exit status on failures.
- Backend handlers provide deterministic `GET /packs`, `GET /packs/{name}`, and `GET /packs/{name}/{version}/download` semantics through controller methods.
- Marketplace backend handlers expose deterministic payloads and error semantics for list/detail/download flows.
- CLI/API surface and command-catalog contracts include `inspect marketplace` and `verify marketplace`.
- Invalid pack names, invalid artifact paths, missing artifacts, and checksum mismatches fail deterministically.
- Required quality and verification gates pass with no context or contract drift.

## Open Questions

- Future specs still need identity/authentication, entitlement, purchase, and MCP integration behavior.

## Next Steps

- Implement Marketplace spec `002-marketplace-identity-and-authentication` after promotion from drafts.
- Extend marketplace compatibility checks for hosted/distributed registry workflows in later specs.
