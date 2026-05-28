---
name: implement-spec
description: Implement a Foundry execution spec through one strict workflow for module, feature, and pack targets. Always enforces stabilize-strict behavior.
---

# Purpose

Use this skill when:
- implementing an active execution spec to completion
- finalizing framework or app work that must end in a clean state
- executing module, feature, or pack specs through one deterministic loop

This is the canonical strict implementation skill.

Invoke it as:

```text
implement-spec <path>
```

Do not require `implement-spec-and-stabilize-strict` in the invocation name.
Strict stabilization is the default behavior of `implement-spec`.

Do not use this skill for:
- draft-spec implementation
- exploratory planning
- partial best-effort work

---

# Inputs

Expect one active execution-spec path:

- `Modules/*/specs/*.md`
- `Features/*/specs/*.md`
- `Packs/*/*/specs/*.md`

Reject:

- any `specs/drafts/*.md` path
- missing paths
- non-canonical execution-spec filenames

---

# Unified Design

The strict implementation system is one workflow with four shared layers:

1. One implementation skill: `implement-spec <path>`
2. One target classifier: derive the target from the spec path
3. One context resolver: resolve canonical local context from the target
4. One execution queue/index: list and order executable specs across all roots

The implementation loop stays unified across all target types.
Only the context-resolution rules differ by target type.

---

# Path-Derived Target Classification

Classify the target from the spec path only:

- `Modules/*/specs/*.md` => module spec
- `Features/*/specs/*.md` => feature spec
- `Packs/*/*/specs/*.md` => pack spec

Classification must be deterministic and path-derived.
Do not infer target type from prose inside the spec.

---

# Resolver Layer

Use a resolver layer centered on `SpecTargetResolver`.

`SpecTargetResolver` must produce:

- `target_type`
- `target_name`
- `target_root`
- `spec_path`
- `implementation_log_path`
- `context_paths`

Meaning:

- `target_type`: `module`, `feature`, or `pack`
- `target_name`: normalized logical name such as `ExecutionSpecSystem`, `Blog`, or `foundry/blog`
- `target_root`: owning filesystem root
- `spec_path`: canonical execution-spec path passed to `implement-spec`
- `implementation_log_path`: canonical log destination for completed active-spec entries
- `context_paths`: canonical local context files that must be read before implementation

## Resolver Examples

Module example:

```json
{
  "target_type": "module",
  "target_name": "ExecutionSpecSystem",
  "target_root": "Modules/ExecutionSpecSystem",
  "spec_path": "Modules/ExecutionSpecSystem/specs/004-multi-root-spec-discovery-and-target-resolution.md",
  "implementation_log_path": "Modules/implementation.log",
  "context_paths": [
    "Modules/ExecutionSpecSystem/execution-spec-system.spec.md",
    "Modules/ExecutionSpecSystem/execution-spec-system.md",
    "Modules/ExecutionSpecSystem/execution-spec-system.decisions.md"
  ]
}
```

Feature example:

```json
{
  "target_type": "feature",
  "target_name": "Blog",
  "target_root": "Features/Blog",
  "spec_path": "Features/Blog/specs/003-publish-workflow.md",
  "implementation_log_path": "Features/implementation.log",
  "context_paths": [
    "Features/Blog/blog.spec.md",
    "Features/Blog/blog.md",
    "Features/Blog/blog.decisions.md"
  ]
}
```

Pack example:

```json
{
  "target_type": "pack",
  "target_name": "foundry/blog",
  "target_root": "Packs/foundry/blog",
  "spec_path": "Packs/foundry/blog/specs/001-posts-rendering-and-rss.md",
  "implementation_log_path": "Packs/foundry/blog/implementation.log",
  "context_paths": [
    "Packs/foundry/blog/foundry.json",
    "Packs/foundry/blog/docs/blog.md",
    "Packs/foundry/blog/docs/blog.decisions.md"
  ]
}
```

For pack targets, include pack-local manifest and docs context in `context_paths`.
If pack context files are missing, stop and repair or create the required context before claiming completion.

---

# Context Rules By Target

The implementation loop is shared.
Only local context resolution changes.

## Module Targets

Read before implementation:

- `Modules/<Module>/<module>.spec.md`
- `Modules/<Module>/<module>.md`
- `Modules/<Module>/<module>.decisions.md`

Run context verification against the module or its mapped feature name using repository-supported commands.

## Feature Targets

Read before implementation:

- `Features/<Feature>/<feature>.spec.md`
- `Features/<Feature>/<feature>.md`
- `Features/<Feature>/<feature>.decisions.md`

Run feature-scoped context verification when available.

## Pack Targets

Read before implementation:

- `Packs/<vendor>/<pack>/foundry.json`
- `Packs/<vendor>/<pack>/docs/<pack>.md`
- `Packs/<vendor>/<pack>/docs/<pack>.decisions.md`
- the active pack spec itself

If pack-owned context docs do not yet exist, create or repair them as part of bringing the target into a compliant state.
Keep generic framework support in `src/*` only when it is reusable pack infrastructure rather than pack-specific behavior.

---

# Execution Queue And Index

Use the canonical normalized spec queue/index commands:

