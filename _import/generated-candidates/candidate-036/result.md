Implemented the 19K graph-contract upgrade across the compiler, verifier, CLI, and docs. The core work lives in [src/Compiler/GraphSpec](\/Users/lo_fye/code/foundry-framework/src/Compiler/GraphSpec), with [ApplicationGraph.php](/Users/lo_fye/code/foundry-framework/src/Compiler/ApplicationGraph.php), [Nodes.php](/Users/lo_fye/code/foundry-framework/src/Compiler/IR/Nodes.php), [GraphVerifier.php](/Users/lo_fye/code/foundry-framework/src/Compiler/GraphVerifier.php), [InspectGraphCommand.php](/Users/lo_fye/code/foundry-framework/src/CLI/Commands/InspectGraphCommand.php), [VerifyGraphCommand.php](/Users/lo_fye/code/foundry-framework/src/CLI/Commands/VerifyGraphCommand.php), and [DoctorCommand.php](/Users/lo_fye/code/foundry-framework/src/CLI/Commands/DoctorCommand.php) wired to it. The graph is now explicitly versioned at `graph_version = 2` with `graph_spec_version = 1`, unknown node fallback to `FeatureNode` is gone, edge legality is registry-backed, integrity checks are first-class, subgraph extraction/fingerprints are available, and the JSON artifact now carries graph/spec/integrity/compatibility/observability metadata.

I also aligned the human-facing contract in [docs/graph-spec.md](/Users/lo_fye/code/foundry-framework/docs/graph-spec.md) and expanded coverage in [tests/Integration/CLIGraphCommandsTest.php](/Users/lo_fye/code/foundry-framework/tests/Integration/CLIGraphCommandsTest.php), [tests/Unit/GraphSpecRegistryTest.php](/Users/lo_fye/code/foundry-framework/tests/Unit/GraphSpecRegistryTest.php), and [tests/Unit/NodeFactoryTest.php](/Users/lo_fye/code/foundry-framework/tests/Unit/NodeFactoryTest.php). One last gap I found during verification was that `verify graph-integrity --json` wasn’t surfacing version metadata; that’s fixed now in [GraphIntegrityReport.php](/Users/lo_fye/code/foundry-framework/src/Compiler/GraphSpec/GraphIntegrityReport.php).

Verification passed with:
- `php bin/foundry compile graph --json`
- `php bin/foundry verify graph --json`
- `php bin/foundry verify graph-integrity --json`
- `php bin/foundry doctor --graph --json`

Focused PHPUnit also passed:
- `php vendor/bin/phpunit tests/Integration/CLIGraphCommandsTest.php tests/Unit/FrameworkDoctorTest.php tests/Unit/GraphCompilerTest.php tests/Unit/GraphSpecRegistryTest.php tests/Unit/NodeFactoryTest.php`

I didn’t run the full repository PHPUnit suite, only the graph-related slice above.

34 files changed
+2467
-390
Undo
docs/graph-spec.md
src/CLI/Commands/DoctorCommand.php
src/CLI/Commands/InspectGraphCommand.php
src/CLI/Commands/VerifyGraphCommand.php
src/Compiler/ApplicationGraph.php
src/Compiler/CompileResult.php
src/Compiler/Extensions/CoreCompilerExtension.php
src/Compiler/Extensions/FoundationCompilerExtension.php
src/Compiler/Extensions/IntegrationCompilerExtension.php
src/Compiler/Extensions/PlatformCompilerExtension.php
src/Compiler/GraphCompiler.php
src/Compiler/GraphSpec/CanonicalGraphSpecification.php
src/Compiler/GraphSpec/EdgeTypeDefinition.php
src/Compiler/GraphSpec/GraphArtifactMigrator.php
src/Compiler/GraphSpec/GraphCompatibility.php
src/Compiler/GraphSpec/GraphIntegrityReport.php
src/Compiler/GraphSpec/GraphIntegrityVerifier.php
src/Compiler/GraphSpec/GraphSpecification.php
src/Compiler/GraphSpec/IllegalGraphEdge.php
src/Compiler/GraphSpec/NodeTypeDefinition.php
src/Compiler/GraphSpec/UnknownGraphEdgeType.php
src/Compiler/GraphSpec/UnknownGraphNodeType.php
src/Compiler/GraphVerifier.php
src/Compiler/IR/Nodes.php
src/Compiler/Passes/EmitPass.php
src/Doctor/Checks/GraphIntegrityCheck.php
src/Extensions/Demo/DemoCapabilityExtension.php
src/Support/ApiSurfaceRegistry.php
tests/Integration/CLIGraphCommandsTest.php
tests/Unit/CLICommandMatchesTest.php
tests/Unit/CompatibilityCheckerTest.php
tests/Unit/GraphCompilerTest.php
tests/Unit/GraphSpecRegistryTest.php
tests/Unit/NodeFactoryTest.php

------------------------------------------------------------------------------------------

Please run the full repository PHPUnit suite to ensure >= 90% test coverage of the entire codebase.

------------------------------------------------------------------------------------------

The full repository PHPUnit suite now passes, and the repo clears the `>= 90%` line-coverage bar.

I had to load Xdebug explicitly because the default Herd PHP runtime did not expose a coverage driver by default. After fixing three full-suite regressions and adding focused coverage around the new graph-contract code, the final run succeeded with `372` tests, `2938` assertions, `10` skips, and repository-wide coverage of `90.15%` lines, `70.79%` methods, and `48.39%` classes.

The small fixes needed to get the suite green were in [src/CLI/CliSurfaceVerifier.php](/Users/lo_fye/code/foundry-framework/src/CLI/CliSurfaceVerifier.php) and [tests/Fixtures/CustomUpgradeExtension.php](/Users/lo_fye/code/foundry-framework/tests/Fixtures/CustomUpgradeExtension.php). The coverage lift came from new tests in [tests/Unit/GraphIntegrityVerifierTest.php](/Users/lo_fye/code/foundry-framework/tests/Unit/GraphIntegrityVerifierTest.php) and [tests/Unit/ApplicationGraphContractTest.php](/Users/lo_fye/code/foundry-framework/tests/Unit/ApplicationGraphContractTest.php).

Command used for the authoritative coverage run:
```bash
XDEBUG_MODE=coverage php -d zend_extension=/opt/homebrew/lib/php/pecl/20240924/xdebug.so vendor/bin/phpunit --coverage-text
```

4 files changed
+772
-2
Undo
src/CLI/CliSurfaceVerifier.php
tests/Fixtures/CustomUpgradeExtension.php
tests/Unit/ApplicationGraphContractTest.php
tests/Unit/GraphIntegrityVerifierTest.php
