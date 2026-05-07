# Execution Spec: 006-require-implementation-reconstruction-notes

## Purpose

Introduce mandatory implementation reconstruction notes for every completed execution spec.

Foundry already preserves module/feature context files, execution specs, decision ledgers, implementation log entries, source code, and tests. This spec adds the missing reconstruction layer: durable implementation notes under `plans/`.

These notes are not speculative project plans. They are post-implementation reconstruction records: compact, deterministic summaries of what actually changed, why the implementation shape exists, and how a future LLM or developer could faithfully reconstruct the module or feature without chat history.

---

## Core Principle

Specs define intent.

Source code defines current behavior.

Tests define executable truth.

Decision ledgers define why.

Implementation logs define completion.

Reconstruction notes define how the implementation was actually shaped.

Together, they allow a future LLM or developer to resume, audit, or rebuild a feature/module without relying on chat history.

---

## Background

Foundry’s context-persistence model currently stores:

```text
Modules/<Module>/<module>.md
Modules/<Module>/<module>.spec.md
Modules/<Module>/<module>.decisions.md
Modules/<Module>/specs/*.md
Modules/implementation.log
```

and, for application features:

```text
Features/<Feature>/<feature>.md
Features/<Feature>/<feature>.spec.md
Features/<Feature>/<feature>.decisions.md
Features/<Feature>/specs/*.md
```

The repository also has support for `plans/`, but recent implementations completed without plan files. This revealed that the repo has strong intent/history records, but inconsistent implementation reconstruction records.

---

## Problem

Without per-spec reconstruction notes, a future LLM can usually answer:

- what was intended
- what specs were completed
- what tests exist
- what commands should pass

But it may struggle to answer:

- what files/classes were introduced by a specific spec
- which runtime invariants are intentional
- which implementation choices were rejected
- what downstream specs assume from this implementation
- how to rebuild the feature/module faithfully from scratch
- whether an implementation shape is intentional or incidental

This weakens Foundry’s context-persistence goal.

---

## Goals

1. Require a reconstruction note for every completed framework execution spec.
2. Store reconstruction notes in the owning module’s `plans/` directory.
3. Support equivalent app feature notes under `Features/<Feature>/plans/`.
4. Make reconstruction notes post-implementation artifacts, not pre-implementation guesses.
5. Validate that promoted/completed framework specs have matching reconstruction notes.
6. Update implementation workflow docs and skills.
7. Update AGENTS/README/philosophy docs to explain the rule.
8. Preserve existing spec immutability and implementation-log behavior.

---

## Non-Goals

- Do not require pre-implementation project plans.
- Do not turn plans into lengthy status reports.
- Do not duplicate full source code.
- Do not duplicate the full execution spec.
- Do not replace decision ledgers.
- Do not replace implementation logs.
- Do not require reconstruction notes for draft specs.
- Do not rename `plans/` in this spec.
- Do not introduce nondeterministic generated content.

---

## Terminology

### Execution Spec

A markdown file under:

```text
Modules/<Module>/specs/*.md
Features/<Feature>/specs/*.md
```

that has been promoted out of `drafts/`.

### Draft Spec

A markdown file under:

```text
Modules/<Module>/specs/drafts/*.md
Features/<Feature>/specs/drafts/*.md
```

Draft specs do not require implementation-log entries or reconstruction notes.

### Implementation Log Entry

A line in:

```text
Modules/implementation.log
```

for framework module specs, or the relevant application feature implementation log if app-level logs are later introduced.

### Reconstruction Note

A deterministic markdown file stored under:

```text
Modules/<Module>/plans/<spec-id-and-slug>.md
Features/<Feature>/plans/<spec-id-and-slug>.md
```

that summarizes how the spec was actually implemented.

---

## Canonical File Placement

### Framework Modules

For:

```text
Modules/Marketplace/specs/003-marketplace-entitlements-and-license-activation.md
```

the matching reconstruction note must be:

```text
Modules/Marketplace/plans/003-marketplace-entitlements-and-license-activation.md
```

For:

```text
Modules/Marketplace/specs/002.001-marketplace-auth-runtime-contracts.md
```

the matching note must be:

```text
Modules/Marketplace/plans/002.001-marketplace-auth-runtime-contracts.md
```

### Application Features

For:

