### Decision: adopt canonical Features workspace with migration-safe compatibility

Timestamp: 2026-05-03T23:15:00-04:00

**Context**

- The repository previously used `docs/features/*` as the only feature-doc workspace.
- The feature-system execution spec requires canonical `Features/` ownership with deterministic migration compatibility.

**Decision**

- Implement canonical `Features/` discovery and CLI inspection/verification surfaces.
- Keep legacy `docs/features/*` readable while preferring canonical paths when both exist.
- Treat `Features/implementation.log` as canonical implementation-ledger location when canonical workspace is present.

**Reasoning**

- Canonical co-location reduces cross-repository lookup cost for humans and agents.
- Compatibility avoids destabilizing existing workflows during incremental migration.
- Deterministic diagnostics make duplication and enforcement drift explicit instead of hidden.

**Alternatives Considered**

- Keep `docs/features/*` as permanent canonical path.
- Hard-cut migration with no compatibility bridge.
- Delay CLI and validation support until a full repository migration.

**Impact**

- Feature workspace inspection and boundary verification now support canonical and legacy layouts.
- Spec validation accepts canonical `Features/*` execution spec and plan paths.
- Implementation-log path semantics now recognize canonical `Features/implementation.log`.

**Spec Reference**

- Goals
- Expected Behavior
- Acceptance Criteria

### Decision: allow migration-phase state details that exceed the current canonical spec wording

Timestamp: 2026-05-03T23:28:00-04:00

**Context**

- The Current State documents concrete implementation details (diagnostic codes, canonical/legacy compatibility behavior, and migration notes).
- Some of those details are more specific than the current spec text and can appear as state-spec divergence during alignment checks.

**Decision**

- Keep the Current State details as implemented behavior and treat them as an explicit migration-phase refinement.
- Use this decision entry as the supporting reference for state claims that are more specific than the current spec wording.

**Reasoning**

- The state document should remain truthful about what is actually implemented.
- Recording the divergence explicitly is safer than deleting implemented details or over-broadening the spec contract prematurely.

**Alternatives Considered**

- Remove specific state claims so alignment appears strict but less informative.
- Expand the spec immediately with every implementation detail.

**Impact**

- Alignment tooling can treat the documented divergence as intentional and decision-backed.
- Current State retains implementation-useful detail during incremental migration.

**Spec Reference**

- Constraints
- Expected Behavior
- Assumptions

### Decision: document migration-phase state claims as explicit divergence references

Timestamp: 2026-05-03T23:35:00-04:00

**Context**

- Current State includes migration-phase implementation claims that can be more specific than existing spec phrasing.

**Decision**

- Deterministic `feature:list`, `feature:inspect <feature>`, `feature:map`, and `verify features` CLI surfaces are available.
- `Features/implementation.log` is used when canonical workspace is present.
- `spec:validate` supports canonical `Features/*/specs` and `Features/*/plans`.
- Legacy `docs/features/*` inputs remain readable during migration.
- Canonical and legacy duplicate definitions produce deterministic diagnostics.
- Boundary enforcement defaults to enabled and disabled mode emits visible warnings.

**Reasoning**

- Keeping explicit migration-phase details in both state and decisions preserves truthful implementation context while allowing intentional divergence tracking.

**Alternatives Considered**

- Remove migration-phase implementation details from Current State.
- Delay canonical workspace reporting until a full physical migration is complete.

**Impact**

- Decision ledger now explicitly references the implemented migration-phase state claims.
- Alignment tooling can treat these claims as decision-backed during incremental migration.

**Spec Reference**

- Goals
- Expected Behavior
- Acceptance Criteria

### Decision: migrate framework governance workspace from Features to Modules with deterministic compatibility guards

Timestamp: 2026-05-06T12:05:00-04:00

**Context**

- Execution spec `003-separate-framework-modules-from-application-features` requires framework-owned governance directories to move from `Features/` to `Modules/`.
- Framework context/spec/plan/log resolution and validation services still treated `Features/` as the canonical root for framework governance.

**Decision**

