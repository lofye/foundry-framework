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


------------------------------------------------------------------------------------------



------------------------------------------------------------------------------------------



------------------------------------------------------------------------------------------
