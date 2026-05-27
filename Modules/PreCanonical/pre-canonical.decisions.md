# Decisions: pre-canonical

### Decision: Preserve Explicit Pre-Canonical Archive Markers

Timestamp: 2026-05-08T11:45:00-04:00

**Context**
- The imported source archive uses explicit `S`, `R`, and `P` markers to distinguish specifications, result evidence, and preamble context.

**Decision**
- Preserve the marked archive under `Modules/PreCanonical` and use marker type plus normalized `NAME:` text as the only pairing authority.

**Reasoning**
- The pre-canonical archive predates modern module ownership, so preserving lineage is safer than inferring current module placement.

**Alternatives Considered**
- Infer modern modules during import.
- Discard result or preamble blocks.
- Import preambles as specs.

**Impact**
- Imported specs remain deterministic historical artifacts with reconstruction notes carrying paired evidence and context.

**Spec Reference**
- Modules/FeatureSystem/specs/013-import-explicitly-marked-precanonical-archive.md

### Decision: Map Legacy IDs Without Renumbering

Timestamp: 2026-05-08T11:45:00-04:00

**Context**
- Legacy IDs combine numeric, alphabetic, and hyphen suffix segments such as `19FB`, `30C-2`, and `35D7C`.

**Decision**
- Convert legacy IDs into padded dot-separated canonical IDs while preserving segment order.

**Reasoning**
- The mapping keeps lexical ordering aligned with intended historical ordering without inventing modern spec identities.

**Alternatives Considered**
- Allocate new contiguous modern module IDs.
- Preserve raw legacy IDs in filenames.

**Impact**
- Imported filenames are validator-compatible and stable across reruns.

**Spec Reference**
- Modules/FeatureSystem/specs/013-import-explicitly-marked-precanonical-archive.md

### Decision: Record Concrete Imported Range In State

Timestamp: 2026-05-08T11:45:00-04:00

**Context**
- The generated state file records the concrete imported pre-canonical archive range from `_import/pre-canonical-specs.md` after apply.

**Decision**
- Record that `Modules/PreCanonical` contains imported pre-canonical archive specs from `_import/pre-canonical-specs.md` and covers 85 spec artifacts from `000.001-foundational-compiler-layer` through `035-generate-system-end-to-end-explain-driven-pack-aware`.

**Reasoning**
- The broad module spec defines the archive contract, while state should describe the actual imported archive contents without making modern ownership claims.

**Alternatives Considered**
- Keep state generic and omit the imported count and range.
- Promote the concrete range into the canonical module spec.

**Impact**
- Future agents can see the actual imported range while preserving the spec as a durable archive-lineage contract.

**Spec Reference**
- Modules/FeatureSystem/specs/013-import-explicitly-marked-precanonical-archive.md