- Move framework governance directories and implementation log to `Modules/*` and `Modules/implementation.log`.
- Update context/spec/planning resolution, validation, and feature verification services to discover canonical framework module governance from `Modules/*`.
- Keep deterministic compatibility for legacy `Features/*` and `docs/features/*` paths while emitting deterministic violations for misplaced framework modules under `Features/` when `Modules/` is present.

**Reasoning**

- Separating framework module governance from application feature ownership removes namespace ambiguity and aligns with the module-vs-feature contract.
- Deterministic compatibility reduces migration risk while preserving strict failure semantics for incorrect canonical placement.

**Alternatives Considered**

- Keep `Features/` as canonical and defer migration.
- Hard-remove all legacy compatibility in the same step.

**Impact**

- Framework governance canonical root is now `Modules/`.
- Validation and resolver surfaces now support `Modules/*` canonical placement and canonical `Modules/implementation.log` discovery.
- Misplaced framework module directories remaining under `Features/` become deterministic verification failures when `Modules/` exists.

**Spec Reference**

- Core Rule
- Required Layout
- Migration Requirements
- Resolver And Validator Updates
- Legacy Compatibility Rule
- Deterministic Error Requirements

### Decision: enforce application feature-local runtime layout under Features root

Timestamp: 2026-05-06T13:52:12-04:00

**Context**

- Execution spec `004-enforce-application-feature-local-runtime-layout` requires `Features/<Feature>/` to be reserved for application/business features with deterministic local runtime ownership.
- Existing verification behavior still allowed executable features with missing runtime/test directories and did not deterministically detect some legacy ownership leaks.

**Decision**

- Enforce executable application feature layout under `Features/<Feature>/` by default:
  - require canonical root context files
  - require `src/` and `tests/` for executable features
  - permit omitted `specs/`, `plans/`, and `docs/` only when absent, and fail when present paths are non-directories
- Allow planning-only features to omit `src/` and `tests/` only with explicit `feature.json` override (`"executable": false`).
- Reject attributable legacy application ownership leaks from `app/features/<slug>` runtime files and `docs/features/<slug>` context files.
- Treat `Features/<Name>` entries as framework-module duplication violations only when a matching `Modules/<Name>` directory exists, avoiding misclassification of application features.

**Reasoning**

- Enforcing deterministic feature-local runtime ownership keeps application behavior, tests, and context together for LLM-safe bounded edits.
- Explicit non-executable opt-out preserves strict defaults while supporting planning-only artifacts without hidden conventions.
- Duplicate-only module misplacement detection reduces false positives after framework module migration to `Modules/*`.

**Alternatives Considered**

- Continue allowing executable features without `src/`/`tests/` and rely on soft guidance.
- Require placeholder `specs/`/`plans/`/`docs/` directories even when empty.
- Flag all PascalCase `Features/*` directories as misplaced framework modules when `Modules/` exists.

**Impact**

- `verify features --json` now emits deterministic application-layout violations and legacy ownership diagnostics.
- Scaffold verification coverage now explicitly guards against shipping framework module directories under app `Features/`.
- Feature-system tests now encode the new runtime-localization contract and deterministic failure modes.

**Spec Reference**

- Core Rule
- Required Application Feature Layout
- Validator Requirements
- Scaffold Requirements
- CLI Requirements
- Tests Required

### Decision: align framework and app instructions with modules-vs-features split

Timestamp: 2026-05-06T14:10:00-04:00

**Context**

- Execution spec `005-align-docs-agents-and-skills-with-modules-vs-features` requires docs, agent instructions, and implementation skills to match the implemented `Modules/*` (framework) and `Features/*` (application) split.
- Several guidance files still used legacy `docs/features/*` references for canonical framework workflow and did not clearly enforce app feature-local runtime/test placement.

**Decision**

- Align framework contributor docs to treat `Modules/*` as canonical framework-module context and `Modules/implementation.log` as the framework execution ledger.
- Align app-facing docs/instructions to treat `Features/*` as canonical app feature context and enforce feature-local runtime/tests under `Features/<Feature>/src` and `Features/<Feature>/tests`.
- Update strict and non-strict implementation skills to distinguish framework-module and application-feature spec/log paths explicitly.

