# Execution Spec: 007-normalize-implementation-log-canonical-spec-paths

## Purpose

Normalize `Modules/implementation.log` so every framework implementation entry references the canonical repository-relative spec path.

This fixes legacy entries that still use module slugs such as:

```text
feature-system/005-align-docs-agents-and-skills-with-modules-vs-features.md
```

instead of canonical module spec paths such as:

```text
Modules/FeatureSystem/specs/005-align-docs-agents-and-skills-with-modules-vs-features.md
```

This spec also tightens implementation-log validation so future entries cannot regress to slug-based or pre-Modules naming.

---

## Core Principle

`Modules/implementation.log` is a completion ledger.

A completion ledger must point to the same immutable spec identity used by:

- the filesystem
- `spec:validate`
- reconstruction notes
- module docs
- developer review
- future LLM context loading

For framework modules, the canonical spec identity is:

```text
Modules/<Module>/specs/<spec-id-and-slug>.md
```

---

## Goals

1. Normalize all existing `Modules/implementation.log` entries to canonical spec paths.
2. Update validators so future implementation-log entries must use canonical framework spec paths.
3. Preserve log ordering and historical completion meaning.
4. Do not rewrite spec files except where necessary to document the new rule.
5. Update AGENTS/skills/docs if they still show slug-style implementation-log examples.
6. Ensure `spec:validate --json` reports deterministic violations for non-canonical log references.

---

## Non-Goals

- Do not change implemented spec IDs.
- Do not renumber specs.
- Do not move specs.
- Do not create reconstruction notes unless required by the already-implemented reconstruction-note rule.
- Do not normalize application feature logs unless app-level logs already exist and are in scope.
- Do not compact, summarize, or delete implementation-log history.

---

## Required Canonical Format

Every framework implementation-log entry must include or resolve to a canonical spec path:

```text
Modules/<Module>/specs/<spec-id-and-slug>.md
```

Examples:

```text
Modules/FeatureSystem/specs/005-align-docs-agents-and-skills-with-modules-vs-features.md
Modules/Marketplace/specs/003-marketplace-entitlements-and-license-activation.md
Modules/McpServer/specs/002-mcp-plan-generation-and-validation.md
```

Invalid examples:

```text
feature-system/005-align-docs-agents-and-skills-with-modules-vs-features.md
marketplace/003-marketplace-entitlements-and-license-activation.md
Modules/Marketplace/003-marketplace-entitlements-and-license-activation.md
Features/Marketplace/specs/003-marketplace-entitlements-and-license-activation.md
```

---

## Existing Log Migration

Update `Modules/implementation.log` in place.

Rules:

- preserve entry order
- preserve completion dates/statuses if present
- preserve human notes if present
- replace only the non-canonical spec identifier/path portion
- do not remove entries
- do not merge entries
- do not infer completion for specs without existing entries

If an existing entry cannot be mapped confidently, keep it and add a deterministic validation failure rather than inventing a path.

---

## Validation Rules

Update implementation-log validation so for every promoted framework spec:

```text
Modules/<Module>/specs/*.md
```

there must be a matching implementation-log entry using that exact canonical path.

Validation must fail if the log uses a legacy slug path.

Suggested failure code:

```text
EXECUTION_SPEC_IMPLEMENTATION_LOG_PATH_NOT_CANONICAL
```

Example JSON violation:

```json
{
  "code": "EXECUTION_SPEC_IMPLEMENTATION_LOG_PATH_NOT_CANONICAL",
  "path": "Modules/implementation.log",
  "entry": "feature-system/005-align-docs-agents-and-skills-with-modules-vs-features.md",
  "expected": "Modules/FeatureSystem/specs/005-align-docs-agents-and-skills-with-modules-vs-features.md"
}
```

If an implemented spec has no entry:

```text
EXECUTION_SPEC_IMPLEMENTATION_LOG_MISSING
```

must continue to be used.

---

## Documentation Updates

Update any examples in:

```text
AGENTS.md
APP-AGENTS.md
README.md
APP-README.md
.skills/*
docs/policies/codex-reasoning-policy.md
```

that imply implementation-log entries may use module slugs.

Add or adapt this rule:

```md
Framework implementation-log entries MUST reference canonical spec paths:

`Modules/<Module>/specs/<spec-id-and-slug>.md`

Do not use module slugs such as `feature-system/005-...`.
```

---

## Testing Requirements

Add or update tests for:

- canonical implementation-log entry passes
- slug-style implementation-log entry fails
- missing implementation-log entry still fails
- normalized existing log entries satisfy validation
- JSON violation ordering remains deterministic
- child spec IDs such as `002.001-*` work
- no false positives for draft specs

---

## Acceptance Criteria

- all existing `Modules/implementation.log` entries use canonical spec paths
- validators reject legacy slug-style entries
- docs/skills show canonical examples only
- `php bin/foundry spec:validate --json` exits `0`
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

This is a deterministic cleanup and validator-tightening task. Do not broaden it into historical import or reconstruction-note generation.
