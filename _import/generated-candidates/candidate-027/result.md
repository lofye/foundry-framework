**19E Complete**

The required 19D cleanup is done and 19E is implemented.

The main architectural fixes are in [ExplainContext.php](/Users/lo_fye/code/foundry-framework/src/Explain/ExplainContext.php), [ExplainSupport.php](/Users/lo_fye/code/foundry-framework/src/Explain/ExplainSupport.php), [ExplainSubjectFactory.php](/Users/lo_fye/code/foundry-framework/src/Explain/ExplainSubjectFactory.php), and [ExplanationPlan.php](/Users/lo_fye/code/foundry-framework/src/Explain/ExplanationPlan.php). `ExplainContext` is now structured into named slices, canonical kind mapping is explicit, raw graph types no longer leak into subjects or JSON, and the old raw-graph summary path was removed by replacing [RuleBasedSummaryBuilder.php](/Users/lo_fye/code/foundry-framework/src/Explain/RuleBasedSummaryBuilder.php) with [SummarySectionBuilder.php](/Users/lo_fye/code/foundry-framework/src/Explain/SummarySectionBuilder.php). `rg` now shows no remaining `context->graph`, `context->artifacts`, or bag-style `context->get(...)` access in `src/Explain`.

The intelligence layer now lives where it should: collectors in [src/Explain/Collectors](/Users/lo_fye/code/foundry-framework/src/Explain/Collectors), subject analyzers and section analyzers in [src/Explain/Analyzers](/Users/lo_fye/code/foundry-framework/src/Explain/Analyzers), deterministic fix building in [SuggestedFixesBuilder.php](/Users/lo_fye/code/foundry-framework/src/Explain/SuggestedFixesBuilder.php), and centralized assembly in [ExplanationPlanAssembler.php](/Users/lo_fye/code/foundry-framework/src/Explain/ExplanationPlanAssembler.php). The engine in [ExplainEngine.php](/Users/lo_fye/code/foundry-framework/src/Explain/ExplainEngine.php) still only orchestrates, and the renderers in [TextExplanationRenderer.php](/Users/lo_fye/code/foundry-framework/src/Explain/Renderers/TextExplanationRenderer.php) and [MarkdownExplanationRenderer.php](/Users/lo_fye/code/foundry-framework/src/Explain/Renderers/MarkdownExplanationRenderer.php) still consume only the assembled plan. I also fixed two real 19E bugs while tightening the suite: permission-definition lookup in [PipelineContextCollector.php](/Users/lo_fye/code/foundry-framework/src/Explain/Collectors/PipelineContextCollector.php) and nested extension capability handling in [ExtensionSubjectAnalyzer.php](/Users/lo_fye/code/foundry-framework/src/Explain/Analyzers/ExtensionSubjectAnalyzer.php).

**Verification**

Focused and integration coverage/tests were updated in [ExplainFoundationTest.php](/Users/lo_fye/code/foundry-framework/tests/Unit/ExplainFoundationTest.php), [ExplainEngineTest.php](/Users/lo_fye/code/foundry-framework/tests/Unit/ExplainEngineTest.php), [ExplainRenderersTest.php](/Users/lo_fye/code/foundry-framework/tests/Unit/ExplainRenderersTest.php), [ExplainArchitectureCoverageTest.php](/Users/lo_fye/code/foundry-framework/tests/Unit/ExplainArchitectureCoverageTest.php), [ProAnalysisToolsTest.php](/Users/lo_fye/code/foundry-framework/tests/Unit/ProAnalysisToolsTest.php), and [CLIProCommandsTest.php](/Users/lo_fye/code/foundry-framework/tests/Integration/CLIProCommandsTest.php).

Verified with:
- `php vendor/bin/phpunit tests/Unit/ExplainFoundationTest.php tests/Unit/ExplainRenderersTest.php tests/Unit/ExplainEngineTest.php tests/Unit/ExplainArchitectureCoverageTest.php tests/Unit/ProAnalysisToolsTest.php tests/Unit/ApiSurfaceRegistryTest.php tests/Unit/CLIApplicationEdgeCasesTest.php tests/Unit/CLICommandMatchesTest.php tests/Integration/CLIProCommandsTest.php tests/Integration/CLIApplicationTest.php`
- `php -d zend_extension=/opt/homebrew/lib/php/pecl/20240924/xdebug.so -d xdebug.mode=coverage vendor/bin/phpunit --coverage-clover /tmp/explain-19e.xml ...`
- `php bin/foundry compile graph --json`
- `php bin/foundry verify graph --json`
- `php bin/foundry verify pipeline --json`
- `php bin/foundry verify contracts --json`

