19D is implemented and aligned with the foundation requirements.

The main foundation changes were structural, not feature creep. I added a dedicated plan assembler in [src/Explain/ExplanationPlanAssembler.php](/Users/lo_fye/code/foundry-framework/src/Explain/ExplanationPlanAssembler.php), a dedicated subject normalizer in [src/Explain/ExplainSubjectFactory.php](/Users/lo_fye/code/foundry-framework/src/Explain/ExplainSubjectFactory.php), and explicit resolver-domain error classes in [src/Explain/AmbiguousExplainTargetException.php](/Users/lo_fye/code/foundry-framework/src/Explain/AmbiguousExplainTargetException.php) and [src/Explain/UnsupportedExplainTargetException.php](/Users/lo_fye/code/foundry-framework/src/Explain/UnsupportedExplainTargetException.php). [src/Explain/ExplainEngine.php](/Users/lo_fye/code/foundry-framework/src/Explain/ExplainEngine.php) now orchestrates and delegates final plan shaping to the assembler instead of assembling the `ExplanationPlan` inline, and [src/Explain/ExplainTargetResolver.php](/Users/lo_fye/code/foundry-framework/src/Explain/ExplainTargetResolver.php) now routes subject creation through the factory and fails cleanly for unsupported kinds. I also made the foundation DTO contracts more explicit in [src/Explain/ExplainContext.php](/Users/lo_fye/code/foundry-framework/src/Explain/ExplainContext.php), [src/Explain/ExplainTarget.php](/Users/lo_fye/code/foundry-framework/src/Explain/ExplainTarget.php), [src/Explain/ExplainOptions.php](/Users/lo_fye/code/foundry-framework/src/Explain/ExplainOptions.php), and [src/Pro/CLI/ExplainCommand.php](/Users/lo_fye/code/foundry-framework/src/Pro/CLI/ExplainCommand.php).

To keep section ordering centralized, I removed renderer-owned section sorting and made the renderers in [src/Explain/Renderers/TextExplanationRenderer.php](/Users/lo_fye/code/foundry-framework/src/Explain/Renderers/TextExplanationRenderer.php) and [src/Explain/Renderers/MarkdownExplanationRenderer.php](/Users/lo_fye/code/foundry-framework/src/Explain/Renderers/MarkdownExplanationRenderer.php) consume plan order directly. That leaves resolution, normalization, orchestration, assembly, and rendering clearly separated. The CLI command is still thin and the renderers still do not touch raw graph/projection data.

I added foundation coverage in [tests/Unit/ExplainFoundationTest.php](/Users/lo_fye/code/foundry-framework/tests/Unit/ExplainFoundationTest.php) and kept the existing explain contract coverage in [tests/Unit/ExplainEngineTest.php](/Users/lo_fye/code/foundry-framework/tests/Unit/ExplainEngineTest.php), [tests/Unit/ExplainArchitectureCoverageTest.php](/Users/lo_fye/code/foundry-framework/tests/Unit/ExplainArchitectureCoverageTest.php), [tests/Unit/ExplainRenderersTest.php](/Users/lo_fye/code/foundry-framework/tests/Unit/ExplainRenderersTest.php), and [tests/Integration/CLIProCommandsTest.php](/Users/lo_fye/code/foundry-framework/tests/Integration/CLIProCommandsTest.php). Coverage is now `93.26%` for the explain slice, and `92.89%` including the adjacent CLI/help layers.

Verified with:
- `php vendor/bin/phpunit tests/Unit/ExplainFoundationTest.php tests/Unit/ExplainRenderersTest.php tests/Unit/ExplainEngineTest.php tests/Unit/ExplainArchitectureCoverageTest.php tests/Unit/ProAnalysisToolsTest.php tests/Unit/ApiSurfaceRegistryTest.php tests/Unit/CLIApplicationEdgeCasesTest.php tests/Unit/CLICommandMatchesTest.php tests/Integration/CLIProCommandsTest.php tests/Integration/CLIApplicationTest.php`
- `php -d zend_extension=/opt/homebrew/lib/php/pecl/20240924/xdebug.so -d xdebug.mode=coverage vendor/bin/phpunit --coverage-clover /tmp/explain-clover-19d.xml ...`
- `php bin/foundry verify graph --json`
- `php bin/foundry verify pipeline --json`
- `php bin/foundry verify contracts --json`

The 19D exit criteria are satisfied: clean layers, explicit DTOs, deterministic resolution, centralized plan assembly, real text/JSON renderers, clear error handling, and no architectural shortcuts that would block 19E.

14 files changed
+539
-228
Undo
src/Explain/AmbiguousExplainTargetException.php
src/Explain/ExplainContext.php
src/Explain/ExplainEngine.php
src/Explain/ExplainEngineFactory.php
src/Explain/ExplainOptions.php
src/Explain/ExplainSubjectFactory.php
src/Explain/ExplainTarget.php
src/Explain/ExplainTargetResolver.php
src/Explain/ExplanationPlanAssembler.php
src/Explain/Renderers/MarkdownExplanationRenderer.php
src/Explain/Renderers/TextExplanationRenderer.php
src/Explain/UnsupportedExplainTargetException.php
src/Pro/CLI/ExplainCommand.php
tests/Unit/ExplainFoundationTest.php


















------------------------------------------------------------------------------------------
