# Execution Spec: 009-generate-historical-module-context-docs

## Purpose

Create or repair module context files from imported historical specs and archived implementation records.

This spec builds on:

```text
008-import-historical-spec-archive.md
```

It ensures each module with imported historical specs has usable durable context:

```text
Modules/<Module>/<module>.md
Modules/<Module>/<module>.spec.md
Modules/<Module>/<module>.decisions.md
```

---

## Core Principle

Imported history must become navigable module context.

A future LLM should be able to read a module’s context files and understand what the module is for, what state it is in, what decisions shaped it, and which historical specs contributed to it.

---

## Goals

1. Ensure every module with imported specs has the three canonical context files.
2. Populate missing context files from imported historical records.
3. Update existing context files without destroying current content.
4. Append decision entries for historical imports.
5. Clearly mark inferred or uncertain historical details.
6. Preserve decision ledgers as append-only raw history.

---

## Non-Goals

- Do not compact decision files.
- Do not remove old decisions.
- Do not rewrite source code.
- Do not pretend inferred historical data is certain.
- Do not import app feature docs unless explicitly in archive metadata.

---

## Required Context Files

For each affected module:

```text
Modules/<Module>/<module>.md
Modules/<Module>/<module>.spec.md
Modules/<Module>/<module>.decisions.md
```

Example:

```text
Modules/Marketplace/marketplace.md
Modules/Marketplace/marketplace.spec.md
Modules/Marketplace/marketplace.decisions.md
```

---

## Context File Roles

### `<module>.md`

Current state and working context.

Must include or update:

- purpose
- current state
- implemented specs
- active boundaries
- next steps
- known historical import caveats

### `<module>.spec.md`

Current module contract.

Must include or update:

- goals
- non-goals
- runtime boundaries
- deterministic contracts
- validator/CLI surfaces where relevant

### `<module>.decisions.md`

Append-only decision ledger.

Must append entries for:

- historical import performed
- uncertain mappings
- recovered decisions
- reconstruction caveats

Do not edit existing decision entries except to fix formatting if validation requires it.

---

## Historical Import Marking

Use explicit wording for imported/inferred context:

```md
Historical import note: this section was reconstructed from archived specs, Codex implementation results, follow-up prompts, and current repository state. Details marked inferred should be treated as lower-confidence historical reconstruction.
```

---

## Determinism Requirements

- stable module ordering
- stable spec ordering
- stable generated section ordering
- no timestamps unless the project already uses deterministic date fields supplied by archive metadata
- no absolute local archive paths in committed docs

---

## Testing Requirements

Test:

- missing module context files are created
- existing context files are updated without destructive rewrite
- decision ledger entries are appended
- uncertain/inferred imports are marked
- deterministic ordering
- validation passes after context generation/import

---

## Acceptance Criteria

- imported historical specs have module-level context
- context files remain valid and consumable
- decision ledgers remain append-only
- uncertainty is explicit
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

Do not overwrite valuable context. Append, merge, and mark uncertainty.