```text
Features/Blog/specs/001-blog-posting.md
```

the matching reconstruction note should be:

```text
Features/Blog/plans/001-blog-posting.md
```

This spec must enforce module notes immediately. App feature enforcement may be SHOULD unless the implementation also supports app validation cleanly.

---

## Required Reconstruction Note Format

Each reconstruction note must be markdown.

The first heading must match the filename without extension.

Example file:

```text
Modules/Marketplace/plans/003-marketplace-entitlements-and-license-activation.md
```

Required heading:

```md
# 003-marketplace-entitlements-and-license-activation
```

---

## Required Sections

Every reconstruction note must include these sections in this order:

```md
# <filename-without-extension>

## Spec Implemented

## Implementation Summary

## Files Introduced

## Files Modified

## Runtime Contracts

## Deterministic Outputs

## Tests Added Or Updated

## Verification Commands

## Decisions And Tradeoffs

## Reconstruction Notes

## Follow-Up Dependencies
```

Section headings must be exact.

---

## Section Requirements

### Spec Implemented

Must identify the implemented spec path.

Example:

```md
## Spec Implemented

`Modules/Marketplace/specs/003-marketplace-entitlements-and-license-activation.md`
```

### Implementation Summary

Must summarize what was actually implemented in 3–10 bullets.

### Files Introduced

Must list new files introduced by the spec. Use `None.` if none.

### Files Modified

Must list materially modified files. Use `None.` if none.

### Runtime Contracts

Must capture important behavior future agents must preserve.

Example:

```md
## Runtime Contracts

- All entitlement decisions route through `PackEntitlementResolver`.
- Entitlement-required downloads fail closed.
- Expired entitlements do not resolve as granted.
```

### Deterministic Outputs

Must list deterministic CLI/JSON/API outputs introduced or changed. Use `None.` if none.

### Tests Added Or Updated

Must list the main tests added/updated.

### Verification Commands

Must record the commands that passed before completion.

At minimum for framework specs:

```md
## Verification Commands

- `php bin/foundry spec:validate --json`
- `php bin/foundry verify context --json`
- `php bin/foundry verify features --json`
- `php bin/foundry verify contracts --json`
- `php vendor/bin/phpunit`
- `XDEBUG_MODE=coverage php vendor/bin/phpunit --coverage-clover build/coverage/clover.xml`
- `php bin/foundry verify coverage --min=90 --clover=build/coverage/clover.xml --json`
```

Add module-specific commands when relevant.

### Decisions And Tradeoffs

Must capture implementation decisions not already obvious from code. Use `None beyond the implemented spec and module decision ledger.` if none.

### Reconstruction Notes

Must describe how to rebuild the implementation faithfully.

### Follow-Up Dependencies

Must list known specs or systems that depend on this work. Use `None.` if none.

---

## Validation Rules

Update `spec:validate` or the appropriate validation service so completed/promoted framework specs require reconstruction notes.

For every spec under:

```text
Modules/<Module>/specs/*.md
```

excluding `drafts/`, validation must check:

1. matching reconstruction note exists under `plans/`
2. reconstruction note heading matches filename
3. all required sections exist
4. required sections appear in canonical order
5. draft specs do not require reconstruction notes

If app feature enforcement is implemented, apply equivalent rules to:

```text
Features/<Feature>/specs/*.md
```

Otherwise, document app feature notes as SHOULD for now.

### Failure Codes

Introduce deterministic failure codes such as:

```text
EXECUTION_SPEC_RECONSTRUCTION_NOTE_MISSING
EXECUTION_SPEC_RECONSTRUCTION_NOTE_HEADING_INVALID
EXECUTION_SPEC_RECONSTRUCTION_NOTE_SECTION_MISSING
EXECUTION_SPEC_RECONSTRUCTION_NOTE_SECTION_ORDER_INVALID
```

### JSON Output

`spec:validate --json` must include deterministic violations.

Example:

```json
{
  "code": "EXECUTION_SPEC_RECONSTRUCTION_NOTE_MISSING",
  "path": "Modules/Marketplace/specs/003-marketplace-entitlements-and-license-activation.md",
  "expected_path": "Modules/Marketplace/plans/003-marketplace-entitlements-and-license-activation.md"
}
```

---

## Historical Specs / Migration Behavior

This spec must not leave the repo invalid.

