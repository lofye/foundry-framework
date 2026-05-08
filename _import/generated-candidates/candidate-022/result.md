19B is implemented on top of the 19A architecture.

The main gaps I closed were the end-to-end parts that were still thin: real docs metadata, real contributor wiring, and richer plan-driven human output. [src/Explain/ExplainArtifactCatalog.php](/Users/lo_fye/code/foundry-framework/src/Explain/ExplainArtifactCatalog.php) now exposes deterministic docs metadata from curated and generated docs pages, [src/Explain/Collectors/DocsContextCollector.php](/Users/lo_fye/code/foundry-framework/src/Explain/Collectors/DocsContextCollector.php) resolves related docs for supported subject kinds, and [src/Explain/ExplainEngine.php](/Users/lo_fye/code/foundry-framework/src/Explain/ExplainEngine.php) merges that into the `ExplanationPlan`. Contributor support is now a real hook rather than just an interface: [src/Explain/ExplainEngineFactory.php](/Users/lo_fye/code/foundry-framework/src/Explain/ExplainEngineFactory.php) and [src/Pro/ArchitectureExplainer.php](/Users/lo_fye/code/foundry-framework/src/Pro/ArchitectureExplainer.php) accept `ExplainContributorInterface` instances, and [src/Support/ApiSurfaceRegistry.php](/Users/lo_fye/code/foundry-framework/src/Support/ApiSurfaceRegistry.php) classifies that contributor interface as an extension hook.

I also made the renderers fully useful for 19B instead of just skeletal. [src/Explain/Renderers/TextExplanationRenderer.php](/Users/lo_fye/code/foundry-framework/src/Explain/Renderers/TextExplanationRenderer.php) and [src/Explain/Renderers/MarkdownExplanationRenderer.php](/Users/lo_fye/code/foundry-framework/src/Explain/Renderers/MarkdownExplanationRenderer.php) now render sections, dependencies, execution flow, diagnostics, related commands, and related docs from the plan. The docs were updated in [docs/architecture-tools.md](/Users/lo_fye/code/foundry-framework/docs/architecture-tools.md), [docs/extension-author-guide.md](/Users/lo_fye/code/foundry-framework/docs/extension-author-guide.md), and [README.md](/Users/lo_fye/code/foundry-framework/README.md). Test coverage was extended in [tests/Unit/ExplainArchitectureCoverageTest.php](/Users/lo_fye/code/foundry-framework/tests/Unit/ExplainArchitectureCoverageTest.php), [tests/Unit/ExplainEngineTest.php](/Users/lo_fye/code/foundry-framework/tests/Unit/ExplainEngineTest.php), [tests/Unit/ApiSurfaceRegistryTest.php](/Users/lo_fye/code/foundry-framework/tests/Unit/ApiSurfaceRegistryTest.php), and [tests/Integration/CLIProCommandsTest.php](/Users/lo_fye/code/foundry-framework/tests/Integration/CLIProCommandsTest.php).

Verification:
- Explain-slice coverage: `90.85%` (`1380/1519`) for `src/Explain`, [src/Pro/ArchitectureExplainer.php](/Users/lo_fye/code/foundry-framework/src/Pro/ArchitectureExplainer.php), and [src/Pro/CLI/ExplainCommand.php](/Users/lo_fye/code/foundry-framework/src/Pro/CLI/ExplainCommand.php)
- `php vendor/bin/phpunit tests/Unit/ExplainEngineTest.php tests/Unit/ExplainArchitectureCoverageTest.php tests/Unit/ProAnalysisToolsTest.php tests/Unit/ApiSurfaceRegistryTest.php tests/Unit/CLICommandMatchesTest.php tests/Integration/CLIProCommandsTest.php tests/Integration/CLIApplicationTest.php`
- `php bin/foundry compile graph --json`
- `php bin/foundry verify graph --json`
- `php bin/foundry verify pipeline --json`
- `php bin/foundry verify contracts --json`

This leaves 19B in a good state: deterministic target resolution, engine/collector/analyzer pipeline, plan-backed text/json/markdown output, diagnostics and docs integration, contributor extensibility, and coverage above the required threshold.

15 files changed
+647
-7
Undo
README.md
docs/architecture-tools.md
docs/extension-author-guide.md
src/Explain/Collectors/DocsContextCollector.php
src/Explain/ExplainArtifactCatalog.php
src/Explain/ExplainEngine.php
src/Explain/ExplainEngineFactory.php
src/Explain/Renderers/MarkdownExplanationRenderer.php
src/Explain/Renderers/TextExplanationRenderer.php
src/Pro/ArchitectureExplainer.php
src/Support/ApiSurfaceRegistry.php
tests/Integration/CLIProCommandsTest.php
tests/Unit/ApiSurfaceRegistryTest.php
tests/Unit/ExplainArchitectureCoverageTest.php
tests/Unit/ExplainEngineTest.php
