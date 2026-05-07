# Feature Spec: feature-system

## Purpose

Define canonical framework-module governance boundaries under `Modules/` with deterministic compatibility for legacy `Features/` and `docs/features/` paths.

## Goals

- Provide deterministic `feature:list`, `feature:inspect`, `feature:map`, and `verify features` CLI surfaces.
- Treat `Modules/implementation.log` as the canonical implementation ledger path.
- Support canonical `Modules/*/specs/` and `Modules/*/plans/` in spec validation.
- Require reconstruction notes for completed framework module specs under `Modules/<Module>/plans/<id>-<slug>.md`.
- Require canonical module implementation-log references in the form `Modules/<Module>/specs/<id>-<slug>.md`.
- Keep migration-compatible behavior for legacy `docs/features/*` inputs.
- Provide deterministic prep-only historical-spec archive extraction tooling before full module import migration.
- Provide deterministic historical-spec evidence mapping that preserves legacy ordering keys, confidence levels, and supporting evidence before import.
- Tighten historical evidence boundaries so `Spec35D1` is the canonical transition anchor and historical candidates are classified by import era/action before any import step.

## Non-Goals

- Do not physically migrate all framework runtime/test code in one step.
- Do not remove legacy `docs/features/` compatibility in this step.

## Constraints

- Output ordering must remain deterministic and stable.
- Canonical and legacy duplicate definitions must emit deterministic diagnostics.
- Boundary enforcement defaults to enabled and disabled mode must emit visible warnings.

## Expected Behavior

- `feature:list` returns deterministic feature rows from canonical and legacy sources.
- `feature:inspect <feature>` returns context and directory mapping with deterministic dependency order.
- `feature:map` returns deterministic owned path maps.
- `verify features` reports boundary/duplication issues and enforcement status.
- `historical-specs:extract` scans `_import/raw-historical-specs` and writes deterministic candidate archives under `_import/historical-specs` without importing into `Modules/*`.
- `historical-specs:evidence` builds deterministic `_import/historical-specs/evidence-map.json` data (and optional markdown report) with legacy-order keys, module suggestions, confidence values, evidence-state fields, and supporting evidence-file references.
- Legacy labels such as `Spec 19FB`, `Spec 30C-2`, and `Spec 35D7JA` are parsed into stable sort keys used for deterministic candidate ordering.
- Known summary/planning files are treated as supporting evidence sources by default unless explicit extractable execution-spec headings are present.
- `verify features` enforces executable application feature-local runtime layout under `Features/<Feature>/` (`src/` and `tests/` required by default).
- `verify features` permits omitted `specs/`, `plans/`, and `docs/` directories when absent, and fails deterministically when present paths are not directories.
- `verify features` emits deterministic violations when attributable application-owned runtime/context files remain in legacy `app/features/<slug>` and `docs/features/<slug>` paths.
- Framework-module misplacement checks do not classify application features under `Features/` as framework modules unless matching `Modules/<Name>` entries exist.
- `spec:validate` validates canonical `Modules/*/specs` and `Modules/*/plans` paths.
- `spec:validate` requires reconstruction-note coverage for active framework module specs and reports deterministic violations when notes are missing or malformed.
- `spec:validate` rejects slug-style framework implementation-log references and reports deterministic canonical-path violations.
- Active-spec implementation logging uses `Modules/implementation.log` when canonical module workspace is present.
- Framework contributor docs, app-facing docs, and implementation skills align terminology and path contracts so framework-module work resolves from `Modules/*` while application-feature work resolves from `Features/*`.

## Acceptance Criteria

- Canonical `Modules/` workspace is discoverable and preferred for framework modules.
- New feature-system CLI surfaces are available and deterministic.
- Canonical/legacy duplicate detection reports `FEATURE_DUPLICATE_CANONICAL_AND_LEGACY`.
- Application feature-local runtime ownership checks are deterministic and enforce default executable layout semantics.
- Spec validation supports canonical `Modules/*` specs and plans.
- Active module specs require matching reconstruction notes; legacy `# Implementation Plan:` notes remain deterministic grandfathered artifacts during migration.
- Framework implementation-log entries are canonicalized to module spec paths and slug-style module references fail deterministic validation.
- Canonical implementation ledger path is recognized as `Modules/implementation.log`.
- Documentation and agent/skill guidance consistently encode the modules-vs-features split without implying framework modules are governed under `Features/*`.
- Historical-spec prep extraction remains explicit and non-authoritative until follow-up import specs are implemented.
- Historical ordering/evidence mapping remains explicit and non-authoritative until follow-up import specs are implemented.
- Historical evidence output distinguishes `pre_canonical`, `canonical_existing`, `ambiguous`, and `supporting_evidence` candidates with deterministic `import_action`, transition-relative metadata, and confidence-scored module inference.

## Assumptions

- Additional migration specs will progressively move more feature-owned code into localized feature directories.

## Framework Modules vs Application Features

Foundry distinguishes between:

### Framework Modules
- Implemented under `src/*`
- Organized by technical layer (Context, CLI, State, Quality, etc.)
- May span multiple capabilities
- Not required to live under `Modules/*/src/`

### Application Features
- Located under `Features/<Feature>/`
- Own:
    - context files (`<feature>.md`, `.spec.md`, `.decisions.md`)
    - `specs/`
    - `plans/`
    - optional `docs/`
    - required `src/` and `tests/` for executable features
- Default behavior is executable unless explicitly marked non-executable via deterministic feature metadata.
- Runtime and tests for feature-owned application behavior are localized under the owning feature root.

### Rule

Framework Modules are layer-organized.
Application Features are ownership-organized.
For application features, owning feature directories define the canonical runtime/test ownership boundary.
