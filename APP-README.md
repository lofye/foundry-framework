# {{DISPLAY_NAME}}

This Foundry project was scaffolded in `{{STARTER_LABEL}}` mode.

{{STARTER_SUMMARY}}

## Working With LLMs

Start with `AGENTS.md`. It defines the repo-local workflow and command rules for AI assistants working in this app.

For meaningful feature work, maintain canonical feature context under:

- `Features/<Feature>/<feature>.spec.md`
- `Features/<Feature>/<feature>.md`
- `Features/<Feature>/<feature>.decisions.md`

These paths mean:

- `Features/<Feature>/<feature>.spec.md` → authoritative feature intent
- `Features/<Feature>/<feature>.md` → current state
- `Features/<Feature>/<feature>.decisions.md` → append-only decision history
- `Features/<Feature>/specs/*.md` → execution specs (planning artifacts, non-authoritative after implementation)
- `Features/<Feature>/specs/drafts/*.md` → draft execution specs (non-executable planning artifacts)
- `Features/<Feature>/outcomes/*.md` → implementation reconstruction notes (post-implementation artifacts)
- `Features/implementation.log` → completed execution-spec ledger

For completed active execution specs, create or update the matching reconstruction note before reporting completion. Reconstruction notes should describe what actually changed, not speculative implementation plans.

Execution spec IDs are ordered contracts within each feature. IDs must stay contiguous at every hierarchy level, skipping numbers is forbidden, and agents must stop instead of planning, implementing, promoting, or logging specs when a numeric gap exists.

Use `foundry verify context --feature=<feature> --json` as the primary machine-readable proceed/fail gate. If a feature does not have canonical context yet, create it first with `foundry context init <feature> --json`. If context verification fails, repair context before implementation.

## Feature-Localized Layout

Foundry apps are moving toward a localized structure where the feature directory is the primary context unit for LLMs:

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

Feature-specific app code and tests should live inside the owning feature directory once the localized feature layout is enabled. Shared files should contain registration glue only.
Feature-owned runtime code belongs under `Features/<Feature>/src/` and feature-owned tests belong under `Features/<Feature>/tests/`.

Use boundary verification when available:

```bash
foundry verify features --json
foundry feature:map --feature=<feature> --json
```

`Features/*` is obsolete for authored feature source. `Features/<Feature>/*` is obsolete for application feature context. Public docs still live under `docs/`; feature-owned context, code, and tests live under `Features/<Feature>/`.

Code for a Blog feature belongs at `Features/Blog/src/`.

### Feature Reconstruction Notes

Application features may include reconstruction notes under:

```text
Features/<Feature>/outcomes/
```

A reconstruction note records how a completed feature spec was implemented: files changed, runtime contracts, tests, deterministic outputs, and follow-up dependencies.

These notes help future developers and agents understand or rebuild feature behavior without needing the original chat or implementation session.

## First Run

Foundry scaffolds a project-local `foundry` launcher. If your shell does not resolve current-directory executables, use `./foundry ...` instead.

In a scaffolded app, `foundry` inspects the current project instead of loading an onboarding example. `foundry explain` with no target explains the first feature or route deterministically.
`foundry explain --json`, `foundry explain --git --json`, `foundry explain --diff --json`, and `foundry generate ... --json` also include deterministic confidence data for LLM and automation workflows.
When Git is available, `foundry generate` can warn on dirty repository state, and `--git-commit` can create an explicit post-verification commit for safe generate-owned files.

```bash
composer install
foundry
foundry explain --json
foundry explain publish-post --git --json
foundry explain --diff --json
foundry compile graph --json
foundry inspect graph --json
foundry inspect pipeline --json
foundry doctor --json
foundry generate docs --format=markdown --json
foundry generate inspect-ui --json
foundry verify graph --json
foundry verify pipeline --json
foundry verify contracts --json
php vendor/bin/phpunit -c phpunit.xml.dist
bin/phpunit-coverage --coverage-clover build/coverage/clover.xml
foundry verify coverage --min=90 --clover=build/coverage/clover.xml --json
php -S 127.0.0.1:8000 public/index.php
```

Clarification: `feature-alignment-pass` is a skill/workflow name, not a Foundry CLI command.
Use `foundry verify context --json` as the canonical CLI command for context/alignment validation.

## Starter Routes

{{ROUTE_SUMMARY}}

## Inspectability

- Generated graph docs: `docs/generated`
- Generated inspect UI: `docs/inspect-ui`
- Source definition example: `app/definitions/inspect-ui/dev.inspect-ui.yaml`
- {{AUTH_HINT}}

## Packs

Foundry can also load deterministic packs for extension work, either from disk or from the optional hosted registry.

- discover remote packs with `foundry pack search <query> --json`
- install from the hosted registry with `foundry pack install vendor/pack --json`
- install and inspect local packs with `foundry pack list --json` and `foundry inspect packs --json`
- active pack versions are tracked in `.foundry/packs/installed.json`
- hosted registry lookups are cached at `.foundry/cache/registry.json`
- installed pack files live under `Packs/*/*` and should be replaced from source rather than hand-edited
