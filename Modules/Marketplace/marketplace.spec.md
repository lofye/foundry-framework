# Feature Spec: marketplace

## Purpose

Define deterministic framework-owned marketplace backend behavior for listing pack metadata and serving pack artifacts from repository-local storage.

## Goals

- Provide canonical local marketplace storage under `.foundry/marketplace`.
- Provide deterministic read-only marketplace inspection and verification surfaces.
- Provide deterministic backend handlers for `GET /packs`, `GET /packs/{name}`, and `GET /packs/{name}/{version}/download`.
- Preserve compatibility with existing extension pack metadata semantics.

## Non-Goals

- No authentication, billing, licensing, search ranking, reviews, or hosted sync.
- No third-party upload APIs or background publishing workflows.
- No MCP marketplace tools in this module step.

## Constraints

- No absolute filesystem paths in user-facing JSON responses.
- Deterministic ordering for packs, versions, errors, and output fields.
- Artifact resolution must not allow path traversal outside `.foundry/marketplace`.
- Framework runtime implementation remains under `src/*` rather than `Modules/Marketplace/src/*`.

## Expected Behavior

- Missing `.foundry/marketplace/packs.json` is treated as an empty marketplace.
- `inspect marketplace --json` returns storage location, deterministic pack summaries, and totals.
- `verify marketplace --json` validates metadata/artifact integrity and returns deterministic stable error codes.
- Marketplace backend handlers expose deterministic payloads and error semantics for list/detail/download flows.

## Acceptance Criteria

- Marketplace repository/verifier/controller runtime exists under `src/Marketplace/*`.
- `inspect marketplace --json` and `verify marketplace --json` are registered and discoverable.
- Invalid pack names, invalid artifact paths, missing artifacts, and checksum mismatches fail deterministically.
- Required quality and verification gates pass with no context or contract drift.

## Assumptions

- Later marketplace specs will add identity, entitlements, monetization, and MCP integration.
