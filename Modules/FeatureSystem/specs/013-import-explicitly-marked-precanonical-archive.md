# Execution Spec: 013-import-explicitly-marked-precanonical-archive

## Purpose

Import a manually marked, concatenated pre-canonical archive into a dedicated `Modules/PreCanonical` module.

This spec replaces fuzzy historical extraction for the pre-canonical archive with explicit human-supplied delimiters:

```text
S@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
NAME: 0A — Foundational Compiler Layer
```

```text
R@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
NAME: 0A — Foundational Compiler Layer
```

```text
P@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
NAME: 3 - Roadmap Phase 3 - Billing, Workflows, Orchestration, Search, Localization, Roles, Inspect
```

`S` means spec.

`R` means result / output / follow-up evidence.

`P` means preamble / roadmap / transition / setup context.

The importer must preserve pre-canonical history without forcing these specs into modern module ownership yet.

---

## Core Principle

Pre-canonical specs are still part of Foundry’s active lineage, but they predate the module system.

Therefore, import them into:

```text
Modules/PreCanonical/
```

This is an archive-lineage module, not a normal cohesive runtime module.

Modern modules may later reference these specs as lineage evidence. They must not be prematurely moved into modern module folders or renumbered into modern module sequences.

---

## Background

Earlier historical-import work added extraction helpers, evidence mapping, import tooling, context generation, reconstruction note generation, implementation-log generation, and extraction hardening.

However, the historical source material is conversational and inconsistent. Even hardened fuzzy extraction still requires too much review.

The archive will now be prepared manually as one concatenated file with explicit block markers.

All specs in this marked archive were implemented. They did not originally belong to modules. Module inference and lineage mapping are intentionally deferred.

All sources are known to be ChatGPT conversation exports or ChatGPT-derived text. Historical model names were not consistently recorded and should not be required.

---

## Goals

1. Parse one explicitly marked pre-canonical archive file.
2. Split `S` spec blocks deterministically.
3. Split `R` result/follow-up blocks deterministically.
4. Split `P` preamble/context blocks deterministically.
5. Pair `R` blocks to `S` blocks by normalized `NAME`.
6. Preserve `P` blocks as contextual evidence without merging them into spec bodies or result bodies.
7. Import all spec blocks into `Modules/PreCanonical/specs/`.
8. Generate reconstruction notes in `Modules/PreCanonical/outcomes/`.
9. Include paired result evidence in reconstruction notes.
10. Include relevant preamble evidence as supporting context.
11. Create or update `Modules/PreCanonical/pre-canonical.md`.
12. Create or update `Modules/PreCanonical/pre-canonical.spec.md`.
13. Create or update `Modules/PreCanonical/pre-canonical.decisions.md`.
14. Append canonical implementation-log entries to `Modules/implementation.log`.
15. Assign canonical numeric IDs using the legacy alphanumeric mapping.
16. Preserve original legacy names and source provenance.
17. Avoid modern module inference in this import pass.

---

## Non-Goals

- Do not use fuzzy extraction for this marked archive.
- Do not import pre-canonical specs into modern modules.
- Do not infer final modern module ownership.
- Do not move already-canonical `35D1+` specs.
- Do not renumber existing modern module specs.
- Do not compact decision ledgers.
- Do not require `RESULT` blocks.
- Do not require `PREAMBLE` blocks.
- Do not require `SOURCE`, `MODEL`, or `DATE` fields.
- Do not rewrite historical spec body text beyond required canonical wrapping.
- Do not treat `Modules/PreCanonical` as a normal cohesive runtime feature module.

---

## Input Format

The importer must accept a single marked archive file.

Suggested path:

```text
_import/precanonical/marked-archive.md
```

The path must be configurable.

---

## Required Block Types

### Spec Block

A spec block starts with:

```text
S@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
NAME: <legacy-id> — <description>
```

The content that follows is the original pre-canonical spec body.

### Result Block

A result block starts with:

```text
R@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
NAME: <legacy-id> — <description>
```

The content that follows is implementation output, stabilization notes, Codex result text, follow-up prompts, or other result evidence for the named spec.

### Preamble Block

A preamble block starts with:

```text
P@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
NAME: <description>
```

The content that follows is roadmap, transition, setup, preface, or contextual prose.

Preamble blocks are not specs.

Preamble blocks are not results.

Preamble blocks must be preserved as supporting context.

---