Preferred approach:

- Generate reconstruction notes for all currently promoted framework module specs as part of this implementation.
- Notes may be concise, but must include all required sections and enough information to aid reconstruction.

Acceptable alternative:

- Add explicit deterministic grandfathering for specs completed before this spec.
- Do not use hidden date-based behavior.
- Do not use silent exceptions.

The preferred approach is strongly recommended because Foundry’s goal is rebuildable context.

---

## Implementation Workflow Update

Update the strict implementation workflow so after a spec is implemented and before final completion is reported, Codex must:

1. update module/feature context files
2. append decision ledger entry if behavior changed
3. create/update matching reconstruction note in `plans/`
4. append implementation-log entry
5. run validation and test gates

Order:

```text
spec implemented
→ context updated
→ decisions updated
→ reconstruction note written
→ implementation.log appended
→ gates run
```

A spec must not be reported complete without a matching reconstruction note.

---

## Implementation Log Interaction

`Modules/implementation.log` remains the completion ledger.

The reconstruction note is not a replacement for the implementation log.

Implementation log answers:

```text
Was this spec implemented?
```

Reconstruction note answers:

```text
How was this spec implemented, and how would we rebuild it faithfully?
```

Validation should require both for promoted framework module specs.

---

## Plans Directory Semantics

Clarify that `plans/` stores post-implementation reconstruction notes unless a future spec introduces pre-implementation planning artifacts.

For now:

```text
plans/ = durable implementation reconstruction notes
```

Do not introduce a separate `implementation/` directory in this spec.

---

## Required Documentation Updates

This spec must update:

```text
AGENTS.md
APP-AGENTS.md
README.md
APP-README.md
docs/philosophy/foundry-philosophy.md
.skills/implement-spec-and-stabilize.skill.md
.skills/implement-spec-and-stabilize-strict.skill.md
```

The exact text below should be adapted only as needed to match surrounding style.

---

## AGENTS.md Addition

Add a section near the spec/implementation workflow rules:

```md
## Implementation Reconstruction Notes

Every completed framework execution spec MUST have a matching reconstruction note under the owning module's `plans/` directory.

Example:

- Spec: `Modules/Marketplace/specs/003-marketplace-entitlements-and-license-activation.md`
- Reconstruction note: `Modules/Marketplace/plans/003-marketplace-entitlements-and-license-activation.md`

Reconstruction notes are written after implementation. They are not speculative project plans. They record what actually changed so a future agent or developer can understand, audit, or rebuild the module without chat history.

A reconstruction note MUST include:

- implemented spec path
- implementation summary
- files introduced
- files modified
- runtime contracts
- deterministic outputs
- tests added or updated
- verification commands
- decisions and tradeoffs
- reconstruction notes
- follow-up dependencies

Do not report a spec complete until its reconstruction note, decision ledger updates, context updates, implementation-log entry, tests, coverage, and validation gates are all complete.

`Modules/implementation.log` answers whether a spec was implemented. The matching `plans/` file answers how it was implemented.
```

---

## APP-AGENTS.md Addition

Add a section near app feature workflow rules:

```md
## Application Feature Reconstruction Notes

Every completed application feature spec SHOULD have a matching reconstruction note under the feature's `plans/` directory.

Example:

- Spec: `Features/Blog/specs/001-blog-posting.md`
- Reconstruction note: `Features/Blog/plans/001-blog-posting.md`

For executable application features, reconstruction notes preserve implementation memory: files added, runtime contracts, tests, deterministic outputs, and follow-up dependencies.

Application agents should treat reconstruction notes as part of the feature's durable context. They are written after implementation and should describe what actually changed, not what might happen.
```

If app validation enforces this immediately, use MUST instead of SHOULD.

---

## README.md Addition

Add to the section explaining modules/specs/context persistence:

```md
### Reconstruction Notes

Foundry stores implementation reconstruction notes in each module's `plans/` directory.

Specs define what must be true. Decision ledgers explain why architectural choices were made. Implementation logs record that a spec was completed. Reconstruction notes explain how the spec was actually implemented.

This gives future agents and developers enough context to resume work, audit behavior, or rebuild a module without relying on chat history.

Example:

```text
Modules/Marketplace/specs/003-marketplace-entitlements-and-license-activation.md
Modules/Marketplace/plans/003-marketplace-entitlements-and-license-activation.md
```

For framework modules, completed specs are expected to have matching reconstruction notes.
```

