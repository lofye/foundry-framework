# 012-extraction-boundary-and-root-detection-hardening

## Spec Implemented

`Modules/FeatureSystem/specs/012-extraction-boundary-and-root-detection-hardening.md`

## Implementation Summary

- Hardened historical spec extraction so only credible explicit roots and legacy `Foundry-Spec-*` filename fallbacks emit active candidates.
- Suppressed common section fragments, recap prose, and embedded prior-spec references as standalone candidate roots.
- Added supporting-evidence handling for result/output-only historical content with transcript preservation in `result.md`.
- Added deterministic candidate metadata for `emission_reason`, `candidate_quality`, `rejected_root_signals`, and `result_association_confidence`.
- Aligned historical evidence mapping with the same hardened root semantics so it does not resurrect rejected fragments as importable candidates.

## Files Introduced

- `Modules/FeatureSystem/outcomes/012-extraction-boundary-and-root-detection-hardening.md`

## Files Modified

- `src/FeatureSystem/HistoricalSpecArchiveExtractor.php`
- `src/FeatureSystem/HistoricalSpecEvidenceMapper.php`
- `tests/Unit/HistoricalSpecArchiveExtractorTest.php`
- `tests/Unit/HistoricalSpecEvidenceMapperTest.php`
- `Modules/FeatureSystem/specs/012-extraction-boundary-and-root-detection-hardening.md`
- `Modules/FeatureSystem/feature-system.spec.md`
- `Modules/FeatureSystem/feature-system.md`
- `Modules/FeatureSystem/feature-system.decisions.md`
- `Modules/implementation.log`

## Runtime Contracts

- Explicit spec headings and execution-spec headings remain valid candidate roots.
- Legacy `Foundry-Spec-*` filenames can emit one probable candidate when the file has contract structure and no internal roots.
- Section headings such as `must:`, `Architecture`, `Implementation`, and `Final polish` do not create candidates by themselves.
- Prose references such as `Spec 19D established...` remain body text, not candidate roots.
- Result/output-only content is emitted as supporting evidence and preserves transcripts in `result.md`.
- Weak candidates default to review behavior; supporting evidence defaults to ignore-supporting behavior.

## Deterministic Outputs

- Candidate numbering remains stable by sorted source file and detected root order.
- Metadata includes stable `emission_reason`, `candidate_quality`, rejected root signal order, and result association confidence.
- Historical extraction/evidence outputs remain repository-relative and timestamp-free.

## Tests Added Or Updated

- `tests/Unit/HistoricalSpecArchiveExtractorTest.php`
- `tests/Unit/HistoricalSpecEvidenceMapperTest.php`
- `tests/Integration/CLIHistoricalSpecsExtractCommandTest.php`
- `tests/Integration/CLIHistoricalSpecsEvidenceCommandTest.php`

## Verification Commands

- `php vendor/bin/phpunit tests/Unit/HistoricalSpecArchiveExtractorTest.php tests/Unit/HistoricalSpecEvidenceMapperTest.php tests/Integration/CLIHistoricalSpecsExtractCommandTest.php tests/Integration/CLIHistoricalSpecsEvidenceCommandTest.php`
- `php bin/foundry historical-specs:extract --source=_import/historical-specs --target=_import/generated-candidates --json`
- `php bin/foundry historical-specs:evidence --source=_import/historical-specs --dry-run --json`
- `php bin/foundry spec:validate --json`
- `php bin/foundry verify context --json`
- `php bin/foundry verify features --json`
- `php bin/foundry verify contracts --json`
- `php vendor/bin/phpunit`
- `XDEBUG_MODE=coverage php vendor/bin/phpunit --coverage-clover build/coverage/clover.xml`
- `php bin/foundry verify coverage --min=90 --clover=build/coverage/clover.xml --json`

## Decisions And Tradeoffs

- Kept extraction conservative about roots while still preserving explicit multi-spec files.
- Avoided hard-coding expected candidate counts because the archive can legitimately contain more specs than files.
- Preserved low-confidence material as supporting evidence instead of silently discarding result/output content.
- Kept the historical import tooling inside FeatureSystem per the execution spec rather than creating a new module.

## Reconstruction Notes

- Reconstruct the behavior by reading source files in deterministic order, splitting only on explicit root lines, applying filename fallback only for legacy spec filenames, attaching result/output sections to the nearest candidate, and rendering supporting evidence for result-only files.
- Evidence mapping uses the same root recognition model and marks weak/supporting candidates so downstream import can avoid treating fragments as import-ready specs.

## Follow-Up Dependencies

- Future historical import review can consume the cleaner generated candidate set without manual delimiter insertion.
