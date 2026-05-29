# 005-mcp-plan-explainability-and-dev-ux

## Spec Implemented

`Modules/McpServer/specs/005-mcp-plan-explainability-and-dev-ux.md`

## Implementation Summary

Implemented deterministic persisted-plan explanation for MCP and CLI developer workflows. The new shared service loads existing plan records, uses strict replay dry-run to derive current readiness without mutation, normalizes entitlement and pack blocker data, derives sorted change summaries from persisted plan actions, and emits stable next actions for apply, validation, entitlement resolution, marketplace inspection, or regeneration.

## Files Introduced

- `src/Explain/PlanExplanationService.php`
- `src/MCP/Handlers/ExplainPlanHandler.php`
- `tests/Unit/PlanExplanationServiceTest.php`
- `Modules/McpServer/outcomes/005-mcp-plan-explainability-and-dev-ux.md`

## Files Modified

- `src/CLI/Commands/ExplainCommand.php`
- `src/MCP/MCPServer.php`
- `tests/Integration/CLIMcpServeCommandTest.php`
- `tests/Unit/MCPServerTest.php`
- `tests/Unit/McpPlanHandlersTest.php`
- `Modules/McpServer/mcp-server.spec.md`
- `Modules/McpServer/mcp-server.md`
- `Modules/McpServer/mcp-server.decisions.md`
- `Modules/implementation.log`

## Runtime Contracts

- `php bin/foundry explain plan <plan_id> --json` returns the normalized explanation data object.
- `php bin/foundry mcp:serve --tool=explain_plan --input='{"plan_id":"..."}' --json` returns the same data object inside `{"tool":"explain_plan","data":...}`.
- `explain_plan` requires a non-empty string `plan_id`; invalid MCP input throws `MCP_INPUT_INVALID`.
- Unknown plan ids return structured `status=missing` explanations.
- Malformed or unreplayable plan records return structured `status=invalid` explanations.
- Plan explanations do not generate plans, apply plans, acquire entitlements, or mutate source files.

## Deterministic Outputs

- Packs are sorted by `name`.
- Readiness reasons are sorted by `code`, `pack`, and `message`.
- Next actions are sorted by `type`, `pack`, and `command`.
- Changes are sorted by `path` and `action`.
- Pack reasons use persisted reason metadata when present, otherwise deterministic explicit/inferred fallbacks.
- Validation details redact token, secret, and raw license-key fields.

## Tests Added Or Updated

- Added unit coverage for executable, missing-entitlement, expired-entitlement, stale, missing-plan, sorting, and redaction explanation cases.
- Added handler coverage for `explain_plan` CLI delegation and input validation.
- Updated MCP manifest coverage to include `explain_plan`.
- Added integration coverage proving CLI `explain plan` JSON matches MCP `explain_plan` data.

## Verification Commands

- `php vendor/bin/phpunit --filter 'CLIMcpServeCommandTest|MCPServerTest|McpPlanHandlersTest|PlanExplanationServiceTest|Explain'`
- `php -l src/Explain/PlanExplanationService.php`
- `php -l src/MCP/Handlers/ExplainPlanHandler.php`
- `php -l src/CLI/Commands/ExplainCommand.php`
- `php bin/foundry spec:validate --json`
- `php bin/foundry verify context --feature=mcp-server --json`
- `php bin/foundry verify context --json`
- `php bin/foundry verify features --json`
- `php bin/foundry verify contracts --json`
- `php vendor/bin/phpunit --filter 'CLIMcpServeCommandTest|MCPServerTest|Explain'`
- `php vendor/bin/phpunit`
- `PATH=/opt/homebrew/bin:$PATH XDEBUG_MODE=coverage php vendor/bin/phpunit --coverage-clover build/coverage/clover.xml`
- `php bin/foundry verify coverage --min=90 --clover=build/coverage/clover.xml --json`

## Decisions And Tradeoffs

- The MCP handler delegates through the CLI/read bridge to preserve byte-for-byte CLI/MCP data parity.
- Strict replay dry-run is used for current readiness because it already encodes drift, validation, entitlement, and Marketplace blocker contracts.
- The service keeps `plan:show` unchanged and treats explanation as a separate developer UX surface.
- The service avoids inventing detailed pack-selection prose; when no persisted reason exists, it uses deterministic explicit/inferred fallback text.

## Reconstruction Notes

- Rebuild by wiring `PlanExplanationService` into `ExplainCommand` before normal architecture-target explain parsing.
- Register `ExplainPlanHandler` in `MCPServer::boot()`.
- Keep output shape stable with required top-level keys: `plan_id`, `status`, `intent`, `mode`, `execution_state`, `readiness`, `packs`, `changes`, and `validation`.
- Use only persisted plan records plus strict replay dry-run output as explanation inputs.

## Follow-Up Dependencies

- None required for spec completion.
