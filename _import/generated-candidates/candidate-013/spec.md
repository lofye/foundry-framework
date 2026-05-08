# Spec 11 — Graph Visualization CLI and Export System

Preface

Foundry’s architecture graph is one of its most distinctive strengths. Before 1.0, developers should be able to inspect and export that graph directly from the CLI in ways useful for humans, tooling, and docs.

All new code must maintain ≥ 90% automated test coverage.

Goals

Spec 11 must:
	•	make graph inspection easier
	•	provide export formats
	•	improve developer understanding of application structure
	•	support docs/tooling integration

Requirements

1. Graph inspection commands

Expand or stabilize commands such as:

foundry graph inspect
foundry graph visualize
foundry inspect graph

Codex may settle on the final command naming, but it must be consistent and public.

2. Export formats

Support export to at least:
	•	JSON
	•	DOT / Graphviz

Optional later:
	•	Mermaid
	•	SVG

3. Scope filters

Allow filtering or slicing by:
	•	feature
	•	extension
	•	pipeline
	•	command
	•	event/workflow area

4. Human-readable summaries

Provide concise summaries for developers who want quick understanding without parsing raw graph JSON.

5. Docs integration

Ensure graph export remains easy for the docs site and architecture explorer to consume.

Deliverables
	•	stable graph inspection CLI
	•	export formats
	•	filtering support
	•	docs/tooling-friendly outputs

Testing Requirements

Tests must cover:
	•	graph export correctness
	•	filtering
	•	output schemas
	•	CLI behavior
	•	graph summary output

Coverage must remain ≥ 90%.

==========================================================================================

RESULT

Implemented Spec 11 around a shared graph slice/export pipeline in [src/Compiler/Visualization/GraphVisualizer.php](/Users/lo_fye/code/foundry-framework/src/Compiler/Visualization/GraphVisualizer.php), then wired it into stable CLI surfaces: richer `inspect graph` and `graph inspect` in [src/CLI/Commands/InspectGraphCommand.php](/Users/lo_fye/code/foundry-framework/src/CLI/Commands/InspectGraphCommand.php), rendered alias behavior in [src/CLI/Commands/GraphVisualizeCommand.php](/Users/lo_fye/code/foundry-framework/src/CLI/Commands/GraphVisualizeCommand.php), and new file-writing exports in [src/CLI/Commands/ExportGraphCommand.php](/Users/lo_fye/code/foundry-framework/src/CLI/Commands/ExportGraphCommand.php). The CLI now supports JSON/DOT/Mermaid/SVG output, human-readable summaries, and filtering by feature, extension, pipeline stage, command target, event, workflow, and area. I also updated the public command registry/docs in [src/Support/ApiSurfaceRegistry.php](/Users/lo_fye/code/foundry-framework/src/Support/ApiSurfaceRegistry.php), [README.md](/Users/lo_fye/code/foundry-framework/README.md), and the architecture docs/examples.

Coverage and behavior are backed by updated CLI/unit coverage in [tests/Integration/CLIArchitectureToolsCommandsTest.php](/Users/lo_fye/code/foundry-framework/tests/Integration/CLIArchitectureToolsCommandsTest.php), [tests/Unit/GraphVisualizerTest.php](/Users/lo_fye/code/foundry-framework/tests/Unit/GraphVisualizerTest.php), [tests/Unit/GraphInspectionSupportTest.php](/Users/lo_fye/code/foundry-framework/tests/Unit/GraphInspectionSupportTest.php), and related registry/help tests. Verification run: `php vendor/bin/phpunit` passed, `XDEBUG_MODE=coverage php vendor/bin/phpunit --coverage-text` reported 90.03% line coverage, `php bin/foundry compile graph --json` and `php bin/foundry export graph --format=json --json` passed. `php bin/foundry verify graph --json` still reports one existing integrity warning for [app/generated/feature_index.php](/Users/lo_fye/code/foundry-framework/app/generated/feature_index.php); I restored that generated file so the diff stays focused on source, tests, and docs.

20 files changed
+1819
-183
Undo
README.md
docs/architecture-tools.md
docs/execution-pipeline.md
docs/public-api-policy.md
examples/architecture-tools/README.md
examples/execution-pipeline/README.md
src/CLI/Application.php
src/CLI/Commands/Concerns/InteractsWithGraphInspection.php
src/CLI/Commands/ExportGraphCommand.php
src/CLI/Commands/GraphVisualizeCommand.php
src/CLI/Commands/InspectGraphCommand.php
src/Compiler/Visualization/GraphVisualizer.php
src/Support/ApiSurfaceRegistry.php
tests/Integration/CLIApplicationTest.php
tests/Integration/CLIArchitectureToolsCommandsTest.php
tests/Unit/ApiSurfaceRegistryTest.php
tests/Unit/CLICommandMatchesTest.php
tests/Unit/GraphDocsGeneratorTest.php
tests/Unit/GraphInspectionSupportTest.php
tests/Unit/GraphVisualizerTest.php
