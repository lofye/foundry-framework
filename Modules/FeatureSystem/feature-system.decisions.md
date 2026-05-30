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

- Execution spec `006-require-implementation-reconstruction-notes` requires durable post-implementation reconstruction notes under `Modules/<Module>/outcomes/`.
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

### Decision: tighten historical import boundary and module inference using Spec35D1 anchor

Timestamp: 2026-05-07T17:08:00-04:00

**Context**

- Execution spec `007.003-tighten-historical-import-boundary-and-module-inference` requires historical evidence mapping to separate pre-canonical import candidates from canonical-era existing specs.
- Prior historical evidence mapping captured ordering and confidence but did not enforce a canonical transition boundary anchored at `Spec35D1`.

**Decision**

- Treat `Spec35D1` as the canonical transition anchor by default, while allowing deterministic override through `_import/historical-specs/import-anchors.json`.
- Classify each candidate segment into `pre_canonical`, `canonical_existing`, `ambiguous`, or `supporting_evidence` and emit deterministic import actions (`import`, `link_existing`, `review`, `ignore_supporting`).
- Require canonical-era candidates to link to existing canonical specs when discoverable and fall back to deterministic review status when no match is found.
- Add explicit module-inference evidence and alternatives for pre-canonical candidates so uncertain mappings stay reviewable rather than silently promoted.

**Reasoning**

- The corrected transition anchor prevents accidental re-import of already-canonical specs as historical artifacts.
- Explicit era/action metadata gives follow-up import tooling a safe deterministic boundary without requiring immediate destructive migration steps.
- Confidence-scored inference with alternatives preserves uncertainty and keeps module assignment auditable.

**Alternatives Considered**

- Continue using a later transition assumption (`Spec35D7C`) and infer canonical boundaries implicitly.
- Treat all historical candidates uniformly and defer boundary logic entirely to a future import command.
- Force module assignment even when evidence is weak.

**Impact**

- `historical-specs:evidence --json` now includes deterministic top-level transition/count fields and candidate-level era/action/module-inference boundary metadata.
- Multi-segment historical files can now be evaluated per segment for import/link/review behavior without relying on filename identity alone.
- Future historical import specs can consume evidence-map output directly to avoid importing canonical-era contracts.

**Spec Reference**

- Purpose
- Goals
- New Concept: Historical Era
- Canonical Transition Anchor
- Candidate Metadata Additions
- Canonical Existing Candidate Handling
- Import Boundary Output

### Decision: import reviewed historical archive candidates without fabricating certainty

Timestamp: 2026-05-07T17:06:25-04:00

**Context**

- Execution spec `008-import-historical-spec-archive` requires deterministic import from historical archive bundles into canonical module spec paths.
- Historical candidate evidence can be incomplete, malformed, or uncertain, and existing canonical specs must not be overwritten silently.

**Decision**

- Add `historical-specs:import` as a report/apply command backed by a FeatureSystem archive importer service.
- Treat archive directories containing `spec.md` as candidate bundles and require valid `metadata.json` with module, padded spec id, slug, optional implemented status, and optional source confidence before import.
- Route `implemented: true` imports to active `Modules/<Module>/specs/` and uncertain/non-implemented imports to `Modules/<Module>/specs/drafts/`.
- Render imported specs with canonical execution-spec headings plus a prose historical import note, preserving archived spec text below that note.
- Report unmapped, invalid metadata, exact duplicate, and conflict cases deterministically with repository-relative paths and stable error codes.

**Reasoning**

- Import should preserve available evidence while keeping uncertainty visible.
- Requiring metadata prevents the importer from inventing module mappings or implementation status.
- Draft placement for uncertain candidates protects active spec validation and avoids claiming historical completion without evidence.
- Exact-content duplicate detection supports idempotent reruns, while conflict reporting prevents silent overwrite of current canonical contracts.

**Alternatives Considered**

- Infer module/spec metadata from evidence-map fields during import.
- Import uncertain candidates directly as active specs with warning notes.
- Keep import as manual copy steps without a command.

**Impact**

- Historical archive import now has deterministic report and apply surfaces.
- Follow-up reconstruction/log-generation specs can consume imported specs without re-solving basic destination and conflict handling.
- Current source code remains unchanged except for importer/CLI/test surfaces.

**Spec Reference**

- Goals
- Import Modes
- Imported Spec Header
- Conflict Handling
- Determinism Requirements
- Testing Requirements