**Reasoning**

- Clear terminology and path contracts reduce agent drift and prevent framework work from being logged or reasoned about as app feature work.
- Keeping app instructions explicit about localized runtime/test ownership protects feature-boundary integrity.

**Alternatives Considered**

- Keep legacy docs wording and rely on implicit migration context.
- Update only AGENTS files and leave skill contracts unchanged.

**Impact**

- Framework docs, app docs, and implementation skills now communicate consistent module-vs-feature governance semantics.
- Scaffolded app guidance now explicitly reinforces feature-local runtime/test ownership and avoids framework-module directory creation under app `Features/`.

**Spec Reference**

- Purpose
- Required Content Changes
- Skills
- Acceptance Criteria

### Decision: enforce module reconstruction notes with deterministic legacy grandfathering

Timestamp: 2026-05-07T13:55:00-04:00

**Context**

- Execution spec `006-require-implementation-reconstruction-notes` requires durable post-implementation reconstruction notes under `Modules/<Module>/plans/`.
- The repository already contained many module plan files with legacy `# Implementation Plan:` headings and non-reconstruction sections.
- Immediate strict revalidation of all legacy plan files would invalidate the repository without migration support.

**Decision**

- Extend `spec:validate` to require reconstruction-note coverage for every active framework module spec.
- Introduce deterministic violations for missing note files, invalid reconstruction headings, missing required sections, and out-of-order required sections.
- Accept legacy module plan files that start with `# Implementation Plan:` as explicit grandfathered artifacts during migration.
- Generate missing module plan/reconstruction files for active FeatureSystem, Marketplace, and StateStore specs so promoted specs have matching notes.

**Reasoning**

- Requiring matching note files immediately closes the largest reconstruction-context gap for active module specs.
- Deterministic grandfathering preserves repository validity while avoiding hidden date-based exceptions.
- Keeping strict section/ordering checks for new reconstruction-note headings provides enforceable quality for forward work.

**Alternatives Considered**

- Rewrite every historical module plan file into strict reconstruction-note format in this same change.
- Delay enforcement entirely until a future full-history migration.
- Use timestamp/date rules to exempt old notes.

**Impact**

- `spec:validate` now encodes module reconstruction-note coverage as a first-class contract.
- Framework docs, scaffold docs, philosophy text, and implementation skills now describe `plans/` as post-implementation reconstruction memory.
- Future promoted module specs fail validation if no matching reconstruction note exists.

**Spec Reference**

- Goals
- Validation Rules
- Failure Codes
- Historical Specs / Migration Behavior
- Required Documentation Updates
- Skills Updates

### Decision: normalize framework implementation-log spec references to canonical module paths

Timestamp: 2026-05-07T15:05:00-04:00

**Context**

- Execution spec `007-normalize-implementation-log-canonical-spec-paths` requires `Modules/implementation.log` references to match canonical module spec paths.
- Historical framework log entries used slug-style references (for example `feature-system/005-...`) that were readable but not path-canonical.
- Existing validation only checked presence matching legacy slug references for active specs.

**Decision**

- Normalize deterministic slug-style framework entries in `Modules/implementation.log` to canonical spec paths (`Modules/<Module>/specs/<id>-<slug>.md`).
- Treat canonical module spec paths as the required framework implementation-log identity for module active specs.
- Add deterministic validator failure `EXECUTION_SPEC_IMPLEMENTATION_LOG_PATH_NOT_CANONICAL` when module log coverage exists only through slug-style references.
- Update module `spec:log-entry` generation so module entries emit canonical module paths.

**Reasoning**

- Canonical module paths align implementation-log identity with filesystem source of truth, reconstruction notes, and reviewer navigation.
- Deterministic validation prevents regressions without introducing ambiguous migration heuristics.
- Keeping non-module legacy behavior unchanged in this spec limits scope and avoids accidental app-level contract drift.

**Alternatives Considered**

