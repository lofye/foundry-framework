Implemented the 19A architecture layer for `foundry explain` and wired the existing command through it.

The main change is a new core explain subsystem under [src/Explain/ExplainEngine.php](/Users/lo_fye/code/foundry-framework/src/Explain/ExplainEngine.php) and [src/Explain/ExplainTargetResolver.php](/Users/lo_fye/code/foundry-framework/src/Explain/ExplainTargetResolver.php). It now has explicit DTOs for targets/options/subjects/plans, a deterministic resolver, context collectors over graph + compiled projections + diagnostics, subject analyzers, contributor/render interfaces, and stable plan output. The Pro wrapper in [src/Pro/ArchitectureExplainer.php](/Users/lo_fye/code/foundry-framework/src/Pro/ArchitectureExplainer.php) is now a compatibility adapter over that engine, and [src/Pro/CLI/ExplainCommand.php](/Users/lo_fye/code/foundry-framework/src/Pro/CLI/ExplainCommand.php) parses the initial 19A flags (`--type`, `--markdown`, `--deep`, `--no-diagnostics`, `--no-neighbors`, `--no-flow`) while preserving Pro gating.

I also added focused coverage in [tests/Unit/ExplainEngineTest.php](/Users/lo_fye/code/foundry-framework/tests/Unit/ExplainEngineTest.php) and updated [tests/Unit/ProAnalysisToolsTest.php](/Users/lo_fye/code/foundry-framework/tests/Unit/ProAnalysisToolsTest.php) for the new deterministic plan-backed behavior.

Verified with:
- `php vendor/bin/phpunit tests/Unit/ExplainEngineTest.php tests/Unit/ProAnalysisToolsTest.php tests/Unit/CLICommandMatchesTest.php tests/Unit/ApiSurfaceRegistryTest.php`
- `php vendor/bin/phpunit tests/Integration/CLIProCommandsTest.php tests/Integration/CLIApplicationTest.php`
- `php bin/foundry compile graph --json`
- `php bin/foundry verify graph --json`
- `php bin/foundry verify pipeline --json`

What’s intentionally still modest at 19A: contributors are just a hook, docs collection is a stub, and analyzers/flow output are foundational rather than the full 19B-19F behavior.

44 files changed
+3096
-359
Undo
src/Explain/Analyzers/CommandSubjectAnalyzer.php
src/Explain/Analyzers/EventSubjectAnalyzer.php
src/Explain/Analyzers/ExtensionSubjectAnalyzer.php
src/Explain/Analyzers/FeatureSubjectAnalyzer.php
src/Explain/Analyzers/GenericGraphSubjectAnalyzer.php
src/Explain/Analyzers/JobSubjectAnalyzer.php
src/Explain/Analyzers/PipelineStageSubjectAnalyzer.php
src/Explain/Analyzers/RouteSubjectAnalyzer.php
src/Explain/Analyzers/SchemaSubjectAnalyzer.php
src/Explain/Analyzers/SubjectAnalyzerInterface.php
src/Explain/Analyzers/WorkflowSubjectAnalyzer.php
src/Explain/Collectors/CommandContextCollector.php
src/Explain/Collectors/DiagnosticsContextCollector.php
src/Explain/Collectors/DocsContextCollector.php
src/Explain/Collectors/EventContextCollector.php
src/Explain/Collectors/ExplainContextCollectorInterface.php
src/Explain/Collectors/ExtensionContextCollector.php
src/Explain/Collectors/GraphNeighborhoodCollector.php
src/Explain/Collectors/ImpactContextCollector.php
src/Explain/Collectors/PipelineContextCollector.php
src/Explain/Collectors/SchemaContextCollector.php
src/Explain/Collectors/WorkflowContextCollector.php
src/Explain/Contributors/ExplainContributorInterface.php
src/Explain/ExplainArtifactCatalog.php
src/Explain/ExplainContext.php
src/Explain/ExplainEngine.php
src/Explain/ExplainEngineFactory.php
src/Explain/ExplainEngineInterface.php
src/Explain/ExplainOptions.php
src/Explain/ExplainSubject.php
src/Explain/ExplainSupport.php
src/Explain/ExplainTarget.php
src/Explain/ExplainTargetResolver.php
src/Explain/ExplanationPlan.php
src/Explain/Renderers/ExplanationRendererInterface.php
src/Explain/Renderers/JsonExplanationRenderer.php
src/Explain/Renderers/MarkdownExplanationRenderer.php
src/Explain/Renderers/TextExplanationRenderer.php
src/Explain/RuleBasedSummaryBuilder.php
src/Pro/ArchitectureExplainer.php
src/Pro/CLI/ExplainCommand.php
src/Support/ApiSurfaceRegistry.php
tests/Unit/ExplainEngineTest.php
tests/Unit/ProAnalysisToolsTest.php
