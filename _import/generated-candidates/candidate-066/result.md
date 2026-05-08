Implemented Spec 30F with a single deterministic scoring layer in [src/Confidence/ConfidenceEngine.php](/Users/lo_fye/code/foundry-framework/src/Confidence/ConfidenceEngine.php). Explain now carries additive `confidence` data through [src/Explain/ExplainModel.php](/Users/lo_fye/code/foundry-framework/src/Explain/ExplainModel.php), [src/Explain/ExplanationPlan.php](/Users/lo_fye/code/foundry-framework/src/Explain/ExplanationPlan.php), and [src/Explain/ExplainEngine.php](/Users/lo_fye/code/foundry-framework/src/Explain/ExplainEngine.php); snapshots and architectural diffs now persist confidence via [src/Explain/Snapshot/ExplainSnapshotService.php](/Users/lo_fye/code/foundry-framework/src/Explain/Snapshot/ExplainSnapshotService.php) and [src/Explain/Diff/ExplainDiffService.php](/Users/lo_fye/code/foundry-framework/src/Explain/Diff/ExplainDiffService.php); and generate now emits both `plan_confidence` and `outcome_confidence` from [src/Generate/GenerateEngine.php](/Users/lo_fye/code/foundry-framework/src/Generate/GenerateEngine.php) with the confidence contract embedded in [src/Generate/GenerationPlan.php](/Users/lo_fye/code/foundry-framework/src/Generate/GenerationPlan.php). Human-facing output was updated too in [src/CLI/Commands/GenerateCommand.php](/Users/lo_fye/code/foundry-framework/src/CLI/Commands/GenerateCommand.php), [src/Explain/Renderers/TextExplanationRenderer.php](/Users/lo_fye/code/foundry-framework/src/Explain/Renderers/TextExplanationRenderer.php), and [src/Explain/Renderers/MarkdownExplanationRenderer.php](/Users/lo_fye/code/foundry-framework/src/Explain/Renderers/MarkdownExplanationRenderer.php).

I also aligned the docs in [README.md](/Users/lo_fye/code/foundry-framework/README.md), [docs/explain.md](/Users/lo_fye/code/foundry-framework/docs/explain.md), [docs/generate.md](/Users/lo_fye/code/foundry-framework/docs/generate.md), [docs/architecture-tools.md](/Users/lo_fye/code/foundry-framework/docs/architecture-tools.md), [APP-README.md](/Users/lo_fye/code/foundry-framework/APP-README.md), and [APP-AGENTS.md](/Users/lo_fye/code/foundry-framework/APP-AGENTS.md), and added coverage in [tests/Unit/ConfidenceEngineTest.php](/Users/lo_fye/code/foundry-framework/tests/Unit/ConfidenceEngineTest.php) plus the explain/generate integration tests. One intentional boundary: there still is not a separate Spec 36 interactive review surface or persisted-plan store in this repo, so confidence is now carried in the existing explain/plan/generate payloads those later phases can build on.

Verification passed:
- `php bin/foundry verify cli-surface --json` -> `131` valid, `0` invalid, `0` ambiguous, `0` orphan handlers, coverage `1`
- `php bin/foundry compile graph --json` -> success
- `php bin/foundry verify graph --json` -> `ok: true`
- `php vendor/bin/phpunit` -> `415` tests, `3314` assertions

26 files changed
+1174
-25
Undo
APP-AGENTS.md
APP-README.md
README.md
docs/architecture-tools.md
docs/explain.md
docs/generate.md
src/CLI/Commands/GenerateCommand.php
src/Confidence/ConfidenceEngine.php
src/Explain/Diff/ExplainDiffService.php
src/Explain/ExplainEngine.php
src/Explain/ExplainModel.php
src/Explain/ExplanationPlan.php
src/Explain/Renderers/MarkdownExplanationRenderer.php
src/Explain/Renderers/TextExplanationRenderer.php
src/Explain/Snapshot/ExplainSnapshotService.php
src/Generate/GenerateEngine.php
src/Generate/GenerationContextPacket.php
src/Generate/GenerationPlan.php
src/Support/ApiSurfaceRegistry.php
tests/Integration/CLIGenerateCommandTest.php
tests/Integration/CLILicensedCommandsTest.php
tests/Unit/ConfidenceEngineTest.php
tests/Unit/ExplainDiffServiceTest.php
tests/Unit/ExplainEngineTest.php
tests/Unit/ExplainPolishTest.php
tests/Unit/ExplainSnapshotServiceTest.php


------------------------------------------------------------------------------------------



------------------------------------------------------------------------------------------



------------------------------------------------------------------------------------------