- Keep slug references permanently and rely on alias matching only.
- Require canonical-path rewrite for all log scopes (including app/legacy) in one step.
- Accept both canonical and slug formats indefinitely for modules.

**Impact**

- `Modules/implementation.log` now uses canonical module spec references for existing normalized entries.
- `spec:validate` now distinguishes missing coverage from non-canonical module coverage.
- Module log-entry suggestions/writes now reinforce canonical path discipline.

**Spec Reference**

- Purpose
- Required Canonical Format
- Existing Log Migration
- Validation Rules
- Acceptance Criteria

### Decision: add deterministic historical-spec archive extraction helper before module import

Timestamp: 2026-05-07T15:15:00-04:00

**Context**

- Execution spec `007.001-historical-spec-archive-extraction-helper` requires a prep-only workflow to extract likely historical execution specs from messy source notes before implementing import/migration specs.
- `_import/historical-specs/` exists as a prepared target path and requires deterministic candidate output contracts.

**Decision**

- Add a dedicated `historical-specs:extract` CLI command with deterministic `--source`, `--target`, and `--dry-run` behavior.
- Implement extraction through a feature-system service that scans sorted `.md`/`.txt` source files, detects likely spec boundaries, preserves original extracted text, emits cleaned candidate spec text, and writes best-effort metadata.
- Keep this workflow prep-only: do not import candidates into `Modules/*`, do not append module implementation logs for historical specs, and do not generate reconstruction notes during extraction.

**Reasoning**

- Separating extraction from import reduces migration risk and keeps historical parsing heuristics isolated from module ownership mutations.
- Deterministic candidate numbering and output shape allows repeatable review and follow-up tooling without hidden side effects.

**Alternatives Considered**

- Skip extraction tooling and perform manual archival splitting.
- Combine extraction and module import in one command.
- Emit absolute-path metadata tied to local machine paths.

**Impact**

- Historical-spec prep now has a deterministic machine command and candidate artifact contract.
- Follow-up import specs can consume normalized candidate bundles without reimplementing messy parsing logic.
- Existing module/runtime ownership contracts remain unchanged in this step.

**Spec Reference**

- Goal
- Requirements
- Determinism
- Testing

### Decision: add deterministic historical ordering and evidence map before import

Timestamp: 2026-05-07T16:20:00-04:00

**Context**

- Execution spec `007.002-historical-spec-ordering-and-evidence-map` requires explicit ordering/evidence preparation between candidate extraction and module import.
- Historical sources include mixed legacy labels, summary/planning documents, multi-spec files, and optional RESULT/OUTPUT sections.

**Decision**

- Add `historical-specs:evidence` as the deterministic prep command for ordering and evidence-map generation.
- Generate `_import/historical-specs/evidence-map.json` (and optional markdown report) with explicit confidence/evidence fields, supporting evidence file references, and stable candidate ordering.
- Parse legacy labels into deterministic order keys (for example `Spec 19FB` -> `019.F.B`, `Spec 35D7JA` -> `035.D.007.J.A`) and fallback safely for unknown labels.
- Support anchors (`--anchors`) and optional offline git cross-reference (`--with-git`) while preserving unknown/inferred evidence states explicitly.

**Reasoning**

- Import sequencing should rely on explicit evidence and uncertainty markers, not hidden assumptions.
- Separating extraction from evidence ordering keeps each phase bounded and reviewable.
- Deterministic map output enables repeatable follow-up import workflows and auditing.

**Alternatives Considered**

- Fold full evidence mapping into `historical-specs:extract`.
- Skip explicit order-key parsing and sort by filename only.
- Treat summary/planning files as normal specs by default.

**Impact**

- Historical candidates now have a machine-readable evidence and ordering surface before import.
- Summary/planning artifacts are preserved as supporting sources instead of silently becoming imported specs.
- Future import specs can consume one deterministic evidence-map contract.

**Spec Reference**

- Goals
- New Concept: Historical Evidence Map
- Legacy Ordering Rules
- Known Anchor: Numeric Canonical IDs
- Source Summary Files
- Git Evidence Cross-Reference
