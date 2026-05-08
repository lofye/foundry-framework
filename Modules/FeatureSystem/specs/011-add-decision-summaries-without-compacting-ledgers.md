# Execution Spec: 011-add-decision-summaries-without-compacting-ledgers

## Purpose

Add compact decision summaries while preserving `.decisions.md` files as immutable append-only ledgers.

This spec rejects destructive compaction of decision ledgers and instead introduces summary sections or summary files that make accumulated decisions easier for humans and LLMs to consume.

---

## Core Principle

Raw decisions are historical evidence.

Summaries are navigation aids.

Do not delete, compact, rewrite, or remove “redundant” decision ledger entries. Redundancy may be meaningful historical signal.

---

## Goals

1. Preserve every module/feature decision ledger as append-only raw history.
2. Introduce deterministic decision summaries.
3. Refresh summaries periodically, such as after every five implemented specs in a module.
4. Make summaries useful for LLM context loading.
5. Avoid destructive historical rewriting.

---

## Non-Goals

- Do not remove decision entries.
- Do not rewrite old decision entries.
- Do not compact `.decisions.md`.
- Do not infer decisions without marking them as inferred.
- Do not require summaries for draft-only modules.

---

## Summary Location

Choose one deterministic convention.

Preferred:

```text
Modules/<Module>/<module>.md
```

with a section:

```md
## Decision Summary
```

Acceptable alternative:

```text
Modules/<Module>/<module>.summary.md
```

If using separate files, update validators/context loading accordingly.

Recommended for now: put summaries in `<module>.md` to avoid adding another required file type.

---

## Summary Content

A decision summary should include:

- major architectural choices
- rejected alternatives
- current boundaries
- module invariants
- known caveats
- links/references to decision ledger entries where practical

Example:

```md
## Decision Summary

Through spec 005, Marketplace is a framework-side protocol/client module, not the hosted marketplace application. Hosted auth, payments, storage, and UI live in the website repo. Entitlement decisions are centralized through `PackEntitlementResolver`.
```

---

## Refresh Rule

After every five implemented specs in a module, agents SHOULD refresh the decision summary.

If the validator can determine this reliably, it may warn when a summary appears stale.

Do not fail hard on stale summaries in this spec unless the rule can be enforced deterministically without false positives.

---

## Validation

This spec may add non-blocking warnings for:

```text
DECISION_SUMMARY_MISSING
DECISION_SUMMARY_POSSIBLY_STALE
```

Do not make these hard errors unless current repo state is fully migrated and deterministic.

---

## Documentation Updates

Update AGENTS/skills/docs to clarify:

```md
Decision ledgers are append-only and must not be compacted. If a module accumulates many decisions, add or refresh a Decision Summary instead of rewriting history.
```

---

## Testing Requirements

Test:

- decision ledger remains append-only
- summary section detected
- stale/missing summary warning if implemented
- no hard failure for missing summaries unless explicitly configured
- deterministic warning ordering

---

## Acceptance Criteria

- decision ledgers remain immutable append-only records
- decision summaries exist or are supported
- docs instruct agents not to compact decision files
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

Use GPT-5.3-Codex Medium.

This is documentation/context workflow refinement, not a runtime architecture change.