```bash
php bin/foundry spec:list --json
php bin/foundry spec:next --json
```

These commands must scan:

- `Modules/*/specs/*.md`
- `Features/*/specs/*.md`
- `Packs/*/*/specs/*.md`

They must emit normalized records such as:

- `id`
- `title`
- `target_type`
- `target`
- `path`
- `status`

Treat these commands as the shared queue/index layer for implementation targeting.
Do not maintain separate ad hoc discovery logic per root once these commands exist.

---

# Core Principle

This is a zero-tolerance pipeline.

If any required step fails or leaves unresolved issues:
- do not claim completion
- do not silently downgrade the failure
- report the blocking condition precisely

---

# Unified Implementation Loop

For all module, feature, and pack specs:

1. Read the active execution spec.
2. Resolve the target through `SpecTargetResolver`.
3. Read the resolved local context files.
4. Refuse draft-spec execution and unresolved context mismatch.
5. Implement code, docs, and tests inside the correct ownership boundary.
6. Run focused tests while iterating.
7. Run repository-wide validation before completion.
8. Run the full PHPUnit suite.
9. Run coverage and enforce the repository threshold.
10. Update the canonical implementation log for the resolved target.
11. Create or update the matching reconstruction note.
12. Promote or archive related spec artifacts only when the workflow or repository rules require it.

Only the resolver inputs change by target type.
The execution loop itself does not branch into separate module, feature, or pack workflows.

---

# Required Commands

## Focused Iteration

Use the smallest relevant test scope first.
Examples:

- `php vendor/bin/phpunit <focused-test-path>`
- pack-local tests such as `php vendor/bin/phpunit Packs/foundry/blog/tests`

## Spec Validation

Run:

```bash
php bin/foundry spec:validate --json
```

Must pass cleanly before claiming completion.

## Context Verification

Run the appropriate repository-local context verification for the resolved target.
Prefer target-scoped verification when the CLI supports it.

Examples:

```bash
php bin/foundry verify context --feature=<feature-name> --json
php bin/foundry verify context --json
```

If context fails:
- stop implementation work
- repair context
- re-run verification

## Feature Boundary Verification

Run when available:

```bash
php bin/foundry verify features --json
```

Prefer target-scoped verification when the CLI supports it:

```bash
php bin/foundry verify features --feature=<feature-name> --json
php bin/foundry feature:map --feature=<feature-name> --json
php bin/foundry feature:inspect <feature-name> --json
```

Boundary failures block completion.

## Full Quality Gate

Run before reporting success:

```bash
php vendor/bin/phpunit
XDEBUG_MODE=coverage php vendor/bin/phpunit --coverage-clover build/coverage/clover.xml
php bin/foundry verify coverage --min=90 --clover=build/coverage/clover.xml --json
```

If tests fail, coverage fails, or coverage drops below threshold:
- do not report success

---

# Reconstruction Note And Log Rules

After implementation:

- write the matching reconstruction note under the resolved target root's `plans/` directory
- append the implementation log entry to the resolved `implementation_log_path`

Examples:

- module spec => `Modules/<Module>/plans/<id>-<slug>.md` and `Modules/implementation.log`
- feature spec => `Features/<Feature>/plans/<id>-<slug>.md` and `Features/implementation.log`
- pack spec => `Packs/<vendor>/<pack>/plans/<id>-<slug>.md` and `Packs/<vendor>/<pack>/implementation.log`

Decision ledgers remain append-only.
Refresh decision summaries in state docs when needed instead of compacting ledger history.

---

# Ownership Guidance For This Capability

Multi-root spec discovery, classification, queueing, and implementation targeting belong to `ExecutionSpecSystem`.

The likely owning framework implementation spec should live under:

```text
Modules/ExecutionSpecSystem/specs/004-multi-root-spec-discovery-and-target-resolution.md
```

Use that path as the design anchor for this capability.
If the same owning Module, Feature, or Pack already assigned a `004-*` spec id, do not reuse or rename that existing id within that target. Place the work in the next contiguous spec id for that same Module, Feature, or Pack while preserving the same ownership and scope.

---

# Dry-Run Mode

If invoked with `dry-run`:

- do not modify files
- do not append implementation logs
- do not write repairs

Instead:

1. classify the target
2. resolve local context
3. report files that would change
4. report focused tests and full validation that would run
5. report blocking context, boundary, validation, or coverage risks

Return deterministic machine-readable output.

---

# Completion Criteria

Success requires all of the following:

- active spec implemented
- target resolved correctly
- local context resolved and clean
- ownership boundaries respected
- focused tests passed during iteration
- `php bin/foundry spec:validate --json` passed
- full PHPUnit suite passed
- coverage verification passed
- reconstruction note updated
- implementation log updated
- no unresolved blocking issues remain

If any requirement is incomplete:
- fail loudly
- report the exact unresolved condition

---

# Authority Rule

This skill must never:
- implement a draft spec
- invent a separate workflow per target root
- skip context resolution
- skip the full quality gate
- silently succeed with unresolved issues

This skill must:
- treat path-derived target resolution as authoritative
- keep one strict implementation loop across modules, features, and packs
- enforce deterministic completion evidence before claiming success
