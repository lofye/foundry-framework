# Implementation Plan: 010-project-scaffolding-generator

## Historical Provenance

- Imported spec path: `Modules/PreCanonical/specs/010-project-scaffolding-generator.md`
- Source archive: `_import/pre-canonical-specs.md`
- Legacy name: `10 — Project Scaffolding Generator`
- Legacy id: `10`
- Canonical pre-canonical id: `010`

## Historical Specification Summary

The original pre-canonical specification body is preserved in the imported execution spec. This reconstruction note records adjacent marked context and result evidence without inferring modern module ownership.

## Historical Preamble Context

### Preamble Block 1

- Name: `10 — Project Scaffolding Generator`

Before 1.0, Foundry needs an excellent first-run experience. A developer should be able to create a working project quickly and immediately see the framework’s architecture in action.

Spec 10 introduces stable project scaffolding commands and starter templates.

All new code must maintain ≥ 90% automated test coverage.

## Historical Implementation Evidence

### Result Block 1

- Name: `10 — Project Scaffolding Generator`

RESULT

Implemented Spec 10 in [src/CLI/Commands/InitAppCommand.php](/Users/lo_fye/code/foundry-framework/src/CLI/Commands/InitAppCommand.php): `foundry new` is now the stable project scaffold alias, `init app` remains supported, and scaffolds now support `minimal`, `standard`, and `api-first` modes. The generated app now includes canonical platform/config/bootstrap files, feature-local starter examples, `phpunit.xml.dist`, a boot smoke test, an inspect-ui definition, pre-generated graph docs under `docs/generated`, pre-generated inspect pages under `docs/inspect-ui`, and clearer first-run guidance. I also updated CLI/help metadata and docs in [src/Support/ApiSurfaceRegistry.php](/Users/lo_fye/code/foundry-framework/src/Support/ApiSurfaceRegistry.php), [README.md](/Users/lo_fye/code/foundry-framework/README.md), and [docs/app-scaffolding.md](/Users/lo_fye/code/foundry-framework/docs/app-scaffolding.md).

While validating the generated app, I found a runtime issue: empty request input was failing object-schema validation, which broke zero-input starter routes. That’s fixed in [src/Schema/JsonSchemaValidator.php](/Users/lo_fye/code/foundry-framework/src/Schema/JsonSchemaValidator.php), with coverage added alongside the new scaffold coverage in [tests/Integration/CLIInitAppCommandTest.php](/Users/lo_fye/code/foundry-framework/tests/Integration/CLIInitAppCommandTest.php), [tests/Integration/CLIScaffoldingCommandErrorsTest.php](/Users/lo_fye/code/foundry-framework/tests/Integration/CLIScaffoldingCommandErrorsTest.php), [tests/Integration/CLIApplicationTest.php](/Users/lo_fye/code/foundry-framework/tests/Integration/CLIApplicationTest.php), [tests/Unit/CLICommandMatchesTest.php](/Users/lo_fye/code/foundry-framework/tests/Unit/CLICommandMatchesTest.php), [tests/Unit/ApiSurfaceRegistryTest.php](/Users/lo_fye/code/foundry-framework/tests/Unit/ApiSurfaceRegistryTest.php), and [tests/Unit/SchemaValidatorTest.php](/Users/lo_fye/code/foundry-framework/tests/Unit/SchemaValidatorTest.php).

Verification: `php vendor/bin/phpunit` passed: 236 tests, 3 skipped.

11 files changed
+1130
-193
Undo
README.md
docs/app-scaffolding.md
src/CLI/Commands/InitAppCommand.php
src/Schema/JsonSchemaValidator.php
src/Support/ApiSurfaceRegistry.php
tests/Integration/CLIApplicationTest.php
tests/Integration/CLIInitAppCommandTest.php
tests/Integration/CLIScaffoldingCommandErrorsTest.php
tests/Unit/ApiSurfaceRegistryTest.php
tests/Unit/CLICommandMatchesTest.php
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