## Marker Rules

- marker line must begin with `S@`, `R@`, or `P@`
- marker line must contain only the marker prefix plus repeated `@` characters after the leading block letter
- marker must be followed by a `NAME:` line
- `NAME:` is required for every block
- block content continues until the next marker or EOF
- leading/trailing blank lines around block content may be normalized
- block body content must otherwise be preserved

---

## Required vs Optional Blocks

Only `S` blocks are required for import.

`R` blocks are optional.

`P` blocks are optional.

A spec with no paired result block must still import.

A file with no `S` blocks must fail as not importable.

---

## Name Normalization

Pair `R` blocks to `S` blocks by normalized `NAME`.

Normalization rules:

- trim whitespace
- normalize repeated spaces
- normalize em dash/en dash/hyphen surrounded by spaces to a canonical separator
- compare case-insensitively for pairing
- preserve original `NAME:` text for output

Examples that should match:

```text
0A — Foundational Compiler Layer
0A - Foundational Compiler Layer
0A – Foundational Compiler Layer
```

If two normalized spec names collide with different spec content, fail with a deterministic error.

If two result blocks match the same spec name, preserve both in deterministic source order.

---

## Preamble Association

Preamble blocks must be preserved and may be associated with nearby specs as supporting context.

Rules:

- never merge preamble text into imported spec body
- never merge preamble text into result evidence
- if a preamble appears before a spec block, associate it with the following spec as contextual evidence
- if a preamble appears between result and next spec, associate it with the following spec unless a deterministic rule says it is global
- if a preamble cannot be associated with a specific spec, preserve it as module-level pre-canonical context
- reconstruction notes may summarize associated preamble context under a dedicated section
- context files may summarize global preamble blocks as archive-era notes

Recommended reconstruction note section:

```md
## Historical Preamble Context
```

If no preamble is associated, include:

```text
No marked preamble block was associated with this spec.
```

---

## Legacy ID Extraction

Extract the legacy alphanumeric spec ID from the beginning of `NAME`.

Examples:

```text
0A — Foundational Compiler Layer        -> 0A
19FB — Spec Freeze 1.0.0                -> 19FB
30C-2 — Monetization Realignment        -> 30C-2
35D — Contexting                        -> 35D
35D-2 — Contexting Follow-up            -> 35D-2
```

If no valid leading legacy ID exists in an `S` block, fail the candidate with a deterministic error.

The importer must not invent IDs.

Preamble `NAME` values do not require legacy IDs.

Result `NAME` values must match an existing spec name after normalization.

---

## Canonical ID Mapping

Map legacy alphanumeric IDs to canonical dot-separated 3-digit IDs.

Rules:

1. Numeric segments become zero-padded 3-digit numbers.
2. Letter segments become alphabetical indexes, zero-padded to 3 digits.
3. Hyphen numeric suffixes become additional numeric segments.
4. Mixed alphanumeric labels preserve segment order.
5. IDs must sort lexically in intended historical order.

Examples:

```text
0A      -> 000.001
0B      -> 000.002
1       -> 001
19A     -> 019.001
19FB    -> 019.006.002
30C     -> 030.003
30C-2   -> 030.003.002
35D     -> 035.004
35D-2   -> 035.004.002
35D7C   -> 035.004.007.003
```

Letter mapping:

```text
A -> 001
B -> 002
C -> 003
D -> 004
...
Z -> 026
```

---

## Canonical Slug Mapping

Create a slug from the description portion of `NAME`.

Example:

```text
NAME: 0A — Foundational Compiler Layer
```

becomes:

```text
000.001-foundational-compiler-layer.md
```

Rules:

- remove the leading legacy ID and separator from the slug source
- lowercase
- normalize punctuation
- kebab-case
- collapse repeated hyphens
- trim leading/trailing hyphens
- if description is empty, use `spec-<legacy-id-normalized>`
- filenames must be deterministic

---

## Output Layout

Import into:

```text
Modules/PreCanonical/
```

Required layout:

```text
Modules/PreCanonical/
  pre-canonical.md
  pre-canonical.spec.md
  pre-canonical.decisions.md
  specs/
    000.001-foundational-compiler-layer.md
    000.002-...
  plans/
    000.001-foundational-compiler-layer.md
    000.002-...
```

Do not create runtime `src/` or `tests/` directories for `Modules/PreCanonical`.

---

## Imported Spec File Format

