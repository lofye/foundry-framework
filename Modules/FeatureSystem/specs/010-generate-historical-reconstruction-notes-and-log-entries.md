# Execution Spec: 010-generate-historical-reconstruction-notes-and-log-entries

## Purpose

Generate reconstruction notes and implementation-log entries for imported historical specs.

This spec builds on:

```text
006-require-implementation-reconstruction-notes.md
008-import-historical-spec-archive.md
009-generate-historical-module-context-docs.md
```

It closes the context-persistence loop for historical specs by ensuring they have:

```text
Modules/<Module>/outcomes/<spec-id-and-slug>.md
Modules/implementation.log
```

entries that match current validation rules.

---

## Core Principle

Historical reconstruction notes must be honest.

They should preserve what is known from archived specs, Codex results, follow-up prompts, embedded OUTPUT/RESULT sections, and current repository state, while clearly identifying inferred or uncertain details.

---

## Goals

1. Generate reconstruction notes for imported completed specs.
2. Normalize or append matching implementation-log entries.
3. Ensure imported historical specs satisfy reconstruction-note validation.
4. Preserve uncertainty and provenance.
5. Extract embedded implementation evidence from historical archive files.
6. Avoid fabricating exact implementation details when archive evidence is incomplete.

---

## Non-Goals

- Do not rewrite source code.
- Do not invent implementation files not supported by evidence.
- Do not compact decisions.
- Do not mark uncertain specs as complete unless evidence supports it.
- Do not duplicate entire archived Codex outputs into reconstruction notes.
- Do not assume a one-spec-per-file relationship in historical imports.

---

## Reconstruction Note Placement

For:

```text
Modules/<Module>/specs/<spec-id-and-slug>.md
```

create:

```text
Modules/<Module>/outcomes/<spec-id-and-slug>.md
```

Use the required reconstruction note format from spec 006.

---

## Embedded OUTPUT / RESULT Extraction

Historical archive files may contain embedded implementation transcripts, including sections such as:

```text
OUTPUT
RESULT
IMPLEMENTATION RESULT
STRICT RESULT
```

These sections may appear:

- below a spec
- between multiple specs in a single file
- appended after implementation
- alongside follow-up prompts

The reconstruction generator must:

- detect and preserve these sections as implementation evidence
- associate them with the nearest matching spec segment when deterministically possible
- extract:
  - implementation summaries
  - modified file lists
  - verification command results
  - coverage results
  - stabilization notes
  - follow-up fix notes
- avoid duplicating entire transcripts verbatim into reconstruction notes

Generated reconstruction notes should summarize embedded implementation evidence under sections such as:

```md
## Historical Implementation Evidence

## Historical Verification Evidence

## Historical Stabilization Notes
```

When association confidence is uncertain:

- mark evidence as inferred
- preserve provenance metadata
- avoid claiming exact implementation ordering

If multiple specs exist in a single historical file:

- preserve segment boundaries
- track source segment indices
- associate OUTPUT/RESULT blocks only with matching segments when confidence is sufficient

---

## Historical Provenance Language

Each generated historical reconstruction note must include language equivalent to:

```md
This reconstruction note was generated from archived pre-repository implementation records, Codex result output, embedded OUTPUT/RESULT evidence, follow-up prompts when available, and current repository state. Details marked inferred should be treated as reconstructed context rather than original implementation-session truth.
```

---

## Evidence Levels

Use one of:

```text
confirmed
inferred
unknown
```

Where useful, annotate sections:

```md
- confirmed: `src/Example/File.php` exists and aligns with archived result.
- inferred: implementation order reconstructed from current source and available follow-up prompt.
- unknown: exact original test run output unavailable.
```

---

## Reconstruction Note Structure

Generated reconstruction notes must include deterministic sections in the following order:

```md
# Implementation Plan: <spec-id-and-slug>

## Historical Provenance

## Historical Specification Summary

## Historical Implementation Evidence

## Historical Verification Evidence

## Historical Stabilization Notes

## Current Repository Alignment

## Uncertainty And Reconstruction Notes
```

Sections with no available evidence may explicitly state:

```md
No confirmed historical evidence available.
```

Do not omit required sections.

---

## Implementation Log Entries

For every imported completed spec, ensure `Modules/implementation.log` contains a canonical path entry:

```text
Modules/<Module>/specs/<spec-id-and-slug>.md
```

Rules:

- do not duplicate existing entries
- preserve existing entry order
- append imported historical entries in deterministic order if no historical order is available
- mark imported entries as historical if the log format supports notes
- otherwise capture provenance in the reconstruction note

---

## Validation Interaction

After this spec, imported completed specs should satisfy:

- spec file exists
- implementation-log entry exists
- reconstruction note exists
- reconstruction note required sections exist
- context verifies

---

## Determinism Requirements

Generation must be deterministic.

The same:

- imported archive
- evidence map
- repository state
- reconstruction inputs

must produce identical reconstruction notes and implementation-log output.

Ordering rules:

- stable section ordering
- stable evidence ordering
- stable file ordering
- stable log append ordering

No timestamps, random identifiers, or machine-specific absolute paths may appear in generated artifacts.

---

## Testing Requirements

Test:

- reconstruction note generation for imported spec
- existing reconstruction note not overwritten silently
- incomplete evidence marked explicitly
- implementation-log entry appended once
- canonical implementation-log path used
- deterministic ordering
- validation passes after generation
- embedded RESULT blocks extracted correctly
- multiple spec segments in one source file handled deterministically
- OUTPUT/RESULT evidence linked to correct reconstructed spec
- verification evidence summarized without transcript duplication
- uncertain RESULT associations marked inferred

---

## Acceptance Criteria

- imported completed specs have reconstruction notes
- imported completed specs have canonical implementation-log entries
- uncertainty is explicit
- embedded OUTPUT/RESULT evidence is incorporated into reconstruction notes when available
- no duplicate log entries
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

All commands must exit `0`.

---

## Codex Guidance

Use GPT-5.5 High.

This is context reconstruction work. Be conservative and explicit about uncertainty.

Do not fabricate exact implementation details from partial evidence.

When historical evidence conflicts with current repository state:

- preserve the historical evidence
- explicitly identify the mismatch
- avoid silently reconciling conflicting interpretations
