# Spec 12 — Configuration Schema Validation

Preface

Foundry’s explicit architecture makes it especially well suited to schema-driven configuration. Before 1.0, configs should be validated formally rather than loosely interpreted.

This will improve:
	•	reliability
	•	diagnostics
	•	editor tooling
	•	LLM understanding
	•	docs generation

All new code must maintain ≥ 90% automated test coverage.

Goals

Spec 12 must:
	•	add explicit validation for framework and app config
	•	make config errors actionable
	•	support machine-readable schemas
	•	support future docs/editor integration

Requirements

1. Schema definitions

Define schemas for key configuration areas, such as:
	•	app config
	•	pipeline config
	•	extension config
	•	routing-related config
	•	search/cache/queue adapters where applicable

2. Validation flow

Configs should be validated during:
	•	doctor
	•	compile/build steps
	•	app bootstrap where necessary

3. Error quality

Config validation errors must explain:
	•	the field/path that failed
	•	the expected shape/type
	•	what was actually provided
	•	how to fix it where practical

4. Machine-readable schema access

Where practical, expose schemas in a machine-readable format for:
	•	docs generation
	•	tooling
	•	editor support
	•	LLM use

5. Backward compatibility support

Where old config formats still exist, provide compatibility handling or explicit upgrade guidance.

Deliverables
	•	config schemas
	•	validation integration
	•	better config diagnostics
	•	machine-readable schema exposure

Testing Requirements

Tests must cover:
	•	valid configs
	•	invalid configs
	•	diagnostics quality
	•	schema generation/exposure
	•	compatibility behavior

Coverage must remain ≥ 90%.

==========================================================================================

RESULT

Implemented Spec 12 end to end. Machine-readable config schemas now live in [ConfigSchemaCatalog.php](/Users/lo_fye/code/foundry-framework/src/Config/ConfigSchemaCatalog.php), validation and legacy normalization live in [ConfigValidator.php](/Users/lo_fye/code/foundry-framework/src/Config/ConfigValidator.php) and [ConfigCompatibilityNormalizer.php](/Users/lo_fye/code/foundry-framework/src/Config/ConfigCompatibilityNormalizer.php), compile-time enforcement is wired through [ConfigValidationPass.php](/Users/lo_fye/code/foundry-framework/src/Compiler/Passes/ConfigValidationPass.php), doctor exposes `config_validation` and `config_schemas` in [DoctorCommand.php](/Users/lo_fye/code/foundry-framework/src/CLI/Commands/DoctorCommand.php), and bootstrap now rejects invalid config in [RuntimeFactory.php](/Users/lo_fye/code/foundry-framework/src/Core/RuntimeFactory.php). Schema errors now carry field path, expected, actual, and suggested fix via [JsonSchemaValidator.php](/Users/lo_fye/code/foundry-framework/src/Schema/JsonSchemaValidator.php). Compile emits `app/.foundry/build/manifests/config_schemas.json` and `app/.foundry/build/diagnostics/config_validation.json`.

I also canonicalized the demo app config in [database.php](/Users/lo_fye/code/foundry-framework/app/platform/config/database.php), [storage.php](/Users/lo_fye/code/foundry-framework/app/platform/config/storage.php), and [ai.php](/Users/lo_fye/code/foundry-framework/app/platform/config/ai.php), while preserving upgrade guidance for legacy keys, and the required recompile refreshed [feature_index.php](/Users/lo_fye/code/foundry-framework/app/generated/feature_index.php). Test coverage for valid configs, invalid configs, diagnostics quality, schema exposure, and compatibility behavior was added in [ConfigSchemaValidationTest.php](/Users/lo_fye/code/foundry-framework/tests/Unit/ConfigSchemaValidationTest.php), [CLIConfigValidationTest.php](/Users/lo_fye/code/foundry-framework/tests/Integration/CLIConfigValidationTest.php), and [SchemaValidatorTest.php](/Users/lo_fye/code/foundry-framework/tests/Unit/SchemaValidatorTest.php).

Verification: `php vendor/bin/phpunit` passed (`243` tests, `1561` assertions, `3` skipped). `php bin/foundry compile graph --json`, `php bin/foundry verify graph --json`, and `php bin/foundry doctor --json` also passed; `doctor` still reports existing demo-app warnings unrelated to config validation. I could not confirm the exact coverage percentage locally because `phpunit --coverage-text` reported that no code coverage driver was available.

26 files changed
+2210
-62
Undo
app/platform/config/ai.php
app/platform/config/database.php
app/platform/config/storage.php
src/CLI/Commands/DoctorCommand.php
src/Compiler/BuildLayout.php
src/Compiler/CompilationState.php
src/Compiler/CompileResult.php
src/Compiler/Extensions/ExtensionDescriptor.php
src/Compiler/Extensions/PackDefinition.php
src/Compiler/GraphCompiler.php
src/Compiler/GraphVerifier.php
src/Compiler/Passes/ConfigValidationPass.php
src/Compiler/Passes/EmitPass.php
src/Compiler/SourceScanner.php
src/Config/ConfigCompatibilityNormalizer.php
src/Config/ConfigSchemaCatalog.php
src/Config/ConfigValidationIssue.php
src/Config/ConfigValidationReport.php
src/Config/ConfigValidator.php
src/Core/RuntimeFactory.php
src/Schema/JsonSchemaValidator.php
src/Schema/ValidationError.php
src/Support/ApiSurfaceRegistry.php
tests/Integration/CLIConfigValidationTest.php
tests/Unit/ConfigSchemaValidationTest.php
tests/Unit/SchemaValidatorTest.php