Each imported spec file must be a valid execution spec under current validation rules.

The heading must match the filename-only convention.

Example:

```md
# 000.001-foundational-compiler-layer

## Historical Import Note

This spec was imported from the explicitly marked pre-canonical archive.

- Legacy name: `0A — Foundational Compiler Layer`
- Legacy id: `0A`
- Canonical pre-canonical id: `000.001`
- Imported module: `PreCanonical`
- Source archive: `_import/precanonical/marked-archive.md`

## Original Pre-Canonical Spec

<original spec body>
```

The original spec body should be preserved as much as possible.

---

## Reconstruction Note Format

For each imported spec, create:

```text
Modules/PreCanonical/outcomes/<same-id-and-slug>.md
```

The reconstruction note must follow the required reconstruction note structure established by spec 006.

Required sections:

```md
# Implementation Plan: <id-and-slug>

## Historical Provenance

## Historical Specification Summary

## Historical Preamble Context

## Historical Implementation Evidence

## Historical Verification Evidence

## Historical Stabilization Notes

## Current Repository Alignment

## Uncertainty And Reconstruction Notes
```

If no matching `R` block exists, state:

```text
No matching marked result block was present in the pre-canonical archive.
```

If no associated `P` block exists, state:

```text
No marked preamble block was associated with this spec.
```

Do not fabricate result, preamble, verification, or stabilization details.

---

## Result Block Handling

For each `R` block:

- pair it to the matching `S` block by normalized `NAME`
- include summarized evidence in the reconstruction note
- preserve the full result text if feasible
- do not duplicate extremely long transcripts unnecessarily
- do not treat `R` content as a spec
- do not let future-spec references inside `R` content create additional imports

If multiple `R` blocks match the same `S` block:

- preserve deterministic source order
- combine them into the same reconstruction note
- label them as `Result Block 1`, `Result Block 2`, etc.

If an `R` block has no matching `S` block:

- fail by default
- report `orphan_result_block`
- do not silently discard it

---

## Preamble Block Handling

For each `P` block:

- preserve the block body as supporting historical context
- associate it with the following `S` block when deterministic
- otherwise preserve it as module-level context
- do not import it as a spec
- do not pair it as a result
- do not add it to implementation log
- include it in `pre-canonical.md` if global or broadly applicable
- include it in the associated reconstruction note if spec-specific

If a `P` block has the same `NAME` as an `S` block, it may be associated with that spec.

If a `P` block has a roadmap/phase name rather than a spec name, it should be treated as global or next-spec context.

---

## PreCanonical Context Files

Create or update the three context files.

### `pre-canonical.spec.md`

Purpose:

- define `Modules/PreCanonical`
- document that this is an archive-lineage module
- state that specs predate the module system
- state that modern module ownership is intentionally deferred
- define the marker format
- define the ID mapping rules
- define that imported specs should not be renumbered into modern modules

### `pre-canonical.md`

Purpose:

- summarize import status
- list imported eras/ranges
- summarize global preamble blocks
- explain how modern modules should reference pre-canonical lineage
- avoid summarizing every spec in detail if the list is very large
- include a concise index or range overview

### `pre-canonical.decisions.md`

Purpose:

- record the decision to import pre-canonical specs into `Modules/PreCanonical`
- record the decision not to infer module ownership during this pass
- record the `S` / `R` / `P` marker format
- record the ID mapping convention
- remain append-only

---

## Implementation Log Entries

Append one implementation-log entry per imported spec to:

```text
Modules/implementation.log
```

Each entry must use the canonical module spec path:

```text
Modules/PreCanonical/specs/<id-and-slug>.md
```

Rules:

- do not duplicate existing entries
- preserve existing log entries
- append in deterministic canonical ID order
- mark entries as historical/pre-canonical if the log format supports notes
- otherwise rely on imported spec and plan provenance sections

Do not create implementation-log entries for `P` blocks.

---

## CLI Command

Add or extend a command:

```bash
php bin/foundry precanonical:import   --source=_import/precanonical/marked-archive.md   --json
```

Default behavior must be report/dry-run.

Required flags:

```text
--source=<path>
--apply
--json
```

Optional flags:

```text
--force
--target-module=PreCanonical
```

Default target module must be `PreCanonical`.

### Dry Run

Must report:

