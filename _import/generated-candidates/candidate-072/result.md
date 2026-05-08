{
  "missing_capabilities": [...],
  "suggested_packs": ["foundry/blog"]
}


⸻

Behavior
	•	if missing:
	•	warn user
	•	suggest install
	•	optionally auto-install

⸻

🧱 Part 6 — Explain ↔ Generate Contract (Strengthened)

Strict rules:
	•	Generate MUST use ExplainModel
	•	Generate MUST respect extension attribution
	•	Each generation step must map to:
	•	explain node
	•	extension (if applicable)

⸻

Traceability Requirement

Every action must include:

{
  "explain_node_id": "...",
  "origin": "core|extension",
  "extension": "vendor/pack|null"
}


⸻

🧱 Part 7 — Safety Model (Extended)

Add:
	•	pack installation requires explicit flag
	•	pack-generated plans must be labeled
	•	mixed-origin plans must be clearly segmented

⸻

🧱 Part 8 — Output Contract (Extended)

Human Output

Must include:
	•	summary
	•	files affected
	•	risks
	•	verification results
	•	pack involvement (NEW)

⸻

JSON Output

Must include:
	•	intent
	•	mode
	•	plan
	•	actions_taken
	•	verification_results
	•	errors
	•	metadata
	•	packs_used
	•	packs_installed

⸻

🧱 Part 9 — Determinism (Strengthened)

Determinism must include:
	•	generator selection
	•	pack resolution
	•	plan merging

⸻

🧱 Part 10 — Testing (Extended)

Add tests for:
	•	pack-aware generation
	•	missing capability detection
	•	generator selection logic
	•	mixed core/pack plans
	•	deterministic output across pack states

⸻

🧱 Part 11 — Failure Modes

Must handle:
	•	missing pack
	•	generator conflict
	•	invalid plan
	•	unsafe operation

All must return structured errors.

⸻

🧱 Part 12 — Future Extensions (Unchanged)
	•	interactive planning (Spec 36)
	•	rollback system
	•	diff previews
	•	workflows
	•	hosted AI

⸻

✅ Acceptance Criteria (Updated)
	•	foundry generate works end-to-end
	•	supports new/modify/repair
	•	uses ExplainModel exclusively
	•	supports pack-based generators
	•	detects missing capabilities
	•	produces deterministic plans
	•	validates before execution
	•	runs verification
	•	outputs structured results
	•	does not corrupt system state

⸻

🧠 Done Means (Updated)

Foundry can now:
	•	understand a system (Explain)
	•	extend a system (Packs)
	•	safely modify a system (Generate)

⸻

🔥 Final Insight (Important)

This upgrade does one critical thing:

It makes Generate the execution layer of your marketplace

Now:
	•	Packs define capabilities
	•	Explain describes them
	•	Generate executes them

⸻

🚀 What this unlocks

You can now: foundry generate "add blog"

And Foundry will:
	1.	detect missing capability
	2.	suggest/install foundry/blog
	3.	use its generator
	4.	produce a safe plan
	5.	execute it deterministically

------------------------------------------------------------------------------------------

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


------------------------------------------------------------------------------------------



------------------------------------------------------------------------------------------



------------------------------------------------------------------------------------------



------------------------------------------------------------------------------------------
