# Feature Spec: pre-canonical

## Purpose

`Modules/PreCanonical` preserves explicitly marked pre-canonical archive material that predates the current module system. It is an archive-lineage module, not a cohesive modern runtime module.

## Goals

- Preserve explicitly marked pre-canonical archive specs, results, and preamble context under `Modules/PreCanonical`.
- Keep historical lineage inspectable without inferring modern framework or website ownership.
- Maintain deterministic canonical import paths for all archive artifacts.

## Non-Goals

- Do not treat imported records as modern module ownership decisions.
- Do not renumber historical IDs to satisfy modern contiguous execution-spec sequencing.
- Do not create runtime source or test directories for `Modules/PreCanonical`.

## Constraints

- Imported specs preserve original pre-canonical bodies, including historical terminology and examples.
- WR/WS records remain valid archive lineage here until later explicit mapping decides website or framework ownership.
- Future ownership mapping must happen through separate promoted specs.

## Expected Behavior

- Dry-run import reports deterministic artifacts without writing files.
- Apply import writes specs, reconstruction notes, context files, and idempotent implementation-log entries.
- Validators treat this module as archive lineage rather than a normal contiguous implementation queue.
- State records the concrete imported source, spec count, first imported spec, and last imported spec after apply.

## Acceptance Criteria

- The imported archive remains reproducible from the same marked source file.
- Imported specs and plans remain under `Modules/PreCanonical`.
- Context validation can proceed without requiring modern ownership decisions.

## Assumptions

- The source archive was explicitly marked by a human before import.
- Historical ID shape is meaningful lineage and should be preserved.
- Later alignment work may map selected records into modern modules or external website history.

## Archive Contract

- `S@...` blocks are imported as execution specs.
- `R@...` blocks are paired to specs by normalized `NAME:` text and preserved as reconstruction evidence.
- `P@...` blocks are preserved as contextual preamble evidence and are never imported as specs.
- Legacy IDs map to dot-separated padded numeric canonical IDs by preserving numeric, alphabetic, and hyphen suffix order.
- Imported pre-canonical specs must not be renumbered into modern modules without a later explicit alignment spec.
