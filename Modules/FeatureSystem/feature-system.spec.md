# Feature Spec: feature-system

## Purpose

Define canonical framework-module governance boundaries under `Modules/` and canonical downstream application feature boundaries under `Features/<Feature>/`.

## Goals

- Provide deterministic `feature:list`, `feature:inspect`, `feature:map`, and `verify features` CLI surfaces.
- Treat `Modules/implementation.log` as the canonical implementation ledger path.
- Support canonical `Modules/*/specs/` and `Modules/*/plans/` in spec validation.
- Require reconstruction notes for completed framework module specs under `Modules/<Module>/outcomes/<id>-<slug>.md`.
- Require canonical module implementation-log references in the form `Modules/<Module>/specs/<id>-<slug>.md`.
- Enforce canonical application feature context and runtime placement under `Features/<Feature>/`.
- Provide deterministic prep-only historical-spec archive extraction tooling before full module import migration.
- Provide deterministic historical-spec evidence mapping that preserves legacy ordering keys, confidence levels, and supporting evidence before import.
- Tighten historical evidence boundaries so `Spec35D1` is the canonical transition anchor and historical candidates are classified by import era/action before any import step.
- Provide deterministic historical-spec import from reviewed archive bundles into canonical module spec or draft paths without silently overwriting existing specs.
- Provide deterministic historical module context generation for modules that contain imported historical specs.
- Provide deterministic historical reconstruction-note and implementation-log generation for completed imported historical specs.
- Provide deterministic explicitly marked pre-canonical archive import into a dedicated `Modules/PreCanonical` archive-lineage module without inferring modern module ownership.
- Exclude website-owned historical specs such as `*WS.md` from framework import, context, reconstruction, and implementation-log generation.

## Non-Goals

- Do not physically migrate all framework runtime/test code in one step.
- Do not preserve obsolete `app/features/*` or application-context `docs/features/*` layouts as valid app feature sources.

## Constraints

- Output ordering must remain deterministic and stable.
- Obsolete application feature source/context paths must emit deterministic diagnostics.
- Boundary enforcement defaults to enabled and disabled mode must emit visible warnings.

## Expected Behavior

- `feature:list` returns deterministic application feature rows from canonical `Features/<Feature>/` sources.
- `feature:inspect <feature>` returns context and directory mapping with deterministic dependency order.
- `feature:map` returns deterministic owned path maps.
- `verify features` reports boundary/duplication issues and enforcement status.
- `historical-specs:extract` scans `_import/raw-historical-specs` and writes deterministic candidate archives under `_import/historical-specs` without importing into `Modules/*`.
- Historical extraction candidates track source segment indexes and optional RESULT/FOLLOWUPS detection metadata for multi-spec source files.
- Historical extraction uses hardened root detection so explicit spec headings and legacy `Foundry-Spec-*` filenames produce candidates, while recap prose, section fragments, and result-only content do not become active specs.
- Historical extraction metadata includes deterministic emission reasons, candidate quality, rejected root-signal diagnostics, and result association confidence.
- Historical extraction candidates include deterministic `emission_reason`, `candidate_quality`, rejected-root diagnostics, and result association confidence.
- The extractor suppresses common section fragments (`must:`, `Architecture`, `Implementation`, `Final polish`, continuation prose) as standalone candidates while preserving them inside the nearest valid source segment.
- Result/output-only historical content is emitted as supporting evidence with the transcript preserved in `result.md`, not as an active spec candidate.
- `historical-specs:evidence` builds deterministic `_import/historical-specs/evidence-map.json` data (and optional markdown report) with legacy-order keys, module suggestions, confidence values, evidence-state fields, and supporting evidence-file references.
- Historical evidence mapping treats `Spec35D1` as the canonical transition anchor and emits deterministic candidate-era classification (`pre_canonical`, `canonical_existing`, `ambiguous`, `supporting_evidence`) with explicit import actions (`import`, `link_existing`, `review`, `ignore_supporting`).
- Evidence-map top-level output includes deterministic `canonical_transition` and per-era `counts` fields for downstream historical import boundary enforcement.
- `historical-specs:import` scans archive candidate directories containing `spec.md` and optional `metadata.json`, reports unmapped or invalid metadata deterministically, writes completed imports under `Modules/<Module>/specs/`, writes uncertain imports under `Modules/<Module>/specs/drafts/`, and refuses conflicting destinations unless explicit force handling is requested.
- Imported historical specs receive a canonical execution-spec heading and prose historical import note while preserving archived source text below the import note.
- `historical-specs:context` scans imported historical specs, creates missing module context files, updates existing module context without destructive rewrite, appends decision-ledger reconstruction entries, and marks inferred or uncertain historical details explicitly.
- Historical imported specs are documented in module context with explicit uncertainty markers.
- Imported historical specs have module-level context with explicit caveats.
- Missing module context files are created deterministically.
- Decision ledger entries remain append-only.
- `historical-specs:reconstruct` scans completed imported historical specs, creates missing reconstruction notes, appends canonical `Modules/implementation.log` entries once, summarizes embedded OUTPUT/RESULT evidence, and preserves uncertainty explicitly.
- `precanonical:import` parses explicitly marked `S`, `R`, and `P` archive blocks from a single source file, maps legacy alphanumeric IDs to padded canonical IDs, and reports deterministic PreCanonical output paths in dry-run mode.
- `precanonical:import --apply` writes imported specs, reconstruction notes, PreCanonical context files, and implementation-log entries under `Modules/PreCanonical` without creating runtime source or test directories.
- Pre-canonical result blocks pair to specs only by normalized `NAME:` text, preamble blocks remain contextual evidence, orphan results and malformed legacy IDs fail deterministically, and modern module inference is intentionally deferred.
- `spec:validate` emits non-blocking decision-summary warnings (`DECISION_SUMMARY_MISSING`, `DECISION_SUMMARY_POSSIBLY_STALE`) for module state files while keeping decision ledgers append-only.
- Imported completed specs have reconstruction notes.
- Imported completed specs have canonical implementation-log entries.
- Embedded OUTPUT/RESULT evidence is incorporated into reconstruction notes when available.
- No duplicate log entries are generated for imported historical specs.
- Website-owned historical specs such as `*WS.md` are classified as supporting/ignored evidence and skipped by framework import.
- Legacy labels such as `Spec 19FB`, `Spec 30C-2`, and `Spec 35D7JA` are parsed into stable sort keys used for deterministic candidate ordering.
- Known summary/planning files are treated as supporting evidence sources by default unless explicit extractable execution-spec headings are present.
- Evidence mapping respects hardened extraction boundaries and does not resurrect rejected section fragments as importable candidates.
- `historical-specs:evidence` shares the hardened root-detection semantics and does not independently resurrect rejected section fragments as importable candidates.
- `verify features` enforces executable application feature-local runtime layout under `Features/<Feature>/` (`src/` and `tests/` required by default).
- `verify features` permits omitted `specs/`, `plans/`, and `docs/` directories when absent, and fails deterministically when present paths are not directories.
- `verify features` emits deterministic violations when application-owned runtime/context files remain in obsolete `app/features/<slug>` and application-context `docs/features/<slug>` paths.
- Fresh app scaffolding, feature generation, context initialization, source scanning, graph compilation, feature execution, and feature verification use `Features/<Feature>/` as the app feature source root.
- Framework-module misplacement checks do not classify application features under `Features/` as framework modules unless matching `Modules/<Name>` entries exist.
- `spec:validate` validates canonical `Modules/*/specs` and `Modules/*/plans` paths.
- `spec:validate` requires reconstruction-note coverage for active framework module specs and reports deterministic violations when notes are missing or malformed.
- `spec:validate` rejects slug-style framework implementation-log references and reports deterministic canonical-path violations.
- `spec:validate` reports deterministic decision-summary warnings for module state files without failing validation.
- Active-spec implementation logging uses `Modules/implementation.log` when canonical module workspace is present.
- Framework contributor docs, app-facing docs, and implementation skills align terminology and path contracts so framework-module work resolves from `Modules/*` while application-feature work resolves from `Features/*`.

