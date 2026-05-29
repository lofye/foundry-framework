# Implementation Plan: 033-explain-system-hardening-extension-aware-architecture

## Historical Provenance

- Imported spec path: `Modules/PreCanonical/specs/033-explain-system-hardening-extension-aware-architecture.md`
- Source archive: `_import/pre-canonical-specs.md`
- Legacy name: `33 — Explain System Hardening + Extension-Aware Architecture`
- Legacy id: `33`
- Canonical pre-canonical id: `033`

## Historical Specification Summary

The original pre-canonical specification body is preserved in the imported execution spec. This reconstruction note records adjacent marked context and result evidence without inferring modern module ownership.

## Historical Preamble Context

### Preamble Block 1

- Name: `33 — Explain System Hardening + Extension-Aware Architecture`

Canonical Model + Deterministic Contract + Marketplace Integration Surface

⸻

🧭 Purpose (Updated)

Transform the explain system into:

the canonical, deterministic, extension-aware architecture truth layer

that safely powers:
	•	CLI introspection
	•	documentation generation
	•	graph inspection
	•	generate (Spec 35)
	•	pack / extension introspection (NEW)
	•	marketplace trust + metadata surface (NEW)

This remains a hardening + normalization pass, with ecosystem integration added.

⸻

🧠 Core Principle (Expanded)

Explain must become:

the single source of truth for understanding the system including installed extensions

Not:

a core-only introspection tool

## Historical Implementation Evidence

### Result Block 1

- Name: `33 — Explain System Hardening + Extension-Aware Architecture`

RESULT

Explain is now extension-aware and pack-aware without breaking the existing CLI shape. I added a canonical model and attribution layer in [ExplainModel.php](/Users/lo_fye/code/foundry-framework/src/Explain/ExplainModel.php), [ExplainOrigin.php](/Users/lo_fye/code/foundry-framework/src/Explain/ExplainOrigin.php), [ExplanationPlan.php](/Users/lo_fye/code/foundry-framework/src/Explain/ExplanationPlan.php), and [ExplanationPlanAssembler.php](/Users/lo_fye/code/foundry-framework/src/Explain/ExplanationPlanAssembler.php). `foundry explain` now emits top-level `graph`, `execution`, `guards`, `events`, `schemas`, `docs`, `impact`, `commands`, and `extensions` domains, while keeping the legacy `executionFlow`/`relationships`/`relatedDocs` contract for compatibility. Subjects now carry `origin` and `extension`, and renderers surface that context in [TextExplanationRenderer.php](/Users/lo_fye/code/foundry-framework/src/Explain/Renderers/TextExplanationRenderer.php) and [MarkdownExplanationRenderer.php](/Users/lo_fye/code/foundry-framework/src/Explain/Renderers/MarkdownExplanationRenderer.php).

The resolver and analyzer stack now understand installed packs directly. [ExplainTarget.php](/Users/lo_fye/code/foundry-framework/src/Explain/ExplainTarget.php), [ExplainTargetResolver.php](/Users/lo_fye/code/foundry-framework/src/Explain/ExplainTargetResolver.php), [ExplainSubjectFactory.php](/Users/lo_fye/code/foundry-framework/src/Explain/ExplainSubjectFactory.php), [ExtensionContextCollector.php](/Users/lo_fye/code/foundry-framework/src/Explain/Collectors/ExtensionContextCollector.php), and [PackSubjectAnalyzer.php](/Users/lo_fye/code/foundry-framework/src/Explain/Analyzers/PackSubjectAnalyzer.php) now support `foundry explain pack:<vendor/pack>` and attribute core-vs-extension contributions deterministically. I also persisted pack install source metadata through [InstalledPackRegistry.php](/Users/lo_fye/code/foundry-framework/src/Packs/InstalledPackRegistry.php), [LocalPackLoader.php](/Users/lo_fye/code/foundry-framework/src/Packs/LocalPackLoader.php), [InstalledPackExtension.php](/Users/lo_fye/code/foundry-framework/src/Packs/InstalledPackExtension.php), and [PackManager.php](/Users/lo_fye/code/foundry-framework/src/Packs/PackManager.php), so the extensions domain can distinguish local vs marketplace installs. Public help/docs were updated in [ApiSurfaceRegistry.php](/Users/lo_fye/code/foundry-framework/src/Support/ApiSurfaceRegistry.php), [README.md](/Users/lo_fye/code/foundry-framework/README.md), and [architecture-tools.md](/Users/lo_fye/code/foundry-framework/docs/architecture-tools.md).

Verification passed:
- `php bin/foundry compile graph --json`
- `php bin/foundry verify graph --json`
- `php bin/foundry verify cli-surface --json` -> `128` valid, `0` invalid, `0` ambiguous, coverage `1`
- `php vendor/bin/phpunit` -> `386` tests passed, `3081` assertions

29 files changed
+1205
-56
Undo
README.md
docs/architecture-tools.md
src/Explain/Analyzers/PackSubjectAnalyzer.php
src/Explain/Analyzers/RelatedCommandsAnalyzer.php
src/Explain/Collectors/ExtensionContextCollector.php
src/Explain/Collectors/WorkflowContextCollector.php
src/Explain/ExplainArtifactCatalog.php
src/Explain/ExplainEngineFactory.php
src/Explain/ExplainModel.php
src/Explain/ExplainOrigin.php
src/Explain/ExplainSubject.php
src/Explain/ExplainSubjectFactory.php
src/Explain/ExplainSupport.php
src/Explain/ExplainTarget.php
src/Explain/ExplainTargetResolver.php
src/Explain/ExplanationPlan.php
src/Explain/ExplanationPlanAssembler.php
src/Explain/Renderers/MarkdownExplanationRenderer.php
src/Explain/Renderers/TextExplanationRenderer.php
src/Explain/SummarySectionBuilder.php
src/Packs/InstalledPackExtension.php
src/Packs/InstalledPackRegistry.php
src/Packs/LocalPackLoader.php
src/Packs/PackManager.php
src/Support/ApiSurfaceRegistry.php
tests/Integration/CLIPackCommandsTest.php
tests/Unit/ExplainArchitectureCoverageTest.php
tests/Unit/ExplainEngineTest.php
tests/Unit/ExplainPolishTest.php


=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-
=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-
=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-
=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-
=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-

BUILD ORDER:
34, 35, 30D, 30E, 30F, 30G, 36, 37, 38, 39, 40, 41, 42, 43, 44, 44B, 45A, 45B, 45C, 46, 47

## Historical Verification Evidence

Historical verification details, when present, are preserved verbatim inside the paired result blocks above.

## Historical Stabilization Notes

Historical stabilization details, when present, are preserved verbatim inside the paired result blocks above.

## Current Repository Alignment

The imported artifact is intentionally retained under `Modules/PreCanonical` as archive-lineage context. Modern module ownership remains deferred until a separate explicit alignment spec maps the pre-canonical intent into current modules.

## Uncertainty And Reconstruction Notes

No modern module inference was performed. The generated note preserves only the marked archive relationships available through `S`, `R`, and `P` blocks.
