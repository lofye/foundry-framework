# Foundry Framework

Foundry is a production-minded, explicit, deterministic, LLM-first PHP framework for building feature-local web apps.
Visit [FoundryFramework.org](https://foundryframework.org) for extensive documentation.

Core Foundry remains MIT-licensed and fully usable without restriction.
Explain, generate, diagnostics, trace analysis, and graph diffing remain available without a license.
The monetization system is opt-in, local-first, and isolated from core compile, inspect, verify, scaffold, and runtime flows.

It is optimized for:
- explicit contracts
- deterministic generation
- machine-readable inspection
- small safe edit surfaces
- strong verification and testing

## Getting Started

Run:

```bash
foundry
```

Foundry behaves deterministically:

- in an empty directory, it offers curated onboarding examples
- in an existing Foundry project, it inspects the current project
- `foundry explain` with no target explains the first feature or route deterministically

For meaningful framework-module work, canonical context lives in:

- `Modules/<Module>/<module>.spec.md`
- `Modules/<Module>/<module>.md`
- `Modules/<Module>/<module>.decisions.md`

These paths mean:

- `Modules/<Module>/<module>.spec.md` → authoritative module intent
- `Modules/<Module>/<module>.md` → current state
- `Modules/<Module>/<module>.decisions.md` → append-only decision history
- Keep module decision ledgers append-only; do not compact historical entries. Summarize accumulated decisions in a `## Decision Summary` section inside `Modules/<Module>/<module>.md`.
- `Modules/<Module>/specs/*.md` → execution specs (planning artifacts, non-authoritative after implementation)
- `Modules/<Module>/specs/drafts/*.md` → draft execution specs (non-executable planning artifacts)
- `Modules/<Module>/plans/*.md` → implementation reconstruction notes (post-implementation artifacts)
- `Modules/implementation.log` → completed framework execution-spec ledger
- Framework implementation-log `- spec:` entries must use canonical module spec paths (`Modules/<Module>/specs/<id>-<slug>.md`), not slug aliases.

For downstream application feature work, use:

- `Features/<Feature>/<feature>.spec.md`
- `Features/<Feature>/<feature>.md`
- `Features/<Feature>/<feature>.decisions.md`

For completed active framework execution specs, create or update the matching reconstruction note before reporting completion. Reconstruction notes describe what actually changed, not speculative implementation plans.

Execution spec IDs are ordered contracts within each feature. IDs must remain contiguous at every hierarchy level (`001`, `002`, `003`; `007.001`, `007.002`, ...), skipping numbers is forbidden, and agents must stop instead of planning, implementing, promoting, or logging specs when any numeric gap exists.

Use `foundry verify context --feature=<feature> --json` as the primary machine-readable proceed/fail gate. If canonical context is missing, create it first with `foundry context init <feature> --json`. If context verification fails, repair context before implementation.

## Modules vs Features

Foundry framework capabilities are governed as Framework Modules under `Modules/`.
Downstream business/application capabilities are governed as Application Features under `Features/`.
Framework internals may remain layer-organized under `src/*`.

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

## Feature-Localized Layout

Foundry features are moving toward a localized structure where the feature directory is the primary context unit for LLMs:

```text
Features/
  README.md

  <FeatureName>/
    <feature>.spec.md
    <feature>.md
    <feature>.decisions.md
    specs/
    plans/
    docs/
    src/
    tests/
```

Feature-specific runtime code and tests should live inside the owning feature directory once the localized feature layout is enabled. Shared framework files should contain registration glue only.

Use boundary verification when available:

```bash
foundry verify features --json
foundry feature:map --feature=<feature> --json
```

Legacy `docs/features/*`, `src/*`, and `tests/*` paths may exist during migration, but new feature work should prefer localized feature roots.

## Shell Completion

Foundry can emit deterministic completion scripts for bash and zsh:

```bash
foundry completion bash
foundry completion zsh
```

Static completion comes from the registered CLI surface, so command and subcommand suggestions stay aligned with `help --json` and CLI surface verification.

When completing `foundry implement spec <feature> <id>`, names and ids resolve from active execution specs, preferring canonical module/feature roots (`Modules/*/specs` and `Features/*/specs`) with legacy compatibility where applicable. Draft specs are excluded by default.

## Install And First Run (Packagist)

```bash
composer require lofye/foundry-framework
foundry

# or, for automation:
foundry init --example=blog-api
foundry new my-foundry-app --starter=standard --json
cd my-foundry-app
composer install

foundry
foundry explain --json
foundry compile graph --json
foundry inspect graph --json
foundry inspect pipeline --json
foundry doctor --json
foundry verify graph --json
foundry verify pipeline --json
foundry verify contracts --json
php -S 127.0.0.1:8000 public/index.php
```

## Core Workflow for LLMs

1. Read the canonical feature spec, state document, and decision ledger.
2. Run `foundry verify context --feature=<feature> --json`.
3. Repair context first if verification fails.
4. Make the smallest necessary source-of-truth changes.
5. Re-run verification and tests.

Clarification: `feature-alignment-pass` is a skill/workflow name, not a Foundry CLI command.
Use `php bin/foundry verify context --json` as the canonical CLI command for context/alignment validation.

When claiming implementation completion, use this canonical machine gate:

```bash
php vendor/bin/phpunit
XDEBUG_MODE=coverage php vendor/bin/phpunit --coverage-clover build/coverage/clover.xml
php bin/foundry verify coverage --min=90 --clover=build/coverage/clover.xml --json
```

## Reference Pointers

For deeper architecture walkthroughs, use `foundry explain <target> --deep --markdown --json`.
The explain system composes `ExplainContribution` sections through the contributor registry and related docs.

Browse runnable examples in `docs/example-applications.md` and `examples/README.md`.

Use `AGENTS.md` for the full contributor and agent workflow.
