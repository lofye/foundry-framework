# Feature: pre-canonical

## Purpose

- Preserve imported pre-canonical archive lineage under `Modules/PreCanonical` without assigning modern module ownership.

## Current State

- Dry-run import reports deterministic artifacts without writing files.
- Apply import writes specs, reconstruction notes, context files, and idempotent implementation-log entries.
- Validators treat this module as archive lineage rather than a normal contiguous implementation queue.
- State records the concrete imported source, spec count, first imported spec, and last imported spec after apply.
- The imported archive remains reproducible from the same marked source file.
- Imported specs and plans remain under `Modules/PreCanonical`.
- Context validation can proceed without requiring modern ownership decisions.

## Decision Summary

- Pre-canonical archive records are preserved under `Modules/PreCanonical` instead of inferred into modern modules.
- `S`, `R`, and `P` markers remain the durable archive boundary for imported material.
- Refreshed Through Spec: `035-generate-system-end-to-end-explain-driven-pack-aware`

## Imported Range

- First imported spec: `000.001-foundational-compiler-layer`
- Last imported spec: `035-generate-system-end-to-end-explain-driven-pack-aware`
- Imported spec count: `85`

## Global Preamble Context

No unassociated marked preamble blocks were present in the imported archive.

## Open Questions

- Which pre-canonical archive records should be mapped into modern module ownership remains intentionally unresolved.

## Next Steps

- Use explicit future alignment specs to connect imported pre-canonical lineage to current framework modules.