### Decision: generate module context from imported historical specs without rewriting history

Timestamp: 2026-05-07T20:38:36-04:00

**Context**

- Execution spec `009-generate-historical-module-context-docs` requires modules with imported historical specs to have canonical state, spec, and decision context files.
- Imported historical records may be inferred, draft-only, or incomplete, and existing module context/decision history must not be overwritten.

**Decision**

- Add `historical-specs:context` as a report/apply command backed by a FeatureSystem historical module context generator.
- Detect imported historical specs only when they match the importer-produced shape: canonical execution-spec heading followed immediately by a `Historical Import Note`.
- Create missing module context files with deterministic sections and explicit historical caveats.
- Update existing module context by appending bounded historical sections and grounding bullets while preserving existing content.
- Append a deterministic decision-ledger entry for historical context generation and avoid duplicate entries on rerun.

**Reasoning**

- Context generation should make imported history navigable without fabricating certainty.
- Shape-based imported-spec detection prevents prose examples from being treated as imported history.
- Bounded section updates preserve existing module context while making recovered historical specs visible to future agents.
- Deterministic placeholder timestamps in generated historical decision entries avoid nondeterministic generated docs while satisfying ledger validation.

**Alternatives Considered**

- Treat any spec mentioning `Historical Import Note` as imported history.
- Rewrite entire module context files from generated summaries.
- Generate module docs only manually without a command surface.

**Impact**

- Modules with imported historical specs can receive canonical context files through a deterministic command.
- Existing decision ledgers remain append-only.
- Inferred or uncertain historical imports stay marked for review before later reconstruction-note or implementation-log generation.

**Spec Reference**

- Goals
- Context File Roles
- Historical Import Marking
- Determinism Requirements
- Testing Requirements

### Decision: generate historical reconstruction notes and canonical log entries conservatively

Timestamp: 2026-05-07T21:40:43-04:00

**Context**

- Execution spec `010-generate-historical-reconstruction-notes-and-log-entries` requires completed imported historical specs to receive reconstruction notes and canonical implementation-log entries.
- Historical evidence can include embedded OUTPUT/RESULT sections, partial verification transcripts, and file references, but full original implementation sessions may be unavailable.

**Decision**

- Add `historical-specs:reconstruct` as a report/apply command backed by a FeatureSystem historical reconstruction generator.
- Target only completed imported historical specs under active `Modules/<Module>/specs/*.md`; draft imports remain excluded.
- Generate missing `Modules/<Module>/outcomes/<id-and-slug>.md` notes with historical provenance, evidence summaries, verification/stabilization sections, repository alignment, and uncertainty notes.
- Preserve existing reconstruction notes instead of overwriting them silently.
- Append missing canonical `Modules/implementation.log` entries exactly once in deterministic spec-path order.
- Classify website-owned historical specs such as `*WS.md` as supporting/ignored evidence and skip them during framework import.

**Reasoning**

- Completed imported specs must satisfy validation without pretending that incomplete historical evidence is fully known.
- Excluding drafts prevents uncertain historical specs from being marked complete prematurely.
- Summarizing embedded evidence protects against copying large transcripts while preserving useful implementation, verification, and stabilization signals.
- Idempotent log-entry generation keeps repeated apply runs safe.
- Website specs belong to the website repository, so importing them into framework modules would pollute framework context with out-of-scope application/product work.

**Alternatives Considered**

- Overwrite existing reconstruction notes with generated versions.
- Treat draft imports as completed historical specs.
- Copy full embedded RESULT/OUTPUT transcripts into reconstruction notes.
- Import `*WS.md` records and mark them uncertain inside framework context.

**Impact**

- Imported completed historical specs can satisfy reconstruction-note and implementation-log validation.
- Historical evidence remains explicit, summarized, and uncertainty-marked.
- Future follow-up specs can build on generated reconstruction artifacts without re-solving log idempotency or evidence extraction.
- Website-owned historical records remain visible as ignored/supporting evidence without becoming framework module specs.

**Spec Reference**

- Goals
- Embedded OUTPUT / RESULT Extraction
- Historical Provenance Language
- Implementation Log Entries
- Acceptance Criteria

### Decision: add module decision summaries as non-destructive context guidance

Timestamp: 2026-05-08T09:15:00-04:00

**Context**

- Execution spec `011-add-decision-summaries-without-compacting-ledgers` requires better decision-history readability without rewriting append-only ledgers.
- Existing instructions strongly preserved append-only decision files but lacked deterministic validator guidance for summary freshness.

