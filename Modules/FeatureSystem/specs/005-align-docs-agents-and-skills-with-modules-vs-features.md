# Execution Spec: 005-align-docs-agents-and-skills-with-modules-vs-features

## Purpose

Update Foundry documentation, agent instructions, README files, and implementation skills to reflect the new terminology and layout split:

- `Modules/` for Foundry framework modules
- `Features/` for downstream application features

This spec is documentation and instruction alignment after the structural behavior exists.

## Dependency

This spec must be implemented after:

- `003-separate-framework-modules-from-application-features.md`
- `004-enforce-application-feature-local-runtime-layout.md`

Do not implement this spec first.

## Terminology To Adopt

Use these terms consistently:

### Framework Module

A Foundry-owned capability/subsystem governed under:

```text
Modules/<Module>/
```

Framework module runtime may remain layer-organized under `src/*`.

### Application Feature

A downstream app-owned capability governed and implemented under:

```text
Features/<Feature>/
```

Application feature-owned runtime code must live under:

```text
Features/<Feature>/src/
```

Application feature-owned tests must live under:

```text
Features/<Feature>/tests/
```

## Files To Update

Update at minimum:

```text
AGENTS.md
APP-AGENTS.md
README.md
APP-README.md
.skills/implement-spec-and-stabilize.skill.md
.skills/implement-spec-and-stabilize-strict.skill.md
```

Also update any other repository docs that still state or imply that framework capabilities live under `Features/`.

## Required Content Changes

### AGENTS.md

Add or revise guidance so agents understand:

- this repository's framework governance artifacts live under `Modules/`
- framework runtime source remains under `src/*` unless a spec says otherwise
- agents must not move framework source into `Modules/<Module>/src/` opportunistically
- `Features/` is reserved for application features
- final quality gates must include the Clover-backed coverage verifier

### APP-AGENTS.md

Add or revise guidance so downstream app agents understand:

- application features live under `Features/<Feature>/`
- feature-owned runtime code must live under `Features/<Feature>/src/`
- feature-owned tests must live under `Features/<Feature>/tests/`
- generated projections/adapters are not the source of truth
- app agents should not create framework module directories under `Features/`

### README.md

Add concise developer-facing explanation:

- Foundry's own capabilities are Framework Modules under `Modules/`
- app/business capabilities are Application Features under `Features/`
- framework internals may remain layer-organized under `src/*`

Do not over-expand README with implementation details already covered by specs.

### APP-README.md

Explain the application feature layout and source-of-truth rule:

```text
Features/<Feature>/src/
Features/<Feature>/tests/
Features/<Feature>/specs/
Features/<Feature>/outcomes/
Features/<Feature>/docs/
```

State clearly that application feature-owned runtime code belongs inside the feature directory.

### Skills

Update strict and non-strict implementation skills so they:

- look for framework specs under `Modules/<Module>/specs/`
- look for application specs under `Features/<Feature>/specs/`
- append framework implementation entries to `Modules/implementation.log`
- do not append framework module work to `Features/implementation.log`
- preserve the canonical final gate:

```bash
php vendor/bin/phpunit
XDEBUG_MODE=coverage php vendor/bin/phpunit --coverage-clover build/coverage/clover.xml
php bin/foundry verify coverage --min=90 --clover=build/coverage/clover.xml --json
```

## Search/Replacement Safety

Do not blindly replace every occurrence of `feature` with `module`.

Only rename concepts where the subject is a Foundry framework-owned capability.

Keep `feature` where the subject is an application/business feature or an existing public command name that remains unchanged.

## Tests Required

If documentation/skills are validated by tests, update those tests.

Add tests where existing docs/agent/skill validation expects old paths, especially for:

- `Features/implementation.log`
- `Features/<FrameworkCapability>/specs/`
- framework specs under `Features/`

## Required Verification Commands

All must exit `0`:

```bash
php bin/foundry spec:validate --json
php bin/foundry verify context --json
php bin/foundry verify features --json
php bin/foundry verify contracts --json
php vendor/bin/phpunit
XDEBUG_MODE=coverage php vendor/bin/phpunit --coverage-clover build/coverage/clover.xml
php bin/foundry verify coverage --min=90 --clover=build/coverage/clover.xml --json
```

## Acceptance Criteria

- Docs consistently distinguish Framework Modules from Application Features.
- Agent instructions no longer imply framework modules live under `Features/`.
- App instructions hard-require feature-local source/test layout.
- Skills use `Modules/implementation.log` for framework work.
- No old canonical references to `Features/implementation.log` remain except migration/legacy notes.
- All required gates pass.

