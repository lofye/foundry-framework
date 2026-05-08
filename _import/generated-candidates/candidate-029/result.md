Implemented 19F without changing the architecture. The main work is in [src/Explain/ExplanationPlan.php](/Users/lo_fye/code/foundry-framework/src/Explain/ExplanationPlan.php), [src/Explain/ExplanationPlanAssembler.php](/Users/lo_fye/code/foundry-framework/src/Explain/ExplanationPlanAssembler.php), and [src/Explain/ExplainContext.php](/Users/lo_fye/code/foundry-framework/src/Explain/ExplainContext.php): high-value context slices and plan sections are now typed value objects, the public JSON contract is deliberate (`executionFlow`, `relationships`, `relatedCommands`, `relatedDocs`, `suggestedFixes`), and renderers remain plan-only. Contributor support is now explicit through [src/Explain/Contributors/ExplainContribution.php](/Users/lo_fye/code/foundry-framework/src/Explain/Contributors/ExplainContribution.php) and [src/Explain/Contributors/ExplainContributorRegistry.php](/Users/lo_fye/code/foundry-framework/src/Explain/Contributors/ExplainContributorRegistry.php), with deterministic merge/order still owned by the assembler. I also deepened command/schema/extension/job analysis in their subject analyzers and tightened suggested-fix generation in [src/Explain/SuggestedFixesBuilder.php](/Users/lo_fye/code/foundry-framework/src/Explain/SuggestedFixesBuilder.php).

The renderer layer is finalized in [src/Explain/Renderers/TextExplanationRenderer.php](/Users/lo_fye/code/foundry-framework/src/Explain/Renderers/TextExplanationRenderer.php), [src/Explain/Renderers/MarkdownExplanationRenderer.php](/Users/lo_fye/code/foundry-framework/src/Explain/Renderers/MarkdownExplanationRenderer.php), and [src/Explain/Renderers/JsonExplanationRenderer.php](/Users/lo_fye/code/foundry-framework/src/Explain/Renderers/JsonExplanationRenderer.php). Custom/contributor sections now render by declared section shape instead of generic JSON dumping. Docs were updated in [README.md](/Users/lo_fye/code/foundry-framework/README.md), [docs/architecture-tools.md](/Users/lo_fye/code/foundry-framework/docs/architecture-tools.md), and [docs/extension-author-guide.md](/Users/lo_fye/code/foundry-framework/docs/extension-author-guide.md). The extension API registry now marks `ExplainContribution` as stable in [src/Support/ApiSurfaceRegistry.php](/Users/lo_fye/code/foundry-framework/src/Support/ApiSurfaceRegistry.php).

Verified with:
- `php vendor/bin/phpunit tests/Unit/ExplainPolishTest.php tests/Unit/ExplainFoundationTest.php tests/Unit/ExplainRenderersTest.php tests/Unit/ExplainEngineTest.php tests/Unit/ExplainArchitectureCoverageTest.php tests/Unit/ProAnalysisToolsTest.php tests/Unit/ApiSurfaceRegistryTest.php tests/Unit/CLIApplicationEdgeCasesTest.php tests/Unit/CLICommandMatchesTest.php tests/Integration/CLIProCommandsTest.php tests/Integration/CLIApplicationTest.php`
- `php -d zend_extension=/opt/homebrew/lib/php/pecl/20240924/xdebug.so -d xdebug.mode=coverage vendor/bin/phpunit --coverage-clover /tmp/explain-19f.xml ...`
- `php bin/foundry compile graph --json`
- `php bin/foundry verify graph --json`
- `php bin/foundry verify pipeline --json`
- `php bin/foundry verify contracts --json`

Coverage is above threshold:
- explain slice: `91.68%` (`2601/2837`)
- explain slice plus adjacent CLI/help layers: `91.63%` (`3131/3417`)

This leaves `foundry explain` in the 19F state: stable contracts, polished rendering, contributor-aware assembly, deterministic JSON, updated docs, and no layer leakage.


39 files changed
+1311
-237
Undo
README.md
docs/architecture-tools.md
docs/extension-author-guide.md
src/Explain/Analyzers/CommandSubjectAnalyzer.php
src/Explain/Analyzers/DiagnosticsAnalyzer.php
src/Explain/Analyzers/ExtensionSubjectAnalyzer.php
src/Explain/Analyzers/JobSubjectAnalyzer.php
src/Explain/Analyzers/SchemaSubjectAnalyzer.php
src/Explain/Contributors/ExplainContribution.php
src/Explain/Contributors/ExplainContributorInterface.php
src/Explain/Contributors/ExplainContributorRegistry.php
src/Explain/DiagnosticsContextData.php
src/Explain/DiagnosticsSection.php
src/Explain/DocsContextData.php
src/Explain/ExecutionFlowSection.php
src/Explain/ExplainArrayView.php
src/Explain/ExplainContext.php
src/Explain/ExplainEngineFactory.php
src/Explain/ExplainSection.php
src/Explain/ExplainSupport.php
src/Explain/ExplanationPlan.php
src/Explain/ExplanationPlanAssembler.php
src/Explain/GraphNeighborhoodContext.php
src/Explain/GraphRelationshipsSection.php
src/Explain/PipelineContextData.php
src/Explain/RelationshipSection.php
src/Explain/Renderers/MarkdownExplanationRenderer.php
src/Explain/Renderers/TextExplanationRenderer.php
src/Explain/SuggestedFixesBuilder.php
src/Explain/SummarySectionBuilder.php
src/Support/ApiSurfaceRegistry.php
tests/Integration/CLIProCommandsTest.php
tests/Unit/ApiSurfaceRegistryTest.php
tests/Unit/ExplainArchitectureCoverageTest.php
tests/Unit/ExplainEngineTest.php
tests/Unit/ExplainFoundationTest.php
tests/Unit/ExplainPolishTest.php
tests/Unit/ExplainRenderersTest.php
tests/Unit/ProAnalysisToolsTest.php


------------------------------------------------------------------------------------------