## Acceptance Criteria

- Canonical `Modules/` workspace is discoverable and preferred for framework modules.
- New feature-system CLI surfaces are available and deterministic.
- Obsolete app feature source/context detection reports deterministic boundary violations.
- Application feature-local runtime ownership checks are deterministic and enforce default executable layout semantics.
- Fresh app scaffolding and feature workflow commands consistently create, discover, compile, execute, and verify app features from `Features/<Feature>/`.
- Spec validation supports canonical `Modules/*` specs and plans.
- Active module specs require matching reconstruction notes; legacy `# Implementation Plan:` notes remain deterministic grandfathered artifacts during migration.
- Framework implementation-log entries are canonicalized to module spec paths and slug-style module references fail deterministic validation.
- Canonical implementation ledger path is recognized as `Modules/implementation.log`.
- Documentation and agent/skill guidance consistently encode the modules-vs-features split without implying framework modules are governed under `Features/*`.
- Historical-spec prep extraction remains explicit and non-authoritative until follow-up import specs are implemented.
- Historical ordering/evidence mapping remains explicit and non-authoritative until follow-up import specs are implemented.
- Historical evidence output distinguishes `pre_canonical`, `canonical_existing`, `ambiguous`, and `supporting_evidence` candidates with deterministic `import_action`, transition-relative metadata, and confidence-scored module inference.
- Historical import reports remain deterministic, repository-relative, timestamp-free, and machine-readable.
- Historical extraction and evidence mapping preserve legitimate multi-spec historical files while suppressing common subsection fragments such as `must:`, `Architecture`, and continuation prose.
- Historical import apply mode does not overwrite existing canonical specs silently and routes uncertain implementation status to drafts.
- Historical context generation creates or repairs canonical `Modules/<Module>/<module>.md`, `Modules/<Module>/<module>.spec.md`, and `Modules/<Module>/<module>.decisions.md` files for modules with imported historical specs.
- Historical context generation preserves existing context content and decision history while appending bounded historical-import sections and entries.
- Historical reconstruction generation creates `Modules/<Module>/outcomes/<id-and-slug>.md` notes for completed imported specs and appends missing canonical `Modules/implementation.log` entries without duplication.
- Historical reconstruction notes include explicit provenance, evidence levels, embedded RESULT/OUTPUT summaries, repository-alignment notes, and uncertainty sections.
- Explicitly marked pre-canonical archives can be imported into `Modules/PreCanonical` with deterministic canonical IDs, paired result evidence, preserved preamble context, generated context files, and idempotent implementation-log entries.
- Website-owned historical specs (`*WS.md`) are excluded from framework import and downstream context/reconstruction/log generation.

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
