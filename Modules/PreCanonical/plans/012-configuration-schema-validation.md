# Implementation Plan: 012-configuration-schema-validation

## Historical Provenance

- Imported spec path: `Modules/PreCanonical/specs/012-configuration-schema-validation.md`
- Source archive: `_import/pre-canonical-specs.md`
- Legacy name: `12 — Configuration Schema Validation`
- Legacy id: `12`
- Canonical pre-canonical id: `012`

## Historical Specification Summary

The original pre-canonical specification body is preserved in the imported execution spec. This reconstruction note records adjacent marked context and result evidence without inferring modern module ownership.

## Historical Preamble Context

### Preamble Block 1

- Name: `12 — Configuration Schema Validation`

Foundry’s explicit architecture makes it especially well suited to schema-driven configuration. Before 1.0, configs should be validated formally rather than loosely interpreted.

This will improve:
	•	reliability
	•	diagnostics
	•	editor tooling
	•	LLM understanding
	•	docs generation

All new code must maintain ≥ 90% automated test coverage.

## Historical Implementation Evidence

### Result Block 1

- Name: `12 — Configuration Schema Validation`

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