**Decision**

- Keep `.decisions.md` ledgers append-only and non-destructive.
- Add deterministic `spec:validate` warning codes `DECISION_SUMMARY_MISSING` and `DECISION_SUMMARY_POSSIBLY_STALE` for module state files.
- Standardize module state summaries under `## Decision Summary` with a `Refreshed Through Spec: <id-slug>` marker to support deterministic staleness checks.
- Keep decision-summary checks non-blocking in this migration phase.

**Reasoning**

- Summary sections improve human/LLM context loading while preserving raw historical evidence.
- Non-blocking warnings avoid destabilizing existing modules that have not yet adopted summaries.
- A deterministic refresh marker allows stable stale-summary detection without mutating ledger history.

**Alternatives Considered**

- Compact or rewrite decision ledgers directly.
- Introduce hard failures for missing summaries immediately.
- Store summaries in separate required summary files for every module.

**Impact**

- Validation now surfaces decision-summary guidance without failing strict spec-validation gates.
- Contributor docs and skills now direct agents to refresh summaries instead of compacting decisions.
- FeatureSystem module state now includes a Decision Summary refreshed through spec 011.

**Spec Reference**

- Core Principle
- Goals
- Summary Location
- Refresh Rule
- Validation
- Documentation Updates

### Decision: harden historical extraction around credible spec roots

Timestamp: 2026-05-08T10:30:00-04:00

**Context**

- Execution spec `012-extraction-boundary-and-root-detection-hardening` requires historical extraction to preserve legitimate multi-spec files while reducing phantom candidates from section headings and recap prose.
- Prior extraction behavior treated too many contract-like or spec-reference lines as new candidate boundaries.

**Decision**

- Treat explicit spec headings, execution-spec headings, and legacy `Foundry-Spec-*` filename fallback as the valid candidate-root sources.
- Suppress common section fragments and embedded prior-spec references as candidate roots while keeping them in the surrounding source segment.
- Emit result/output-only content as supporting evidence instead of active spec candidates.
- Add deterministic candidate metadata for `emission_reason`, `candidate_quality`, rejected root signals, and result association confidence.
- Align the evidence mapper with the hardened extractor semantics so it does not recreate rejected fragments.

**Reasoning**

- The historical archive contains legitimate multi-spec files, so candidate count alone is not a correctness measure.
- Root detection needs stronger evidence than headings like `must:` or prose references such as `Spec 19D established...`.
- Review metadata lets humans and agents understand why a candidate exists without rereading every raw historical file.

**Alternatives Considered**

- Hard-code an expected candidate count for the 71-file archive.
- Require manual delimiters in historical files.
- Leave evidence mapping permissive while only tightening extraction.

**Impact**

- Historical extraction produces cleaner candidate folders for downstream import review.
- Result/output transcripts remain preserved in sidecar files.
- Weak and supporting candidates default to review or ignore-supporting behavior rather than import.

**Spec Reference**

- Core Principle
- Root Spec Detection Model
- Anti-Root / Section Fragment Detection
- Result / Output Handling
- Candidate Emission Reasons
- Candidate Quality Classification

### Decision: import explicitly marked pre-canonical archives into PreCanonical

Timestamp: 2026-05-08T11:45:00-04:00

**Context**

- Execution spec `013-import-explicitly-marked-precanonical-archive` requires importing a manually marked pre-canonical archive where `S`, `R`, and `P` markers remove ambiguity that the historical extractor cannot safely infer.
- Pre-canonical records predate the current module system, so assigning them to modern modules during import would be speculative.

**Decision**

- Add `precanonical:import` as a report-first CLI command with explicit `--apply`, optional `--force`, and default `--target-module=PreCanonical`.
- Import marked `S` blocks as valid execution specs under `Modules/PreCanonical/specs/` and generate paired reconstruction notes under `Modules/PreCanonical/outcomes/`.
- Pair marked `R` blocks to specs only by normalized `NAME:` text and preserve marked `P` blocks as associated or global preamble context.
- Map legacy alphanumeric IDs into padded dot-separated canonical IDs without renumbering imported records into modern modules.

**Reasoning**

- The explicit archive markers are the strongest available boundary signal and avoid fuzzy extraction or module inference.
- A dedicated PreCanonical module preserves lineage while keeping modern module ownership reviewable in later alignment work.
- Idempotent generated files and implementation-log entries let repeated imports remain deterministic and safe.

