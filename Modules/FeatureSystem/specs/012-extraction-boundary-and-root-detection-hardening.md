# Execution Spec: 012-extraction-boundary-and-root-detection-hardening

## Purpose

Harden historical spec extraction so the importer preserves real historical specs while reducing phantom candidates caused by section headings, recap prose, and embedded prior-spec references.

This spec builds on:

```text
Modules/FeatureSystem/specs/007.001-historical-spec-archive-extraction-helper.md
Modules/FeatureSystem/specs/007.002-historical-spec-ordering-and-evidence-map.md
Modules/FeatureSystem/specs/007.003-tighten-historical-import-boundary-and-module-inference.md
Modules/FeatureSystem/specs/008-import-historical-spec-archive.md
Modules/FeatureSystem/specs/009-generate-historical-module-context-docs.md
Modules/FeatureSystem/specs/010-generate-historical-reconstruction-notes-and-log-entries.md
Modules/FeatureSystem/specs/011-add-decision-summaries-without-compacting-ledgers.md
```

The current extractor successfully scans historical files and emits deterministic candidates, but it over-segments long historical documents by treating subsection headings and recap fragments as standalone specs.

The goal is not to reduce candidate count arbitrarily. The goal is to improve candidate quality while preserving legitimate multi-spec files.

Given the archive shape, 71 historical files may reasonably contain 90+ actual specs. The extractor must therefore support high candidate counts without mistaking ordinary sections such as `must:`, `Architecture`, or continuation prose for independent specs.

---

## Core Principle

Historical extraction must be conservative about spec starts, but permissive about legitimate multi-spec documents.

A candidate should be emitted only when there is credible evidence of a new spec root, not merely because text contains a heading, numbered reference, or contract-like wording.

---

## Background

A dry-run extraction and evidence pass over `_import/historical-specs/` produced:

```text
files_scanned: 71
candidates: 180
```

This showed that the pipeline is functional but too permissive.

Some candidates are clearly valid historical specs. Examples include:

```text
Spec 19A — CLI Entry + Target Resolution
Spec 0D — Middleware
Spec 19H — Local foundry Executable for Installed Apps
```

Other candidates are likely false-positive fragments. Examples include titles such as:

```text
must:
introduced collectors, analyzers, and rich explanation sections
Architecture (what it is)
Final polish (rendering + contributors + docs)
completes the feature by adding:
```

These fragments are usually:

- section headings
- recap material
- continuation prose
- embedded previous-spec references
- explanatory subsections
- result/output commentary

They should remain attached to the surrounding source segment rather than being emitted as standalone spec candidates.

---

## Goals

1. Improve root-spec boundary detection.
2. Preserve legitimate multi-spec files.
3. Avoid requiring manual marker insertion into 71 historical files.
4. Reduce phantom candidates caused by subsection headings and recap fragments.
5. Preserve embedded `OUTPUT` / `RESULT` evidence.
6. Keep extraction deterministic.
7. Add review-focused diagnostics explaining why each candidate was emitted.
8. Ensure downstream import receives cleaner candidate folders.

---

## Non-Goals

- Do not require humans to edit historical files with special delimiters.
- Do not require a perfect semantic parser.
- Do not discard low-confidence content silently.
- Do not import candidates into `Modules/*` in this spec.
- Do not generate module context docs in this spec.
- Do not generate reconstruction notes in this spec.
- Do not compact or rewrite decision ledgers.
- Do not split the historical import work into a new module retroactively.

---

## Module Placement

This spec belongs under:

```text
Modules/FeatureSystem/specs/012-extraction-boundary-and-root-detection-hardening.md
```

Rationale:

- the extractor/importer/evidence/reconstruction pipeline already lives in FeatureSystem
- this is a tightening spec for that existing pipeline
- moving the work into a new `HistoricalSpecImport` module now would add migration overhead without improving the current import task

A future refactor MAY extract historical import tooling into a dedicated `HistoricalSpecImport` module, but this spec must not do that.

---

## Required Behavior

Update:

