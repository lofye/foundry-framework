# Spec 14 — Deterministic Compile Cache

Preface

Foundry’s compiler-oriented architecture should benefit from deterministic caching. Before 1.0, the framework should avoid unnecessary recompilation while preserving reproducibility and correctness.

All new code must maintain ≥ 90% automated test coverage.

Goals

Spec 14 must:
	•	speed up repeated compile/build operations
	•	preserve deterministic outputs
	•	reduce unnecessary recomputation
	•	support CI and local development

Requirements

1. Cache design

Add a compile cache keyed by stable inputs such as:
	•	config/schema hashes
	•	feature manifest hashes
	•	extension metadata hashes
	•	framework version / compatibility markers where relevant

2. Rebuild rules

The framework must rebuild only when relevant inputs change.

3. Determinism

Cache use must not make outputs non-deterministic.

4. Visibility

Developers should be able to tell:
	•	when cache is used
	•	when it is invalidated
	•	why recompilation happened

5. Control commands

Support:
	•	cache clear
	•	cache inspect/status where practical

Deliverables
	•	deterministic compile cache
	•	invalidation rules
	•	cache visibility/debugging
	•	cache control commands or equivalent functionality

Testing Requirements

Tests must cover:
	•	cache hits
	•	cache misses
	•	invalidation behavior
	•	deterministic outputs with/without cache
	•	cache-clearing behavior

Coverage must remain ≥ 90%.

==========================================================================================

RESULT

Implemented the deterministic compile-cache feature across the compiler and CLI surface. The core behavior lives in [GraphCompiler.php](/Users/lo_fye/code/foundry-framework/src/Compiler/GraphCompiler.php), [CompileCacheInspector.php](/Users/lo_fye/code/foundry-framework/src/Compiler/CompileCacheInspector.php), [EmitPass.php](/Users/lo_fye/code/foundry-framework/src/Compiler/Passes/EmitPass.php), and [GraphVerifier.php](/Users/lo_fye/code/foundry-framework/src/Compiler/GraphVerifier.php). The CLI additions are in [CacheInspectCommand.php](/Users/lo_fye/code/foundry-framework/src/CLI/Commands/CacheInspectCommand.php), [CacheClearCommand.php](/Users/lo_fye/code/foundry-framework/src/CLI/Commands/CacheClearCommand.php), [CompileGraphCommand.php](/Users/lo_fye/code/foundry-framework/src/CLI/Commands/CompileGraphCommand.php), and [InspectGraphCommand.php](/Users/lo_fye/code/foundry-framework/src/CLI/Commands/InspectGraphCommand.php). This adds stable cache keys, deterministic reuse, invalidation reasons, `cache inspect`, `cache clear`, and `compile graph --no-cache`.

I also added and extended coverage around the new behavior in [GraphCompilerTest.php](/Users/lo_fye/code/foundry-framework/tests/Unit/GraphCompilerTest.php), [CompileCacheInspectorTest.php](/Users/lo_fye/code/foundry-framework/tests/Unit/CompileCacheInspectorTest.php), [CLIGraphCommandsTest.php](/Users/lo_fye/code/foundry-framework/tests/Integration/CLIGraphCommandsTest.php), [CompileGraphCommandTest.php](/Users/lo_fye/code/foundry-framework/tests/Unit/CompileGraphCommandTest.php), [SourceScannerTest.php](/Users/lo_fye/code/foundry-framework/tests/Unit/SourceScannerTest.php), and [UpgradeAnalyzerTest.php](/Users/lo_fye/code/foundry-framework/tests/Unit/UpgradeAnalyzerTest.php). `php vendor/bin/phpunit` passes: `274 tests`, `1759 assertions`, `3 skipped`. Runtime checks also pass: `php bin/foundry compile graph --json`, `php bin/foundry cache inspect --json`, and `php bin/foundry verify graph --json`.

Exact full-suite coverage could not be re-run cleanly under Xdebug because PHPUnit crashes three process-isolated `CLIInitAppCommandTest` cases when coverage is enabled in this environment. Using the same Xdebug coverage command as the prior baseline, covered lines increased by `97/19354`, which moves the earlier `89.52%` baseline to about `90.02%` by inference. Existing unrelated dirty worktree changes remain untouched.

28 files changed
+1912
-17
Undo
src/CLI/Application.php
src/CLI/Commands/CacheClearCommand.php
src/CLI/Commands/CacheInspectCommand.php
src/CLI/Commands/CompileGraphCommand.php
src/CLI/Commands/InspectGraphCommand.php
src/Compiler/BuildLayout.php
src/Compiler/CompilationState.php
src/Compiler/CompileCacheInspector.php
src/Compiler/CompileOptions.php
src/Compiler/CompilePlanner.php
src/Compiler/CompileResult.php
src/Compiler/GraphCompiler.php
src/Compiler/GraphVerifier.php
src/Compiler/Passes/EmitPass.php
src/Support/ApiSurfaceRegistry.php
tests/Integration/CLIApplicationTest.php
tests/Integration/CLIGraphCommandsTest.php
tests/Unit/ApiSurfaceRegistryTest.php
tests/Unit/CLICommandMatchesTest.php
tests/Unit/CompileCacheInspectorTest.php
tests/Unit/CompileGraphCommandTest.php
tests/Unit/CoverageBoostCompilerTest.php
tests/Unit/FeatureLoaderEdgeCasesTest.php
tests/Unit/GraphCompilerTest.php
tests/Unit/JsonSupportTest.php
tests/Unit/SourceScannerTest.php
tests/Unit/UpgradeAnalyzerTest.php
tests/Unit/UpgradeSupportTypesTest.php