**Alternatives Considered**

- Extend `historical-specs:import` to understand marked monolithic archives.
- Infer current framework modules from legacy names or result content during this pass.
- Preserve pre-canonical records only as raw `_import` files without validator-compatible specs.

**Impact**

- Marked pre-canonical archives can be converted into durable module context, execution specs, reconstruction notes, and implementation-log entries.
- Orphan results, malformed legacy IDs, duplicate spec names with different content, and output conflicts fail deterministically.
- Future specs can map PreCanonical lineage into modern modules without losing the original archive evidence.

**Spec Reference**

- Core Principle
- Required Block Types
- Canonical ID Mapping
- Output Layout
- CLI Command
- Acceptance Criteria

### Decision: make app feature roots canonical without legacy compatibility

Timestamp: 2026-05-29T13:35:14-04:00

**Context**

- First-run app scaffolding and generation still used older `app/features/` and `docs/features/` paths even though app-facing guidance describes localized application feature roots under `Features/<Feature>/`.
- No external Foundry applications depend on the older app feature layout yet.
- The Blog demo revealed that feature-specific context, runtime manifests, actions, services, storage, and tests being spread across older/shared paths undermines Foundry's modularity story.

**Decision**

- Create execution spec `014-canonical-app-feature-roots-without-legacy-layout` to make `Features/<Feature>/` the only authored application feature root.
- Require fresh apps to include top-level `Features/`, `Modules/`, and `Packs/` directories even when empty.
- Remove migration-command and compatibility-branch requirements for `app/features/` and `docs/features/` because there are no older projects to preserve.
- Treat Blog only as an illustrative feature name for examples; the framework must implement generic `<Feature>` behavior and must not special-case Blog.
- Specify that feature-owned runtime code belongs at `Features/<Feature>/src/`, with `Features/Blog/src/` as an example.

**Reasoning**

- Carrying legacy compatibility before there are users would encode confusion into the product and make demos harder to explain.
- A clean app feature root gives humans and agents a single locality boundary for context, code, tests, specs, outcomes, and docs.
- Keeping `Modules/` and `Packs/` visible in new apps makes the framework's reserved top-level concepts explicit without implying that app feature code belongs there.
- Making Blog an example rather than a hard-coded feature keeps the framework generic while preserving a clear demo narrative.

**Alternatives Considered**

- Add a `foundry migrate features --json` command for old layouts.
- Keep legacy `app/features/` and `docs/features/` as readable compatibility paths.
- Warn when legacy paths exist but continue running.
- Special-case Blog support for the demo path.

**Impact**

- The next implementation pass should remove old app feature source/context path support instead of migrating it.
- Verification should fail hard when obsolete app-layout directories exist.
- Docs, stubs, examples, tests, generation, context commands, compiler discovery, and runtime loading must align on `Features/<Feature>/`.
- Blog examples remain useful for demos but do not create Blog-specific framework behavior.

**Spec Reference**

- Purpose
- Core Principle
- Canonical App Layout
- Illustrative Feature Placement Contract
- Context Command Requirements
- Feature Generation Requirements
- Verification Requirements

### Decision: preserve docs as public documentation while removing app context from docs/features

Timestamp: 2026-05-29T14:35:00-04:00

**Context**

`docs/` contains important authored framework documentation, and the foundryframework.org website consumes this repository's docs through a checked-out and pinned git submodule. The canonical app feature root refactor must not treat all docs content as disposable legacy material.

**Decision**

Keep `docs/` as a canonical public documentation surface and update docs in place where path guidance changes. Treat only application feature context under `docs/features/<feature>/` as obsolete; application feature context, runtime code, and tests now belong under `Features/<Feature>/`.

**Reasoning**

Deleting or moving docs wholesale would break the public documentation pipeline and discard important framework information. The confusing legacy behavior was app feature context living in docs, not public docs existing under `docs/`.

**Alternatives Considered**

- Fail verification whenever `docs/features/` exists at all.
- Move existing framework docs out of `docs/` during the feature-root refactor.
- Preserve `docs/features/<feature>/` as an app context compatibility path.

**Impact**

Feature verification can keep hard-failing legacy app context under `docs/features/<feature>/` without blocking ordinary framework documentation. Future docs updates should preserve website-consumed docs and update stale path guidance instead of deleting docs.

**Spec Reference**

- Modules/FeatureSystem/specs/014-canonical-app-feature-roots-without-legacy-layout.md