```text
src/FeatureSystem/HistoricalSpecArchiveExtractor.php
src/FeatureSystem/HistoricalSpecEvidenceMapper.php
```

and related tests so candidate emission is based on stronger root-spec evidence.

---

## Root Spec Detection Model

A new candidate may begin only when the extractor detects a credible spec root.

### Strong Spec Root Signals

Any of these may be sufficient:

```text
# Spec 19A — Title
## Spec 19A — Title
Spec 19A — Title
Spec 19A: Title
Execution Spec: Title
# Execution Spec: Title
Spec: Title
```

provided the heading/line occurs at a plausible boundary and is followed by enough body content.

### Legacy Filename Support

If a file is named:

```text
Foundry-Spec-19A.md
Foundry-Spec-30C-2.md
Foundry-Spec-35D.md
```

and the file contains one obvious top-level spec body without explicit internal spec heading, the extractor may emit one candidate using the filename label.

This must not cause every section in the file to become a candidate.

### Multi-Spec Support

If a file contains multiple explicit spec roots, emit multiple candidates.

Example:

```text
Spec 19A — CLI Entry + Target Resolution
...
Spec 19B — Core Models + Engine Skeleton
...
Spec 19C — Plan Assembly + Renderers
...
```

Each should become a candidate.

---

## Anti-Root / Section Fragment Detection

The extractor must not start a new candidate from common subsection or recap headings unless they also contain an explicit spec label on the same heading.

Examples that must not start a candidate by themselves:

```text
Architecture
Architecture (what it is)
Implementation
Implementation (how it works end-to-end)
UX contract
UX contract (what it feels like)
Foundation slice
Foundation slice (safe starting point)
Intelligence layer
Intelligence layer (collectors + analyzers)
Final polish
Final polish (rendering + contributors + docs)
Goals
Non-Goals
Acceptance Criteria
Requirements
Testing
Verify
must:
should:
introduced ...
established ...
adds ...
completes ...
```

These sections should remain inside the nearest active candidate or source segment.

---

## Structural Threshold

Even when a possible spec root is found, the extractor must verify minimum structure before emitting a candidate.

A candidate should generally require:

1. explicit spec root signal OR legacy filename fallback
2. meaningful body length
3. at least one implementation-contract signal within the segment

Contract signals include:

```text
Purpose
Goals
Non-Goals
Requirements
Acceptance Criteria
Testing
Implementation
CLI
Command
Output
Result
Done Means
Must
Should
```

Very short fragments should be attached to the surrounding candidate or marked as supporting evidence, not emitted as standalone specs.

---

## Result / Output Handling

`OUTPUT`, `RESULT`, `IMPLEMENTATION RESULT`, and similar sections must not by themselves start new spec candidates.

They must be associated with the nearest preceding valid spec candidate when possible.

If no valid candidate exists, preserve them as supporting evidence.

Rules:

- do not duplicate entire result transcripts into `spec.md`
- preserve raw transcript in `result.md`
- record association confidence in `metadata.json`
- if association is uncertain, set confidence to `low` or mark for review

---

## Recap / Embedded Prior-Spec Handling

Some historical specs contain recaps of prior specs. These may mention labels such as:

```text
Spec 19A
Spec 19B
Spec 19C
```

inside the body of another file.

These internal references must not automatically create new candidates unless they appear as independent spec-root headings.

A line that merely describes a prior spec, such as:

```text
Spec 19D established the foundations for `foundry explain`.
```

must remain prose, not become a candidate.

---

## Candidate Emission Reasons

Each candidate metadata file must include a deterministic `emission_reason`.

Examples:

```json
{
  "emission_reason": "explicit_spec_heading"
}
```

Allowed values:

```text
explicit_spec_heading
execution_spec_heading
legacy_filename_single_spec
manual_anchor
supporting_evidence
```

Also add `rejected_root_signals` when useful:

```json
{
  "rejected_root_signals": [
    {
      "text": "must:",
      "reason": "section_fragment"
    }
  ]
}
```

This makes review faster without requiring humans to inspect every raw file.

---

