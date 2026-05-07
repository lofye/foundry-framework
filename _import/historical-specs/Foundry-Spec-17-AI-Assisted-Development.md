Spec 17 — AI-Assisted Development Layer

Preface

One of Foundry’s primary goals is to enable reliable LLM-assisted development.

Unlike traditional frameworks, Foundry’s:
	•	canonical graph
	•	schemas
	•	manifests
	•	execution pipeline

provide structured context that can be used to generate correct, integrated code.

Spec 17 introduces an AI-assisted layer that leverages this structure.

All new code must maintain ≥ 90% automated test coverage.

⸻

Goals

Spec 17 must:
	•	enable prompt-driven feature generation
	•	ensure generated code integrates correctly into the Foundry graph
	•	provide architecture-aware explanations
	•	remain deterministic and inspectable

⸻

Requirements

1. Prompt-to-feature generation

Provide CLI command:

foundry generate "<prompt>"

The system must:
	•	interpret user intent
	•	generate:
	•	feature structure
	•	manifests
	•	schemas
	•	workflows/events where appropriate
	•	integrate into existing application graph

⸻

2. Graph-aware generation

Generated code must:
	•	register correctly in the graph
	•	respect feature boundaries
	•	integrate with:
	•	pipeline
	•	events
	•	workflows
	•	permissions

⸻

3. Deterministic output mode

Provide a mode where generation is:
	•	reproducible
	•	traceable
	•	based on explicit inputs

Optional:

foundry generate "<prompt>" --deterministic


⸻

4. Explanation capability

Provide:

foundry explain <target>

This must:
	•	describe pipeline execution
	•	show guards, events, workflows
	•	reference graph relationships

⸻

5. Safety mechanisms

Generated code must:
	•	pass schema validation (Spec 12)
	•	integrate without breaking graph integrity
	•	optionally run linting or doctor checks after generation

⸻

6. Pluggable AI providers

The system should allow:
	•	different AI providers
	•	local or remote models

No provider should be hard-coded.

⸻

7. No hard dependency

AI features must be optional:
	•	framework works without them
	•	generation gracefully fails if no provider configured

⸻

Deliverables
	•	foundry generate command
	•	graph-aware generation system
	•	explanation tooling
	•	provider abstraction layer
	•	integration with validation and diagnostics

⸻

Testing Requirements

Tests must cover:
	•	generation output structure
	•	graph integration correctness
	•	deterministic mode behavior
	•	provider abstraction
	•	failure modes

Coverage must remain ≥ 90%.

⸻⸻⸻⸻⸻⸻⸻⸻⸻⸻⸻⸻⸻⸻⸻⸻⸻⸻⸻⸻---

RESULT (Spec 17)

Implemented Spec 17 in the Pro layer. `generate` is now a real graph-aware plan/apply flow in [src/Pro/CLI/GenerateCommand.php](/Users/lo_fye/code/foundry-framework/src/Pro/CLI/GenerateCommand.php) backed by [src/Pro/Generation/AIGenerationService.php](/Users/lo_fye/code/foundry-framework/src/Pro/Generation/AIGenerationService.php) and [src/Pro/Generation/PromptFeaturePlanner.php](/Users/lo_fye/code/foundry-framework/src/Pro/Generation/PromptFeaturePlanner.php). It supports deterministic mode, provider-backed mode, dry runs, graph-safe application, and graceful failure when no provider is configured. Provider selection is now pluggable through [src/AI/AIProviderRegistry.php](/Users/lo_fye/code/foundry-framework/src/AI/AIProviderRegistry.php), with runtime/config wiring updated in [src/Core/RuntimeFactory.php](/Users/lo_fye/code/foundry-framework/src/Core/RuntimeFactory.php) and [src/Config/ConfigSchemaCatalog.php](/Users/lo_fye/code/foundry-framework/src/Config/ConfigSchemaCatalog.php).

`explain` is richer now too: [src/Pro/ArchitectureExplainer.php](/Users/lo_fye/code/foundry-framework/src/Pro/ArchitectureExplainer.php) includes pipeline stages, guards, events, workflows, and graph impact context. Help/docs were updated in [src/Support/ApiSurfaceRegistry.php](/Users/lo_fye/code/foundry-framework/src/Support/ApiSurfaceRegistry.php), [README.md](/Users/lo_fye/code/foundry-framework/README.md), and [docs/architecture-tools.md](/Users/lo_fye/code/foundry-framework/docs/architecture-tools.md). Coverage for the new behavior lives in [tests/Integration/CLIProCommandsTest.php](/Users/lo_fye/code/foundry-framework/tests/Integration/CLIProCommandsTest.php), [tests/Unit/AIProviderRegistryTest.php](/Users/lo_fye/code/foundry-framework/tests/Unit/AIProviderRegistryTest.php), [tests/Unit/AIConfigLoaderTest.php](/Users/lo_fye/code/foundry-framework/tests/Unit/AIConfigLoaderTest.php), [tests/Unit/GeneratedFeatureApplierTest.php](/Users/lo_fye/code/foundry-framework/tests/Unit/GeneratedFeatureApplierTest.php), and [tests/Unit/PromptFeaturePlannerTest.php](/Users/lo_fye/code/foundry-framework/tests/Unit/PromptFeaturePlannerTest.php).

Verification passed: `php vendor/bin/phpunit` with `321` tests, `2370` assertions, `3` skipped. Coverage pass also succeeded: `XDEBUG_MODE=coverage php vendor/bin/phpunit --coverage-text` reported `90.08%` line coverage.

23 files changed
+1975
-50
Undo
README.md
docs/architecture-tools.md
src/AI/AIProviderRegistry.php
src/AI/ProviderFactory.php
src/AI/StaticAIProviderFactory.php
src/Config/ConfigSchemaCatalog.php
src/Core/RuntimeFactory.php
src/Pro/ArchitectureExplainer.php
src/Pro/CLI/GenerateCommand.php
src/Pro/Generation/AIConfigLoader.php
src/Pro/Generation/AIGenerationService.php
src/Pro/Generation/GeneratedFeatureApplier.php
src/Pro/Generation/PromptFeaturePlanner.php
src/Support/ApiSurfaceRegistry.php
tests/Fixtures/CustomAIProviderFactory.php
tests/Integration/CLIApplicationTest.php
tests/Integration/CLIProCommandsTest.php
tests/Unit/AIConfigLoaderTest.php
tests/Unit/AIProviderRegistryTest.php
tests/Unit/ApiSurfaceRegistryTest.php
tests/Unit/GeneratedFeatureApplierTest.php
tests/Unit/ProAnalysisToolsTest.php
tests/Unit/PromptFeaturePlannerTest.php