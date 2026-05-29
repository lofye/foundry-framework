# Implementation Plan: 035-generate-system-end-to-end-explain-driven-pack-aware

## Historical Provenance

- Imported spec path: `Modules/PreCanonical/specs/035-generate-system-end-to-end-explain-driven-pack-aware.md`
- Source archive: `_import/pre-canonical-specs.md`
- Legacy name: `35 — Generate System (End-to-End, Explain-Driven, Pack-Aware)`
- Legacy id: `35`
- Canonical pre-canonical id: `035`

## Historical Specification Summary

The original pre-canonical specification body is preserved in the imported execution spec. This reconstruction note records adjacent marked context and result evidence without inferring modern module ownership.

## Historical Preamble Context

### Preamble Block 1

- Name: `35 — Generate System (End-to-End, Explain-Driven, Pack-Aware)`

🧭 Preface

While implementing this Spec, you MUST respect:
	•	Specs 30–32 (core architecture constraints)
	•	Spec 33 (Explain canonical model + extension awareness)
	•	Spec 34 (Pack system + deterministic integration)

⸻

🧠 Purpose

Build a complete generate system that transforms developer intent into safe, architecture-aware modifications using:
	•	explain-derived system understanding
	•	deterministic planning
	•	pack-aware generation (NEW)
	•	controlled execution
	•	verification loops

This is the execution engine of Foundry.

⸻

🧠 Core Principle

Generate is not:

prompt → code

Generate is:

intent
→ explain-derived context
→ pack-aware planning
→ controlled execution
→ verification loop

## Historical Implementation Evidence

### Result Block 1

- Name: `35 — Generate System (End-to-End, Explain-Driven, Pack-Aware)`

RESULT

Implemented Spec 35 end to end. `generate` is now an explain-driven, pack-aware execution path instead of the old provider wrapper: [GenerateCommand.php](/Users/lo_fye/code/foundry-framework/src/CLI/Commands/GenerateCommand.php) now requires `--mode=new|modify|repair`, and the new engine in [GenerateEngine.php](/Users/lo_fye/code/foundry-framework/src/Generate/GenerateEngine.php) builds an explain-backed context packet, resolves pack requirements, selects deterministic generators, validates plans, executes them, and rolls changes back if verification fails. The core generator flow lives in [GeneratorRegistry.php](/Users/lo_fye/code/foundry-framework/src/Generate/GeneratorRegistry.php), [GenerationPlanner.php](/Users/lo_fye/code/foundry-framework/src/Generate/GenerationPlanner.php), [PlanValidator.php](/Users/lo_fye/code/foundry-framework/src/Generate/PlanValidator.php), and the mode-specific generators under [src/Generate/Core/CoreNewFeatureGenerator.php](/Users/lo_fye/code/foundry-framework/src/Generate/Core/CoreNewFeatureGenerator.php), [CoreModifyFeatureGenerator.php](/Users/lo_fye/code/foundry-framework/src/Generate/Core/CoreModifyFeatureGenerator.php), and [CoreRepairFeatureGenerator.php](/Users/lo_fye/code/foundry-framework/src/Generate/Core/CoreRepairFeatureGenerator.php).

Packs can now contribute real runtime generators through [PackContext.php](/Users/lo_fye/code/foundry-framework/src/Packs/PackContext.php), [PackGeneratorDefinition.php](/Users/lo_fye/code/foundry-framework/src/Packs/PackGeneratorDefinition.php), and [InstalledPackExtension.php](/Users/lo_fye/code/foundry-framework/src/Packs/InstalledPackExtension.php). I updated the fixture blog pack to register an actual pack generator in [FoundryBlogPackServiceProvider.php](/Users/lo_fye/code/foundry-framework/tests/Fixtures/Packs/foundry-blog/src/FoundryBlogPackServiceProvider.php), added [BlogPostGenerator.php](/Users/lo_fye/code/foundry-framework/tests/Fixtures/Packs/foundry-blog/src/BlogPostGenerator.php), and switched its interceptor wiring to a real stage interceptor in [FoundryBlogStageInterceptor.php](/Users/lo_fye/code/foundry-framework/tests/Fixtures/Packs/foundry-blog/src/FoundryBlogStageInterceptor.php) so pack-origin generation survives full graph verification. Help/docs/discovery were updated in [ApiSurfaceRegistry.php](/Users/lo_fye/code/foundry-framework/src/Support/ApiSurfaceRegistry.php), [CommandCatalog.php](/Users/lo_fye/code/foundry-framework/src/Documentation/CommandCatalog.php), [ExplainArtifactCatalog.php](/Users/lo_fye/code/foundry-framework/src/Explain/ExplainArtifactCatalog.php), [README.md](/Users/lo_fye/code/foundry-framework/README.md), and [architecture-tools.md](/Users/lo_fye/code/foundry-framework/docs/architecture-tools.md).

