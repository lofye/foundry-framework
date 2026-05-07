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
Modules/<Module>/plans/<spec-id-and-slug>.md
Modules/implementation.log
```

entries that match current validation rules.

---

## Core Principle

Historical reconstruction notes must be honest.

They should preserve what is known from archived specs, Codex results, follow-up prompts, and current source state, while clearly identifying inferred or uncertain details.

---

## Goals

1. Generate reconstruction notes for imported completed specs.
2. Normalize or append matching implementation-log entries.
3. Ensure imported historical specs satisfy reconstruction-note validation.
4. Preserve uncertainty and provenance.
5. Avoid fabricating exact implementation details when archive evidence is incomplete.

---

## Non-Goals

- Do not rewrite source code.
- Do not invent implementation files not supported by evidence.
- Do not compact decisions.
- Do not mark uncertain specs as complete unless evidence supports it.
- Do not duplicate entire archived Codex outputs into reconstruction notes.

---

## Reconstruction Note Placement

For:

```text
Modules/<Module>/specs/<spec-id-and-slug>.md
```

create:

```text
Modules/<Module>/plans/<spec-id-and-slug>.md
```

Use the required reconstruction note format from spec 006.

---

## Historical Provenance Language

Each generated historical reconstruction note must include language equivalent to:

```md
This reconstruction note was generated from archived pre-repository implementation records, Codex result output, follow-up prompts when available, and current repository state. Details marked inferred should be treated as reconstructed context rather than original implementation-session truth.
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

## Testing Requirements

Test:

- reconstruction note generation for imported spec
- existing reconstruction note not overwritten silently
- incomplete evidence marked explicitly
- implementation-log entry appended once
- canonical implementation-log path used
- deterministic ordering
- validation passes after generation

---

## Acceptance Criteria

- imported completed specs have reconstruction notes
- imported completed specs have canonical implementation-log entries
- uncertainty is explicit
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

Use GPT-5.3-Codex High.

This is context reconstruction work. Be conservative and explicit about uncertainty.
