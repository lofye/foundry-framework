# Implementation Plan: 001-roadmap-phase-1-foundation

## Historical Provenance

- Imported spec path: `Modules/PreCanonical/specs/001-roadmap-phase-1-foundation.md`
- Source archive: `_import/pre-canonical-specs.md`
- Legacy name: `1 - Roadmap Phase 1 - Foundation`
- Legacy id: `1`
- Canonical pre-canonical id: `001`

## Historical Specification Summary

The original pre-canonical specification body is preserved in the imported execution spec. This reconstruction note records adjacent marked context and result evidence without inferring modern module ownership.

## Historical Preamble Context

### Preamble Block 1

- Name: `1 - Roadmap Phase 1 - Foundation`

What features should I have Codex add next, based on what developers are most likely to ask their LLMs to create for them. Please spec as many as you think are necessary for a well-rounded modern web framework, and I'll get Codex to build them.

---

PHASE 1

Phase 0A, 0B, 0C, and 0D are now canonical.

In addition to integrating with the semantic compiler, canonical application graph, extension system, migration/versioning model, doctor/analysis tooling, and graph visualization system, all new capabilities in this phase must also integrate with the execution pipeline, feature guard model, interceptor system, and execution-plan inspection/verification tools introduced in Phase 0D.

Important rules:
- Do not introduce ad hoc middleware stacks or parallel runtime request-processing systems.
- Any auth, permission, CSRF, rate-limiting, request-validation, content-negotiation, webhook-verification, locale-resolution, streaming, or other cross-cutting behavior must use the canonical pipeline/guard/interceptor architecture where appropriate.
- New features should emit graph-visible execution plans and participate in pipeline diagnostics, inspection, and visualization.
- Where useful, new capabilities should also integrate with doctor, graph visualization, and prompt-context extraction so that LLMs and humans can inspect the resulting system structure.

In short:
All future phases must be graph-native, extension-native, migration-aware, and pipeline-native.


Before implementing this phase, adapt all generation, verification, and inspection work to the new Foundry Phase 0 semantic compiler architecture.

Important rules for this phase:
- Do not introduce any new parallel truth systems.
- All new starter kits, resources, forms, admin features, uploads, and listing/toolkit metadata must compile into the canonical application graph.
- Any runtime indexes or generated metadata introduced by this phase must be emitted as projections from the graph, not generated independently.
- Any new spec/config formats introduced in this phase must:
  - be versioned
  - have migration/codemod support hooks
  - normalize cleanly into the graph IR
  - participate in compiler diagnostics
- Any new verification logic introduced in this phase must operate over the compiled graph where practical, rather than reparsing source files independently.
- Any new inspect commands introduced in this phase must query the graph or graph-derived projections.
- Any new generation commands must emit source-of-truth files first, then rely on compile/projection passes to produce runtime artifacts.
- Reuse the Phase 0 diagnostics, graph inspection, impact analysis, extension hooks, and migration/versioning systems wherever possible.
- If any part of this phase conflicts with the compiler-layer architecture, revise the phase implementation so the compiler layer remains canonical.

In short:
Phase 1 features must become graph-native Foundry capabilities, not bolt-on generators.

Here’s a single master prompt for Codex to build Foundry Roadmap Phase 1 — the highest-leverage next layer that will make Foundry feel like a real modern framework developers can hand to an LLM and actually get useful app slices back.

## Historical Implementation Evidence

### Result Block 1

- Name: `1 - Roadmap Phase 1 - Foundation`

Implemented. Phase 1 is now graph-native, extension-native, migration-aware, and pipeline-native, and coverage is back above 90%.

**What I added**
- New Phase 1 CLI surfaces:
  - `generate starter ...`, `generate resource ...`, `generate admin-resource ...`, `generate uploads ...`
  - `inspect resource <name>`
  - `verify resource <name>`
  - Command wiring in [Application.php](/Users/lo_fye/code/foundry/src/CLI/Application.php), [GeneratePhaseOneCommand.php](/Users/lo_fye/code/foundry/src/CLI/Commands/GeneratePhaseOneCommand.php), [InspectResourceCommand.php](/Users/lo_fye/code/foundry/src/CLI/Commands/InspectResourceCommand.php), [VerifyResourceCommand.php](/Users/lo_fye/code/foundry/src/CLI/Commands/VerifyResourceCommand.php)

- New generation subsystem:
  - [StarterGenerator.php](/Users/lo_fye/code/foundry/src/Generation/StarterGenerator.php)
  - [ResourceGenerator.php](/Users/lo_fye/code/foundry/src/Generation/ResourceGenerator.php)
  - [AdminResourceGenerator.php](/Users/lo_fye/code/foundry/src/Generation/AdminResourceGenerator.php)
  - [UploadsGenerator.php](/Users/lo_fye/code/foundry/src/Generation/UploadsGenerator.php)
  - [FormSchemaRenderer.php](/Users/lo_fye/code/foundry/src/Generation/FormSchemaRenderer.php)
  - Extended [FeatureGenerator.php](/Users/lo_fye/code/foundry/src/Generation/FeatureGenerator.php) with array-based generation + `--force` support and Phase 1 metadata passthrough.