Coverage was realigned in [CLILicensedCommandsTest.php](/Users/lo_fye/code/foundry-framework/tests/Integration/CLILicensedCommandsTest.php), [CLIGenerateCommandTest.php](/Users/lo_fye/code/foundry-framework/tests/Integration/CLIGenerateCommandTest.php), [CLIApplicationTest.php](/Users/lo_fye/code/foundry-framework/tests/Integration/CLIApplicationTest.php), [ApiSurfaceRegistryTest.php](/Users/lo_fye/code/foundry-framework/tests/Unit/ApiSurfaceRegistryTest.php), [GenerationPlannerTest.php](/Users/lo_fye/code/foundry-framework/tests/Unit/GenerationPlannerTest.php), and [PackRequirementResolverTest.php](/Users/lo_fye/code/foundry-framework/tests/Unit/PackRequirementResolverTest.php).

Verification passed:
- `php bin/foundry verify cli-surface --json` -> `128` valid, `0` invalid, `0` ambiguous, `0` orphan handlers, coverage `1`
- `php bin/foundry compile graph --json` -> completed successfully
- `php bin/foundry verify graph --json` -> `ok: true`
- `php vendor/bin/phpunit` -> `396` tests passed, `3124` assertions

35 files changed
+2640
-222
Undo
README.md
docs/architecture-tools.md
src/CLI/Commands/GenerateCommand.php
src/Documentation/CommandCatalog.php
src/Explain/ExplainArtifactCatalog.php
src/Generate/CodeWriter.php
src/Generate/Core/CoreModifyFeatureGenerator.php
src/Generate/Core/CoreNewFeatureGenerator.php
src/Generate/Core/CoreRepairFeatureGenerator.php
src/Generate/FeaturePlanBuilder.php
src/Generate/GenerateEngine.php
src/Generate/GenerationContextPacket.php
src/Generate/GenerationPlan.php
src/Generate/GenerationPlanner.php
src/Generate/Generator.php
src/Generate/GeneratorRegistry.php
src/Generate/Intent.php
src/Generate/PackRequirementResolver.php
src/Generate/PlanValidator.php
src/Generate/RegisteredGenerator.php
src/Packs/InstalledPackExtension.php
src/Packs/PackContext.php
src/Packs/PackGeneratorDefinition.php
src/Support/ApiSurfaceRegistry.php
tests/Fixtures/Packs/foundry-blog/foundry.json
tests/Fixtures/Packs/foundry-blog/src/BlogPostGenerator.php
tests/Fixtures/Packs/foundry-blog/src/FoundryBlogExtension.php
tests/Fixtures/Packs/foundry-blog/src/FoundryBlogPackServiceProvider.php
tests/Fixtures/Packs/foundry-blog/src/FoundryBlogStageInterceptor.php
tests/Integration/CLIApplicationTest.php
tests/Integration/CLIGenerateCommandTest.php
tests/Integration/CLILicensedCommandsTest.php
tests/Unit/ApiSurfaceRegistryTest.php
tests/Unit/GenerationPlannerTest.php
tests/Unit/PackRequirementResolverTest.php

## Historical Verification Evidence

Historical verification details, when present, are preserved verbatim inside the paired result blocks above.

## Historical Stabilization Notes

Historical stabilization details, when present, are preserved verbatim inside the paired result blocks above.

## Current Repository Alignment

The imported artifact is intentionally retained under `Modules/PreCanonical` as archive-lineage context. Modern module ownership remains deferred until a separate explicit alignment spec maps the pre-canonical intent into current modules.

## Uncertainty And Reconstruction Notes

No modern module inference was performed. The generated note preserves only the marked archive relationships available through `S`, `R`, and `P` blocks.