## Candidate Quality Classification

Add or refine a candidate quality field:

```json
{
  "candidate_quality": "strong|probable|weak|supporting"
}
```

Suggested rules:

- `strong`: explicit spec root + contract structure
- `probable`: legacy filename fallback + contract structure
- `weak`: partial evidence; review before import
- `supporting`: evidence file or result-only content

`weak` candidates must not default to import.

---

## Expected Output Improvement

Do not hard-code an expected candidate count.

However, after this spec, the extractor should no longer emit candidates whose title is only:

```text
must:
Architecture (what it is)
Implementation (how it works end-to-end)
Final polish (rendering + contributors + docs)
introduced ...
completes ...
```

A healthy output may still contain 90+ candidates if the archive actually contains 90+ specs.

The goal is candidate correctness, not candidate minimization.

---

## CLI Behavior

Existing command remains:

```bash
php bin/foundry historical-specs:extract   --source=_import/historical-specs   --target=_import/generated-candidates   --json
```

Add optional review diagnostics if useful:

```bash
php bin/foundry historical-specs:extract   --source=_import/historical-specs   --target=_import/generated-candidates   --json   --diagnostics
```

If `--diagnostics` is not implemented, include enough metadata in normal JSON output to explain candidate emission.

---

## Evidence Mapper Alignment

Update:

```bash
php bin/foundry historical-specs:evidence --source=_import/historical-specs --dry-run --json
```

so it respects the hardened extraction semantics.

The evidence mapper should not independently resurrect rejected section fragments as candidates.

---

## Determinism Requirements

- stable file ordering
- stable candidate numbering
- stable emission reasons
- stable rejected root signal ordering
- no timestamps
- no absolute local paths
- no environment-specific output

---

## Testing Requirements

Add or update tests for:

### Valid Candidate Preservation

- one file with one explicit spec root emits one candidate
- one file with multiple explicit spec roots emits multiple candidates
- filename fallback emits one candidate for a single-spec file
- historical labels like `Spec 30C-2` still parse
- historical labels like `Spec 19FB` still parse

### False Positive Suppression

- `must:` does not become a candidate
- `Architecture (what it is)` does not become a candidate
- `Implementation (how it works end-to-end)` does not become a candidate
- `Final polish (rendering + contributors + docs)` does not become a candidate
- prose like `Spec 19D established...` does not become a candidate
- recap references to prior specs do not create duplicate candidates

### Output / Result Association

- `RESULT` below a spec attaches to that spec
- `OUTPUT` between specs attaches to the correct preceding spec
- result-only content becomes supporting evidence, not an active spec
- result transcript is preserved outside `spec.md`

### Metadata

- `emission_reason` is present
- `candidate_quality` is present
- `rejected_root_signals` are deterministic when emitted
- weak candidates default to review
- supporting evidence remains supporting

### End-to-End Regression

Use a fixture shaped like the 19A–19F historical files and assert that:

- real `Spec 19A` through `Spec 19F` roots are preserved
- recap headings are not emitted as separate candidates
- `must:` fragments are not emitted as specs
- candidate ordering is stable

---

## Acceptance Criteria

- extractor no longer emits common section fragments as standalone specs
- legitimate multi-spec files still produce multiple candidates
- candidate metadata explains why each candidate exists
- evidence mapper respects hardened boundaries
- no manual delimiter insertion is required
- import has cleaner input candidates
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

Also run:

```bash
php bin/foundry historical-specs:extract --source=_import/historical-specs --target=_import/generated-candidates --json
php bin/foundry historical-specs:evidence --source=_import/historical-specs --dry-run --json
```

Both historical commands must exit `0`.

---

## Reconstruction Note

Create:

```text
Modules/FeatureSystem/plans/012-extraction-boundary-and-root-detection-hardening.md
```

---

## Codex Guidance

Use GPT-5.5 High.

This task is partly code, partly linguistic boundary detection. Do not rely on brittle regex-only behavior when it would recreate the over-segmentation problem.

Be conservative about spec starts, but do not suppress legitimate multi-spec files.
