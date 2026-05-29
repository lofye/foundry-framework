# 002-mcp-plan-generation-and-validation

## Spec Implemented

`Modules/McpServer/specs/002-mcp-plan-generation-and-validation.md`

## Implementation Summary

- Implemented deterministic MCP plan-validation support through a new `validate_plan` tool handler.
- Tightened `generate_plan` MCP payload contracts to include explicit validation metadata and persisted-plan path references.
- Registered `validate_plan` in the MCP tool manifest while preserving existing deterministic wrapper behavior.
- Added integration and unit coverage for manifest registration, validation-by-id, inline validation, stale detection, and invalid plan-id handling.

## Files Introduced

- `src/MCP/Handlers/ValidatePlanHandler.php`
- `Modules/McpServer/outcomes/002-mcp-plan-generation-and-validation.md`

## Files Modified

- `src/MCP/MCPServer.php`
- `src/MCP/Handlers/GeneratePlanHandler.php`
- `tests/Unit/MCPServerTest.php`
- `tests/Integration/CLIMcpServeCommandTest.php`
- `Modules/McpServer/mcp-server.spec.md`
- `Modules/McpServer/mcp-server.md`
- `Modules/McpServer/mcp-server.decisions.md`

## Runtime Contracts

- MCP tool manifest now includes `validate_plan`.
- `generate_plan` output now includes:
  - `plan_record_path`
  - `validation` summary object
  - normalized `entitlements`
  - normalized `pack_requirements`
- `validate_plan` supports:
  - persisted `plan_id` validation through strict replay dry-run
  - inline `plan` validation through `GenerationPlan` + `PlanValidator`
- `validate_plan` returns deterministic `status` values:
  - `valid`
  - `blocked`
  - `stale`
  - `invalid`

## Deterministic Outputs

- MCP responses preserve canonical wrapper shape:
  - `{"tool":"<name>","data":{...}}`
- `entitlements` arrays are sorted and deduplicated.
- `pack_requirements` rows are deterministically ordered by pack/code/source.
- execution-state mapping remains deterministic for entitlement and pack-availability blockers.

## Tests Added Or Updated

- Updated `tests/Unit/MCPServerTest.php`:
  - manifest includes `generate_plan` and `validate_plan` exactly once.
- Updated `tests/Integration/CLIMcpServeCommandTest.php`:
  - `generate_plan` blocked-state contract assertions.
  - missing-intent validation for `generate_plan`.
  - `validate_plan` by persisted `plan_id`.
  - `validate_plan` inline plan payload path.
  - stale detection via strict replay drift.
  - invalid status for unknown plan id.

## Verification Commands

- `php vendor/bin/phpunit --filter 'MCPServerTest|CLIMcpServeCommandTest|GenerationPlanAndValidatorTest|PlanRecordStoreTest'`
- `php bin/foundry spec:validate --json`
- `php bin/foundry verify context --feature=mcp-server --json`
- `php bin/foundry verify context --json`
- `php bin/foundry verify features --json`
- `php bin/foundry verify contracts --json`
- `php vendor/bin/phpunit`
- `XDEBUG_MODE=coverage php vendor/bin/phpunit --coverage-clover build/coverage/clover.xml`
- `php bin/foundry verify coverage --min=90 --clover=build/coverage/clover.xml --json`

## Decisions And Tradeoffs

- Reused strict replay dry-run for persisted-plan validation to avoid introducing a second validation engine.
- Kept `generate_apply` untouched in this implementation step because apply contracts belong to later MCP specs.
- Normalized execution-state mapping in handlers so MCP output remains deterministic even when lower-level errors differ by source.

## Reconstruction Notes

- Start from the active `002` spec and existing MCP runtime (`MCPServer`, `ToolRegistry`, `CliReadBridge`).
- Add `validate_plan` registration and implement handler-level mapping for:
  - replay dry-run success
  - stale drift failures
  - entitlement blockers
  - invalid plan-id and malformed-plan failures
- Tighten `generate_plan` handler payload to include validation and record-path fields while preserving existing wrapper and tool semantics.
- Update MCP unit/integration tests to lock deterministic behavior and ensure manifest/tool contract coverage.

## Follow-Up Dependencies

- Spec `003-mcp-apply-layer-and-guard-enforcement` should define canonical `apply_plan` naming and alias behavior relative to `generate_apply`.
- Spec `004-mcp-marketplace-and-entitlement-integration` and `005-mcp-plan-explainability-and-dev-ux` should further refine marketplace-specific precedence and explainability outputs.