---

## APP-README.md Addition

Add to the feature structure/context section:

```md
### Feature Reconstruction Notes

Application features may include reconstruction notes under:

```text
Features/<Feature>/plans/
```

A reconstruction note records how a completed feature spec was implemented: files changed, runtime contracts, tests, deterministic outputs, and follow-up dependencies.

These notes help future developers and agents understand or rebuild feature behavior without needing the original chat or implementation session.
```

If app validation enforces notes immediately, change “may include” to “include”.

---

## docs/philosophy/foundry-philosophy.md Addition

Add a section:

```md
## Context Persistence And Reconstruction

Foundry treats durable context as part of the software.

Source code tells us what the system does now. Tests tell us what behavior is executable and protected. Specs tell us what was intended. Decision ledgers tell us why choices were made. Implementation logs tell us what was completed.

Reconstruction notes complete that loop by explaining how a spec was actually implemented.

This matters because Foundry is designed for humans and LLMs working across many sessions. Chat history is temporary. Repository context is durable.

A future agent should be able to read a module's context files, specs, decisions, reconstruction notes, implementation log, source, and tests, then resume or rebuild the module with high fidelity.

The goal is not bureaucracy. The goal is continuity: a framework that remembers not only its code, but the reasoning and implementation shape that produced it.
```

---

## Skills Updates

Update both implementation skills so final completion requires a reconstruction note.

Add language equivalent to:

```md
Before reporting implementation complete:

1. Update module/feature context files.
2. Append decision ledger entries for behavior or architecture changes.
3. Create or update the matching reconstruction note under `plans/`.
4. Append the implementation log entry.
5. Run the canonical validation, test, and coverage gates.

Never mark a promoted spec complete without a matching reconstruction note.
```

Also add:

```md
The reconstruction note must describe what actually changed, including files introduced/modified, runtime contracts, deterministic outputs, tests, verification commands, decisions/tradeoffs, reconstruction notes, and follow-up dependencies.
```

---

## Likely Implementation Areas

Likely files/services:

```text
src/Context/ExecutionSpecValidationService.php
src/Context/SpecValidator.php
src/Context/ExecutionSpecCatalog.php
src/FeatureSystem/FeatureWorkspaceService.php
```

Do not hardcode Marketplace-specific paths.

Validation must work generically for:

```text
Modules/<Module>/specs/*.md
Modules/<Module>/plans/*.md
Features/<Feature>/specs/*.md
Features/<Feature>/plans/*.md
```

where applicable.

---

## Testing Requirements

Add or extend tests for:

### Validation

- promoted module spec with matching reconstruction note passes.
- promoted module spec missing reconstruction note fails.
- promoted module spec with invalid note heading fails.
- promoted module spec with missing required section fails.
- promoted module spec with sections out of order fails.
- draft module spec without reconstruction note passes.
- child spec ids like `002.001-*` resolve matching reconstruction note correctly.
- app feature spec behavior matches the chosen MUST/SHOULD rule.

### Documentation / Workflow

- skills/docs mention reconstruction notes.
- implementation log behavior remains unchanged.
- existing implementation-log validation still works.

### Determinism

- JSON violation ordering stable.
- multiple violations sorted deterministically.
- no timestamps/randomness in validation output.

---

## Acceptance Criteria

- completed framework module specs require matching reconstruction notes.
- reconstruction notes live under `Modules/<Module>/plans/`.
- required note format is validated.
- draft specs do not require notes.
- docs and skills explain the rule.
- implementation log remains required.
- existing promoted specs either receive notes or are explicitly grandfathered.
- all validation/test/coverage gates pass.

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

If the implementation touches Marketplace/MCP/Generate docs because of existing specs, also run relevant module commands such as:

```bash
php bin/foundry inspect marketplace --json
php bin/foundry verify marketplace --json
```

---

## Codex Guidance

Use GPT-5.3-Codex High.

This change touches validation rules, workflow docs, skills, context persistence philosophy, and existing spec/module artifacts.

Do not rush through existing specs by generating empty notes. Each generated reconstruction note must contain enough information to help a future agent rebuild or audit the implementation.

Concise is fine. Vacuous is not.