Coverage is above the required threshold:
- explain slice: `92.97%` (`2274/2446`) for `src/Explain` plus [ArchitectureExplainer.php](/Users/lo_fye/code/foundry-framework/src/Pro/ArchitectureExplainer.php) and [ExplainCommand.php](/Users/lo_fye/code/foundry-framework/src/Pro/CLI/ExplainCommand.php)
- including adjacent CLI/help layers: `92.66%` (`2803/3025`) with [Application.php](/Users/lo_fye/code/foundry-framework/src/CLI/Application.php) and [ApiSurfaceRegistry.php](/Users/lo_fye/code/foundry-framework/src/Support/ApiSurfaceRegistry.php)

This is safe to build on for 19F. The cleanup blockers from 19D are resolved, and the explain subsystem is now collector-driven, analyzer-driven, plan-driven, and still deterministic.


54 files changed
+3030
-2407
Undo
src/Explain/Analyzers/CommandSubjectAnalyzer.php
src/Explain/Analyzers/DependencyAnalyzer.php
src/Explain/Analyzers/DependentAnalyzer.php
src/Explain/Analyzers/DiagnosticsAnalyzer.php
src/Explain/Analyzers/EventEmissionAnalyzer.php
src/Explain/Analyzers/EventSubjectAnalyzer.php
src/Explain/Analyzers/ExecutionFlowAnalyzer.php
src/Explain/Analyzers/ExtensionSubjectAnalyzer.php
src/Explain/Analyzers/FeatureSubjectAnalyzer.php
src/Explain/Analyzers/GenericGraphSubjectAnalyzer.php
src/Explain/Analyzers/GraphRelationshipsAnalyzer.php
src/Explain/Analyzers/JobSubjectAnalyzer.php
src/Explain/Analyzers/PermissionAnalyzer.php
src/Explain/Analyzers/PipelineStageSubjectAnalyzer.php
src/Explain/Analyzers/RelatedCommandsAnalyzer.php
src/Explain/Analyzers/RelatedDocsAnalyzer.php
src/Explain/Analyzers/RouteSubjectAnalyzer.php
src/Explain/Analyzers/SchemaInteractionAnalyzer.php
src/Explain/Analyzers/SchemaSubjectAnalyzer.php
src/Explain/Analyzers/SectionAnalyzerInterface.php
src/Explain/Analyzers/SubjectAnalysisResult.php
src/Explain/Analyzers/SubjectAnalyzerInterface.php
src/Explain/Analyzers/TriggerAnalyzer.php
src/Explain/Analyzers/WorkflowSubjectAnalyzer.php
src/Explain/Collectors/CommandContextCollector.php
src/Explain/Collectors/DiagnosticsContextCollector.php
src/Explain/Collectors/DocsContextCollector.php
src/Explain/Collectors/EventContextCollector.php
src/Explain/Collectors/ExtensionContextCollector.php
src/Explain/Collectors/GraphNeighborhoodCollector.php
src/Explain/Collectors/ImpactContextCollector.php
src/Explain/Collectors/PipelineContextCollector.php
src/Explain/Collectors/SchemaContextCollector.php
src/Explain/Collectors/WorkflowContextCollector.php
src/Explain/ExplainContext.php
src/Explain/ExplainEngine.php
src/Explain/ExplainEngineFactory.php
src/Explain/ExplainSubjectFactory.php
src/Explain/ExplainSupport.php
src/Explain/ExplainTarget.php
src/Explain/ExplainTargetResolver.php
src/Explain/ExplanationPlan.php
src/Explain/ExplanationPlanAssembler.php
src/Explain/Renderers/MarkdownExplanationRenderer.php
src/Explain/Renderers/TextExplanationRenderer.php
src/Explain/RuleBasedSummaryBuilder.php
src/Explain/SuggestedFixesBuilder.php
src/Explain/SummarySectionBuilder.php
tests/Integration/CLIProCommandsTest.php
tests/Unit/ExplainArchitectureCoverageTest.php
tests/Unit/ExplainEngineTest.php
tests/Unit/ExplainFoundationTest.php
tests/Unit/ExplainRenderersTest.php
tests/Unit/ProAnalysisToolsTest.php

------------------------------------------------------------------------------------------