- parsed spec count
- parsed result count
- parsed preamble count
- paired result count
- associated preamble count
- orphan result count
- duplicate name count
- output paths that would be written
- canonical IDs that would be assigned
- conflicts

Must not write files.

### Apply

Must write:

- specs
- plans
- context files
- implementation-log entries

---

## Conflict Handling

Fail deterministically on:

- missing `NAME`
- duplicate `S` names with different content
- orphan `R` blocks
- canonical ID collision
- target spec exists with different content and `--force` not provided
- target plan exists with different content and `--force` not provided
- malformed legacy ID in `S` block

Allow idempotent re-run when generated files already match.

If `--force` is used:

- replace only generated PreCanonical import artifacts
- never rewrite unrelated module files
- report replacements explicitly

---

## Validation Integration

After apply, existing validators must treat `Modules/PreCanonical` as a valid module.

`spec:validate` must pass for imported specs.

If PreCanonical context files are intentionally broad, validators must not require them to summarize every imported spec in detail.

---

## Determinism Requirements

- stable block parsing
- stable name normalization
- stable result pairing
- stable preamble association
- stable canonical ID mapping
- stable slug generation
- stable output ordering
- stable JSON key ordering
- no timestamps
- no absolute paths
- no environment leakage

---

## Testing Requirements

Add tests for:

### Marker Parsing

- parses one `S` block
- parses one `R` block
- parses one `P` block
- parses multiple alternating `S`, `R`, and `P` blocks
- parses all `S` blocks before all `R` blocks
- requires `NAME`
- preserves body text

### Name Pairing

- pairs `R` to `S` by exact name
- pairs across dash variants
- detects orphan result block
- detects duplicate spec names
- supports multiple result blocks for one spec

### Preamble Handling

- associates preamble before spec with following spec
- preserves roadmap preamble as module-level context when not spec-specific
- does not merge preamble into spec body
- does not merge preamble into result body
- does not create implementation-log entries for preambles

### ID Mapping

- `0A -> 000.001`
- `19A -> 019.001`
- `19FB -> 019.006.002`
- `30C-2 -> 030.003.002`
- `35D -> 035.004`
- `35D-2 -> 035.004.002`
- `35D7C -> 035.004.007.003`

### File Generation

- writes specs to `Modules/PreCanonical/specs`
- writes plans to `Modules/PreCanonical/plans`
- creates PreCanonical context files
- appends implementation-log entries once
- idempotent re-run produces no duplicate log entries

### Conflict Handling

- existing different spec blocks apply without `--force`
- `--force` replaces generated PreCanonical artifacts only
- malformed legacy ID fails cleanly

### CLI

- dry-run writes nothing
- apply writes expected files
- JSON output stable
- missing source fails deterministically

---

## Acceptance Criteria

- marked pre-canonical archive imports into `Modules/PreCanonical`
- canonical IDs are generated from legacy alphanumeric IDs
- result blocks are paired to specs by `NAME`
- preamble blocks are preserved as context
- preambles do not contaminate spec or result bodies
- specs and reconstruction notes are generated
- PreCanonical context files are created
- implementation log entries are appended once
- no modern module inference is attempted
- import is idempotent
- strict gates pass

---

## Required Verification

Run:

```bash
php bin/foundry spec:validate --json
php bin/foundry verify context --json
php bin/foundry verify features --json
php bin/foundry verify contracts --json
php vendor/bin/phpunit
XDEBUG_MODE=coverage php vendor/bin/phpunit --coverage-clover build/coverage/clover.xml
php bin/foundry verify coverage --min=90 --clover=build/coverage/clover.xml --json
```

Also run:

```bash
php bin/foundry precanonical:import --source=_import/precanonical/marked-archive.md --json
```

If the marked archive exists in the repo during implementation, also run:

```bash
php bin/foundry precanonical:import --source=_import/precanonical/marked-archive.md --apply --json
php bin/foundry spec:validate --json
php bin/foundry verify context --json
```

All applicable commands must exit `0`.

---

## Reconstruction Note

Create:

```text
Modules/FeatureSystem/outcomes/013-import-explicitly-marked-precanonical-archive.md
```

---

## Codex Guidance

Use GPT-5.5 High.

This implementation is primarily deterministic parsing and canonical import generation. Avoid fuzzy inference. The marked archive exists to remove ambiguity from extraction.

Do not infer modern modules. Do not move imported specs into modern modules. Preserve pre-canonical lineage under `Modules/PreCanonical`.
