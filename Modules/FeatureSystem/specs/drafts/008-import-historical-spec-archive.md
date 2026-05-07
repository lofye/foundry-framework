# Execution Spec: 008-import-historical-spec-archive

## Purpose

Import historical execution specs that were completed before Foundry began storing specs consistently inside the repository.

The goal is to recover durable context from archived markdown specs, Codex implementation results, and follow-up prompts without pretending the recovered records are perfect.

---

## Core Principle

Historical context should be preserved, not reinvented.

Imported historical specs must clearly distinguish:

- known historical content
- inferred mapping
- current repository state
- uncertain reconstruction details

---

## Scope

This spec introduces a deterministic import workflow for archived historical specs.

It should support importing source material from a local archive directory supplied by the developer, such as:

```text
historical-specs/
```

or another configured path.

Imported specs should be placed under the correct module:

```text
Modules/<Module>/specs/<id-and-slug>.md
```

or under drafts if implementation status is uncertain:

```text
Modules/<Module>/specs/drafts/<id-and-slug>.md
```

---

## Goals

1. Define an archive input format for historical specs and implementation outputs.
2. Import completed historical specs into canonical module spec locations.
3. Preserve original archived text where possible.
4. Mark uncertain imports explicitly.
5. Avoid overwriting existing canonical specs.
6. Produce deterministic import reports.
7. Prepare later specs to generate module docs, decision ledgers, reconstruction notes, and implementation-log entries from the imported archive.

---

## Non-Goals

- Do not rewrite current source code.
- Do not infer implementation details beyond available archive/current repo evidence.
- Do not overwrite existing specs without explicit deterministic conflict handling.
- Do not import website repo specs into the framework repo unless they belong to framework modules.
- Do not create reconstruction notes in this spec unless trivial/import metadata notes are required for validation.
- Do not compact decision files.

---

## Suggested Archive Structure

Support a simple deterministic structure such as:

```text
historical-specs/
  spec-001/
    spec.md
    codex-result.md
    followups.md
    metadata.json
  spec-002/
    spec.md
    codex-result.md
    followups.md
    metadata.json
```

`metadata.json` may include:

```json
{
  "module": "FeatureSystem",
  "spec_id": "001",
  "slug": "example-spec",
  "implemented": true,
  "source_confidence": "high"
}
```

If no metadata exists, importer may run in report-only mode and produce unmapped candidates.

---

## Import Modes

### Report Mode

```bash
php bin/foundry historical-specs:import --source=historical-specs --dry-run --json
```

Must:

- scan archive
- identify candidate specs
- detect duplicates/conflicts
- report proposed destination paths
- not modify files

### Apply Mode

```bash
php bin/foundry historical-specs:import --source=historical-specs --apply --json
```

Must:

- write imported specs to canonical locations
- avoid overwrites unless exact content matches or explicit force flag exists
- produce deterministic result summary

If the repo does not want a new command, implement this as a service plus tests and document manual import steps. A command is preferred.

---

## Imported Spec Header

Each imported historical spec should include a short provenance block near the top, compatible with existing spec metadata rules.

If internal metadata is forbidden in specs, use a normal prose section:

```md
## Historical Import Note

This spec was imported from archived pre-repository implementation records. It reflects the original archived spec as closely as possible. Any uncertainty is documented in the corresponding reconstruction note or decision ledger.
```

Do not add forbidden `id`, `parent`, or `status` metadata.

---

## Conflict Handling

If a destination spec already exists:

- exact same content: report as already imported
- different content: report conflict
- never overwrite silently

Suggested error codes:

```text
HISTORICAL_SPEC_IMPORT_CONFLICT
HISTORICAL_SPEC_IMPORT_UNMAPPED
HISTORICAL_SPEC_IMPORT_INVALID_METADATA
HISTORICAL_SPEC_IMPORT_DESTINATION_EXISTS
```

---

## Determinism Requirements

- stable archive traversal ordering
- stable report ordering
- stable destination path generation
- no timestamps in output
- no environment-specific absolute paths in deterministic JSON

---

## Testing Requirements

Test:

- dry-run import report
- apply import success
- duplicate exact match
- conflicting destination
- missing metadata
- malformed metadata
- implemented vs uncertain status placement
- deterministic JSON ordering

---

## Acceptance Criteria

- historical specs can be imported deterministically
- existing specs are not overwritten silently
- imported specs live under canonical module spec paths
- uncertain specs remain drafts or are flagged
- import report is stable and machine-readable
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

This introduces historical import machinery and must be careful about not fabricating certainty.
