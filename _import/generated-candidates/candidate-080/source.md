Spec 8 — Extension System Stabilization

Preface

Foundry already has an extension/pack architecture, but before 1.0 it needs to become a formally stable ecosystem surface.

Extension authors need confidence that packs can be discovered, loaded, validated, and versioned predictably.

Spec 8 stabilizes the extension model.

All new code must maintain ≥ 90% automated test coverage.

Goals

Spec 8 must:
	•	formalize extension lifecycle
	•	formalize extension metadata
	•	define compatibility rules
	•	stabilize extension discovery and registration
	•	improve extension diagnostics

Requirements

1. Extension contract

Codex must define the stable extension contract, including:
	•	required metadata
	•	optional capabilities
	•	registration lifecycle
	•	graph integration hooks
	•	diagnostics hooks
	•	CLI contribution hooks

2. Pack metadata schema

Define and validate a stable metadata schema for packs/extensions, including fields such as:
	•	name
	•	version
	•	compatibility constraints
	•	dependencies
	•	provided capabilities
	•	optional CLI/docs integration metadata

3. Extension lifecycle

Define clear lifecycle stages such as:
	•	discovered
	•	loaded
	•	validated
	•	graph-integrated
	•	runtime-enabled

4. Dependency ordering and conflict handling

Support:
	•	dependency resolution between extensions
	•	deterministic load order
	•	explicit conflict reporting
	•	missing dependency diagnostics

5. Extension diagnostics

Integrate with foundry doctor and diagnostics so the framework can report:
	•	incompatible extension versions
	•	unresolved dependencies
	•	duplicate registrations
	•	invalid metadata
	•	graph integration failures

6. Docs and examples

Provide:
	•	extension author guide
	•	pack metadata reference
	•	one or more example extensions

Deliverables
	•	stable extension contract
	•	extension metadata schema
	•	dependency/conflict resolution
	•	extension diagnostics integration
	•	extension docs and examples

Testing Requirements

Tests must cover:
	•	extension registration
	•	lifecycle execution
	•	dependency ordering
	•	conflict detection
	•	diagnostics output
	•	metadata validation

Coverage must remain ≥ 90%.

==========================================================================================

RESULT

Spec 8 is implemented. The core of it is in [ExtensionRegistry.php](/Users/lo_fye/code/foundry-framework/src/Compiler/Extensions/ExtensionRegistry.php), [ExtensionMetadataValidator.php](/Users/lo_fye/code/foundry-framework/src/Compiler/Extensions/ExtensionMetadataValidator.php), [ExtensionDescriptor.php](/Users/lo_fye/code/foundry-framework/src/Compiler/Extensions/ExtensionDescriptor.php), and [PackDefinition.php](/Users/lo_fye/code/foundry-framework/src/Compiler/Extensions/PackDefinition.php). Extensions now have explicit metadata schema, lifecycle state, deterministic dependency/load ordering, duplicate/conflict handling, and surfaced diagnostics. Compiler integration was updated in [GraphCompiler.php](/Users/lo_fye/code/foundry-framework/src/Compiler/GraphCompiler.php) so extension pass failures become `FDY7020` diagnostics instead of aborting the compile. CLI/reporting now exposes the stabilized surface through [InspectGraphCommand.php](/Users/lo_fye/code/foundry-framework/src/CLI/Commands/InspectGraphCommand.php), [VerifyCompatibilityCommand.php](/Users/lo_fye/code/foundry-framework/src/CLI/Commands/VerifyCompatibilityCommand.php), [DoctorCommand.php](/Users/lo_fye/code/foundry-framework/src/CLI/Commands/DoctorCommand.php), [CompatibilityChecker.php](/Users/lo_fye/code/foundry-framework/src/Compiler/Extensions/CompatibilityChecker.php), and [CompatibilityReport.php](/Users/lo_fye/code/foundry-framework/src/Compiler/Extensions/CompatibilityReport.php).

I also made the built-in/demo extensions declare explicit dependencies and pack metadata in [FoundationCompilerExtension.php](/Users/lo_fye/code/foundry-framework/src/Compiler/Extensions/FoundationCompilerExtension.php), [IntegrationCompilerExtension.php](/Users/lo_fye/code/foundry-framework/src/Compiler/Extensions/IntegrationCompilerExtension.php), [PlatformCompilerExtension.php](/Users/lo_fye/code/foundry-framework/src/Compiler/Extensions/PlatformCompilerExtension.php), and [DemoCapabilityExtension.php](/Users/lo_fye/code/foundry-framework/src/Extensions/Demo/DemoCapabilityExtension.php). Docs/examples were updated in [docs/extension-author-guide.md](/Users/lo_fye/code/foundry-framework/docs/extension-author-guide.md), [docs/extensions-and-migrations.md](/Users/lo_fye/code/foundry-framework/docs/extensions-and-migrations.md), [docs/architecture-tools.md](/Users/lo_fye/code/foundry-framework/docs/architecture-tools.md), [examples/extensions-migrations/README.md](/Users/lo_fye/code/foundry-framework/examples/extensions-migrations/README.md), and [README.md](/Users/lo_fye/code/foundry-framework/README.md). Coverage was added in [ExtensionRegistryTest.php](/Users/lo_fye/code/foundry-framework/tests/Unit/ExtensionRegistryTest.php), [ExtensionMetadataValidatorTest.php](/Users/lo_fye/code/foundry-framework/tests/Unit/ExtensionMetadataValidatorTest.php), [CompatibilityCheckerTest.php](/Users/lo_fye/code/foundry-framework/tests/Unit/CompatibilityCheckerTest.php), [GraphCompilerExtensionFailuresTest.php](/Users/lo_fye/code/foundry-framework/tests/Unit/GraphCompilerExtensionFailuresTest.php), and the CLI integration tests.

Verification: `php vendor/bin/phpunit` passed (`229` tests, `1354` assertions, `3` skipped). `XDEBUG_MODE=coverage php vendor/bin/phpunit --coverage-text` reported `90.34%` line coverage. I also ran `php bin/foundry inspect extensions --json`, `php bin/foundry verify compatibility --json`, and `php bin/foundry doctor --json`.

27 files changed
+2249
-192
Undo
README.md
docs/architecture-tools.md
docs/extension-author-guide.md
docs/extensions-and-migrations.md
examples/extensions-migrations/README.md
src/CLI/Commands/DoctorCommand.php
src/CLI/Commands/InspectGraphCommand.php
src/CLI/Commands/VerifyCompatibilityCommand.php
src/Compiler/Extensions/AbstractCompilerExtension.php
src/Compiler/Extensions/CompatibilityChecker.php
src/Compiler/Extensions/CompatibilityReport.php
src/Compiler/Extensions/ExtensionDescriptor.php
src/Compiler/Extensions/ExtensionMetadataValidator.php
src/Compiler/Extensions/ExtensionRegistrationLoader.php
src/Compiler/Extensions/ExtensionRegistry.php
src/Compiler/Extensions/FoundationCompilerExtension.php
src/Compiler/Extensions/IntegrationCompilerExtension.php
src/Compiler/Extensions/PackDefinition.php
src/Compiler/Extensions/PlatformCompilerExtension.php
src/Compiler/GraphCompiler.php
src/Extensions/Demo/DemoCapabilityExtension.php
tests/Integration/CLIArchitectureToolsCommandsTest.php
tests/Integration/CLIGraphCommandsTest.php
tests/Unit/CompatibilityCheckerTest.php
tests/Unit/ExtensionMetadataValidatorTest.php
tests/Unit/ExtensionRegistryTest.php
tests/Unit/GraphCompilerExtensionFailuresTest.php
