# Feature: feature-system

## Purpose

- Record the implemented state of canonical framework-module and application-feature workspace boundaries.

## Decision Summary

- Refreshed Through Spec: `014-canonical-app-feature-roots-without-legacy-layout`
- FeatureSystem keeps module and feature decision ledgers append-only and non-destructive.
- Decision history is summarized in module state (`## Decision Summary`) rather than by rewriting `.decisions.md`.
- `spec:validate` surfaces deterministic non-blocking warnings for missing or possibly stale module decision summaries.
- Historical extraction now distinguishes explicit spec roots from ordinary section headings, recaps, and result/output commentary.
- Explicit pre-canonical imports preserve marked archive lineage in `Modules/PreCanonical` without inferring modern module ownership.
- App feature layout makes a clean no-legacy break: new app feature source/context belongs under `Features/<Feature>/`, top-level `Features/`, `Modules/`, and `Packs/` must exist in fresh apps, obsolete `app/features/` app source fails verification, and obsolete app context under `docs/features/<feature>/` fails verification while authored framework docs under `docs/` remain canonical public documentation.

## Current State

- `feature:list` returns deterministic application feature rows from canonical `Features/<Feature>/` sources.
- `feature:inspect <feature>` returns context and directory mapping with deterministic dependency order.
- `feature:map` returns deterministic owned path maps.
- `verify features` reports boundary and duplication issues with explicit enforcement status.
- `spec:validate` validates canonical `Modules/*/specs` and `Modules/*/plans` paths.
- Active-spec implementation logging uses `Modules/implementation.log` when canonical module workspace is present.
- Canonical `Modules/` workspace is discoverable and preferred for framework modules.
- New feature-system CLI surfaces are available and deterministic.
- Framework-module duplicate detection reports deterministic issues when a module is represented under both `Modules/<Module>/` and `Features/<Module>/`.
- Spec validation supports canonical `Modules/*` specs and plans.
- Canonical implementation ledger path is recognized as `Modules/implementation.log`.
- Application feature layout validation enforces executable `Features/<Feature>/src/` and `Features/<Feature>/tests/` ownership by default.
- Optional `specs/`, `plans/`, and `docs/` directories are validated when present and may be omitted when absent.
- Application feature legacy ownership leaks are reported deterministically (`app/features/` runtime artifacts and `docs/features/<slug>` app context files).
- Fresh app scaffolding, feature generation, context initialization, source scanning, graph compilation, feature execution, and feature verification now use `Features/<Feature>/` as the app feature source root.
- Generated apps include empty top-level `Features/`, `Modules/`, and `Packs/` roots.
- Framework module duplication under both `Modules/<Module>/` and `Features/<Module>/` is reported deterministically without misclassifying application features that exist only under `Features/`.
- Framework and scaffold documentation now consistently distinguish Framework Modules (`Modules/*`) from Application Features (`Features/*`).
- Agent guides and implementation skills now route framework execution-spec work to `Modules/*` and `Modules/implementation.log` while keeping app feature-local guidance under `Features/*`.
- Documentation and agent/skill contracts no longer describe framework module governance as canonical under `Features/*`.
- `spec:validate` now enforces reconstruction-note coverage for active module specs with deterministic violations for missing notes, invalid reconstruction headings, missing required sections, and section-order drift.
- Legacy module `plans/` files using `# Implementation Plan:` remain deterministic grandfathered records while new or updated module notes use strict reconstruction-note sections.
- `Modules/implementation.log` framework entries are normalized to canonical module spec paths (`Modules/<Module>/specs/<id>-<slug>.md`) where deterministic mapping exists.
- `spec:validate` now reports deterministic `EXECUTION_SPEC_IMPLEMENTATION_LOG_PATH_NOT_CANONICAL` violations when framework log entries use slug-style spec references.
- `historical-specs:extract` now provides deterministic prep-only archive extraction from `_import/raw-historical-specs` into `_import/historical-specs/candidate-XXX` bundles (`spec.md`, `source.md`, `metadata.json`).
- `historical-specs:evidence` now provides deterministic ordering/evidence mapping for historical candidates, including legacy-order keys, confidence/evidence markers, supporting evidence files, and optional git cross-reference metadata.
- `historical-specs:evidence` builds deterministic `_import/historical-specs/evidence-map.json` data and optional markdown report with legacy-order keys, module suggestions, confidence values, evidence-state fields, and supporting evidence-file references.
- Legacy labels such as `Spec 19FB`, `Spec 30C-2`, and `Spec 35D7JA` are parsed into stable sort keys used for deterministic candidate ordering.
- `_import/historical-specs/evidence-map.json` and optional `_import/historical-specs/evidence-map.md` can now be generated through explicit write mode while keeping default command behavior non-mutating.
- Historical extraction candidates now track source segment indexes and optional RESULT/FOLLOWUPS detection metadata for multi-spec source files.
- Historical extraction candidates now include deterministic `emission_reason`, `candidate_quality`, rejected-root diagnostics, and result association confidence.
- The extractor suppresses common section fragments (`must:`, `Architecture`, `Implementation`, `Final polish`, continuation prose) as standalone candidates while preserving them inside the nearest valid source segment.
- Result/output-only historical content is emitted as supporting evidence with the transcript preserved in `result.md`, not as an active spec candidate.
- Historical summary/planning files are treated as supporting evidence sources by default and are not imported as ordinary specs unless explicit extractable execution-spec headings are present.
- `historical-specs:evidence` now shares the hardened root-detection semantics and does not independently resurrect rejected section fragments as importable candidates.
- Historical evidence mapping now treats `Spec35D1` as the canonical transition anchor and emits deterministic candidate-era classification (`pre_canonical`, `canonical_existing`, `ambiguous`, `supporting_evidence`) with explicit import actions (`import`, `link_existing`, `review`, `ignore_supporting`).
- Evidence-map candidates now include deterministic transition-relative metadata, canonical-existing linking/review behavior, and confidence-scored module inference evidence/alternatives to keep pre-canonical module assignment reviewable.
- Evidence-map top-level output now includes deterministic `canonical_transition` and per-era `counts` fields for downstream historical import boundary enforcement.
- `historical-specs:import` now provides deterministic report/apply import from archive candidate directories containing `spec.md` plus `metadata.json`.
- Historical import writes completed candidates to `Modules/<Module>/specs/<id>-<slug>.md` and uncertain candidates to `Modules/<Module>/specs/drafts/<id>-<slug>.md`.
- Historical import reports unmapped candidates, malformed/invalid metadata, exact duplicates, and destination conflicts with stable status and error-code fields.
- Historical import reports are deterministic, repository-relative, timestamp-free, and machine-readable.
- Historical import apply mode does not overwrite existing canonical specs silently; exact matches are idempotent, conflicting destinations are refused, and explicit force mode is required before replacement.
- Imported historical specs receive canonical execution-spec headings and a prose historical import note while preserving archived spec text below the note.
- `historical-specs:context` now provides deterministic report/apply module context generation for modules that contain imported historical specs.
- Historical context generation creates missing canonical module state/spec/decision files and updates existing files without destructive rewrite.
- Historical imported specs are documented in module context with explicit uncertainty markers.
- Imported historical specs have module-level context with explicit caveats.
- Missing module context files are created deterministically.
- Decision ledger entries remain append-only.
- Historical context generation appends decision-ledger reconstruction entries and keeps inferred or draft-only import caveats explicit.
- `historical-specs:reconstruct` now provides deterministic report/apply reconstruction-note and implementation-log generation for completed imported historical specs.
- Imported completed specs have reconstruction notes.
- Imported completed specs have canonical implementation-log entries.
- Embedded OUTPUT/RESULT evidence is incorporated into reconstruction notes when available.
- No duplicate log entries are generated for imported historical specs.
- Historical reconstruction notes include explicit provenance, evidence levels, embedded RESULT/OUTPUT summaries, current repository alignment, and uncertainty sections.
- Website-owned historical specs such as `*WS.md` are classified as supporting/ignored evidence and skipped by framework import.
- Website-owned historical specs are excluded from downstream framework context, reconstruction-note, and implementation-log generation.
- `spec:validate` now emits non-blocking `DECISION_SUMMARY_MISSING` and `DECISION_SUMMARY_POSSIBLY_STALE` warnings for module state files while keeping decision ledgers append-only.
- `precanonical:import` now parses explicitly marked pre-canonical `S`, `R`, and `P` blocks from a single archive file with report-only default behavior.
- Pre-canonical import maps legacy alphanumeric IDs such as `0A`, `19FB`, `30C-2`, and `35D7C` into padded dot-separated canonical IDs for `Modules/PreCanonical/specs/`.
- Pre-canonical result blocks are paired to specs by normalized `NAME:` text, preamble blocks are preserved as associated or global context, and orphan results or malformed legacy IDs fail deterministically.
- Pre-canonical apply mode writes specs, reconstruction notes, PreCanonical context files, and idempotent `Modules/implementation.log` entries without creating runtime source or test directories for `Modules/PreCanonical`.

## Open Questions

- Shared-glue versus feature-owned runtime boundary classification depth remains open.
- Full physical migration sequencing for framework-owned source/test files remains open.

## Next Steps

- Keep public docs and examples aligned with canonical `Features/<Feature>/` app feature paths as follow-up specs change surrounding workflows.
- Expand boundary-violation classification depth in follow-up execution specs.
- Continue incremental source/test localization through promoted execution specs.
- Evaluate follow-up specs to migrate grandfathered legacy module plan documents into strict reconstruction-note format.
- Use generated historical module context as the review surface before follow-up specs generate reconstruction notes or implementation-log entries from imported history.
- Import explicitly marked pre-canonical archives only through `precanonical:import` and preserve modern module ownership mapping for later explicit alignment specs.
