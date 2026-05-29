# 003-mcp-apply-layer-and-guard-enforcement

## Spec Implemented

`Modules/McpServer/specs/003-mcp-apply-layer-and-guard-enforcement.md`

## Implementation Summary

- Added canonical MCP apply tool registration as `apply_plan`.
- Preserved `generate_apply` as a backward-compatible alias routed to the same handler implementation path.
- Tightened MCP apply input validation (`plan_id` required, `strict`/`dry_run` booleans only, inline `plan` payloads rejected).
- Implemented deterministic preflight-first apply flow using existing `plan:replay --dry-run` and live `plan:replay` runtime behavior.
- Added deterministic guard-failure mapping and canonical apply response contract fields:
  - `status`
  - `plan_id`
  - `dry_run`
  - `execution_state`
  - `preflight`
  - `result`
  - `error`

## Files Introduced

- `Modules/McpServer/outcomes/003-mcp-apply-layer-and-guard-enforcement.md`

## Files Modified

- `src/MCP/MCPServer.php`
- `src/MCP/Handlers/GenerateApplyHandler.php`
- `tests/Unit/MCPServerTest.php`
- `tests/Unit/McpPlanHandlersTest.php`
- `tests/Integration/CLIMcpServeCommandTest.php`
- `Modules/McpServer/mcp-server.spec.md`
- `Modules/McpServer/mcp-server.md`
- `Modules/McpServer/mcp-server.decisions.md`

## Runtime Contracts

- MCP manifest includes canonical `apply_plan` and alias `generate_apply`.
- Both tool names delegate to the same apply handler/service path.
- Apply contract now enforces:
  - explicit persisted `plan_id`
  - deterministic preflight-before-mutation flow
  - `dry_run` mode that never applies mutations
  - fail-closed blocked/invalid response mapping for guard failures
- Apply failures map deterministic MCP error codes (including canonicalized `PLAN_STALE`, `PLAN_CONFLICT`, `POLICY_VIOLATION`, `VERIFY_FAILED`) and execution-state values aligned with spec `002`.

## Deterministic Outputs

- Apply responses remain wrapped by canonical MCP envelope:
  - `{"tool":"<name>","data":{...}}`
- Guard failures return normalized response shape with deterministic `status`, `execution_state`, and structured `error`.
- Entitlement summaries and pack requirements are normalized and sorted before execution-state mapping.

## Tests Added Or Updated

- Updated `tests/Unit/MCPServerTest.php`:
  - assert manifest includes `apply_plan` and `generate_apply` exactly once.
- Updated `tests/Unit/McpPlanHandlersTest.php`:
  - apply preflight-only dry-run behavior
  - apply preflight + live apply sequencing
  - stale-preflight blocking mapping
  - entitlement state-changed blocking mapping
  - verification failure surfaced deterministically
  - input contract validation for booleans/inline plan rejection
- Updated `tests/Integration/CLIMcpServeCommandTest.php`:
  - canonical `apply_plan` dry-run no-mutation behavior
  - canonical `apply_plan` live apply behavior
  - alias `generate_apply` compatibility through shared contract path
  - missing `plan_id` input validation
  - unknown plan-id invalid mapping
  - stale plan blocked before mutation

## Verification Commands

- `php vendor/bin/phpunit --filter 'MCPServerTest|CLIMcpServeCommandTest|McpPlanHandlersTest'`
- `php vendor/bin/phpunit --filter 'MCPServerTest|CLIMcpServeCommandTest|GenerateEngineUndoTest|PlanCommandsTest'`
- `php bin/foundry spec:validate --json`
- `php bin/foundry verify context --feature=mcp-server --json`
- `php bin/foundry verify context --json`
- `php bin/foundry verify features --json`
- `php bin/foundry verify contracts --json`
- `php vendor/bin/phpunit`
- `php -d zend_extension=/opt/homebrew/lib/php/pecl/20240924/xdebug.so -d xdebug.mode=coverage vendor/bin/phpunit --coverage-clover build/coverage/clover.xml`
- `php bin/foundry verify coverage --min=90 --clover=build/coverage/clover.xml --json`

## Decisions And Tradeoffs

- Reused existing replay/apply runtime to avoid introducing an MCP-specific mutation pipeline.
- Kept alias compatibility by sharing one handler instance between `apply_plan` and `generate_apply`.
- Mapped replay error codes into canonical MCP guard code space while preserving underlying details in error payloads.

## Reconstruction Notes

- Start from active `003` constraints and existing `GenerateApplyHandler` path.
- Promote `apply_plan` as canonical MCP tool and wire alias registration through a shared handler instance.
- Refactor apply handler to:
  - validate input contract
  - enforce preflight-first sequencing
  - block/invalid-map deterministic failures
  - return stable response fields across dry-run, blocked, invalid, and applied outcomes
- Expand unit/integration coverage to lock alias behavior and preflight/non-mutation guarantees.

## Follow-Up Dependencies

- Spec `004-mcp-marketplace-and-entitlement-integration` can further refine entitlement-specific explainability and marketplace lifecycle mapping in MCP responses.
- Spec `005-mcp-plan-explainability-and-dev-ux` can layer additional explain nodes and operator-facing diagnostics on top of this guarded apply contract.