- Compiler/graph integration for Phase 1 specs:
  - Discovery of `app/specs/*/*.yaml` in [SourceScanner.php](/Users/lo_fye/code/foundry/src/Compiler/SourceScanner.php) and [DiscoveryPass.php](/Users/lo_fye/code/foundry/src/Compiler/Passes/DiscoveryPass.php)
  - New pass [PhaseOneSpecPass.php](/Users/lo_fye/code/foundry/src/Compiler/Passes/PhaseOneSpecPass.php) to compile spec nodes/edges + diagnostics.
  - New IR node types in [Nodes.php](/Users/lo_fye/code/foundry/src/Compiler/IR/Nodes.php): `starter_kit`, `resource`, `admin_resource`, `upload_profile`, `listing_config`, `form_definition`.
  - New projections in [PhaseOneProjectionEmitters.php](/Users/lo_fye/code/foundry/src/Compiler/Projection/PhaseOneProjectionEmitters.php): `starter_index.php`, `resource_index.php`, `admin_resource_index.php`, `upload_profile_index.php`, `listing_index.php`, `form_index.php`.
  - Graph verifier updated in [GraphVerifier.php](/Users/lo_fye/code/foundry/src/Compiler/GraphVerifier.php).

- Extension/codemod integration:
  - New extension [PhaseOneCompilerExtension.php](/Users/lo_fye/code/foundry/src/Compiler/Extensions/PhaseOneCompilerExtension.php) registered by default in [ExtensionRegistry.php](/Users/lo_fye/code/foundry/src/Compiler/Extensions/ExtensionRegistry.php)
  - New codemod [PhaseOneSpecNormalizeCodemod.php](/Users/lo_fye/code/foundry/src/Compiler/Codemod/PhaseOneSpecNormalizeCodemod.php)

- Inspection/analysis updates:
  - Resource-aware impact/analyzer/visual labels updates in:
    - [ImpactAnalyzer.php](/Users/lo_fye/code/foundry/src/Compiler/Analysis/ImpactAnalyzer.php)
    - [AnalyzerContext.php](/Users/lo_fye/code/foundry/src/Compiler/Analysis/AnalyzerContext.php)
    - [GraphVisualizer.php](/Users/lo_fye/code/foundry/src/Compiler/Visualization/GraphVisualizer.php)

- Docs/examples:
  - [roadmap-phase1.md](/Users/lo_fye/code/foundry/docs/roadmap-phase1.md)
  - [examples/phase1/README.md](/Users/lo_fye/code/foundry/examples/phase1/README.md)
  - README CLI/docs/examples updated in [README.md](/Users/lo_fye/code/foundry/README.md)

**Tests added**
- [CLIPhase1CommandsTest.php](/Users/lo_fye/code/foundry/tests/Integration/CLIPhase1CommandsTest.php)
- [CLIPhase1CommandErrorsTest.php](/Users/lo_fye/code/foundry/tests/Integration/CLIPhase1CommandErrorsTest.php)
- [PhaseOneSpecCompilerTest.php](/Users/lo_fye/code/foundry/tests/Unit/PhaseOneSpecCompilerTest.php)
- [PhaseOneSpecPassDiagnosticsTest.php](/Users/lo_fye/code/foundry/tests/Unit/PhaseOneSpecPassDiagnosticsTest.php)
- [FormSchemaRendererTest.php](/Users/lo_fye/code/foundry/tests/Unit/FormSchemaRendererTest.php)
- [ResourceVerifierTest.php](/Users/lo_fye/code/foundry/tests/Unit/ResourceVerifierTest.php)
- [PhaseOneSpecNormalizeCodemodTest.php](/Users/lo_fye/code/foundry/tests/Unit/PhaseOneSpecNormalizeCodemodTest.php)

**Validation**
- `php vendor/bin/phpunit`: passed (`172` tests, `905` assertions, `3` skipped)
- Coverage command: `XDEBUG_MODE=coverage php vendor/bin/phpunit --coverage-text`
  - **Lines: 90.13% (9192/10199)**

So yes: coverage is currently above the 90% floor after Phase 1 changes.

22 files changed
+358
-30
Undo
README.md
src/CLI/Application.php
src/CLI/CommandContext.php
src/Compiler/Analysis/AnalyzerContext.php
src/Compiler/Analysis/ImpactAnalyzer.php
src/Compiler/CompilationState.php
src/Compiler/Extensions/ExtensionRegistry.php
src/Compiler/GraphVerifier.php
src/Compiler/IR/Nodes.php
src/Compiler/IR/StarterKitNode.php
src/Compiler/Passes/DiscoveryPass.php
src/Compiler/Passes/NormalizePass.php
src/Compiler/Passes/ValidatePass.php
src/Compiler/Projection/CoreProjectionEmitters.php
src/Compiler/Prompt/GraphPromptBuilder.php
src/Compiler/SourceScanner.php
src/Compiler/Visualization/GraphVisualizer.php
src/Generation/FeatureGenerator.php
tests/Integration/ExamplesStructureTest.php
tests/Unit/CLICommandMatchesTest.php
tests/Unit/FormSchemaRendererTest.php
tests/Unit/PhaseOneSpecNormalizeCodemodTest.php


worked for 29 minutes

=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-
=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-
=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-
=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-
=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-

## Historical Verification Evidence

Historical verification details, when present, are preserved verbatim inside the paired result blocks above.

## Historical Stabilization Notes

Historical stabilization details, when present, are preserved verbatim inside the paired result blocks above.

## Current Repository Alignment

The imported artifact is intentionally retained under `Modules/PreCanonical` as archive-lineage context. Modern module ownership remains deferred until a separate explicit alignment spec maps the pre-canonical intent into current modules.

## Uncertainty And Reconstruction Notes

No modern module inference was performed. The generated note preserves only the marked archive relationships available through `S`, `R`, and `P` blocks.
