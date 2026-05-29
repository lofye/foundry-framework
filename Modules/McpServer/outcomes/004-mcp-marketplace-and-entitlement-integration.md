# 004-mcp-marketplace-and-entitlement-integration

## Spec Implemented

`Modules/McpServer/specs/004-mcp-marketplace-and-entitlement-integration.md`

## Implementation Summary

- Normalized shared pack-requirement and entitlement contracts for Marketplace-aware MCP plan/validate/apply flows.
- Extended resolver output to include Marketplace metadata fields (`source`, `distribution`, `version`, `entitlement_required`, structured entitlement metadata, executable flag, message).
- Added deterministic entitlement summary `invalid` handling and canonical summary status mapping.
- Aligned MCP execution-state precedence across generate/validate/apply handlers for Marketplace blockers.
- Preserved centralized entitlement decisions through `PackEntitlementResolver` and `PackRequirementResolver`.

## Files Introduced

- `Modules/McpServer/outcomes/004-mcp-marketplace-and-entitlement-integration.md`

## Files Modified

- `src/Generate/PackRequirementResolver.php`
- `src/MCP/Handlers/GeneratePlanHandler.php`
- `src/MCP/Handlers/ValidatePlanHandler.php`
- `src/MCP/Handlers/GenerateApplyHandler.php`
- `tests/Unit/PackRequirementResolverTest.php`
- `tests/Unit/McpPlanHandlersTest.php`
- `Modules/McpServer/mcp-server.spec.md`
- `Modules/McpServer/mcp-server.md`
- `Modules/McpServer/mcp-server.decisions.md`

## Runtime Contracts

- `pack_requirements` rows now normalize to stable Marketplace-aware shape:
  - `pack`, `source`, `version`, `distribution`, `entitlement_required`, `entitlement`, `executable`, `message` (+ optional `code`)
- `entitlements` summaries now include deterministic:
  - `status`, `required`, `granted`, `missing`, `expired`, `unknown`, `invalid`
- Marketplace execution-state precedence now resolves as:
  - `invalid`
  - `blocked_pack_unavailable`
  - `blocked_expired_entitlement`
  - `blocked_missing_entitlement`
  - `blocked_unknown_entitlement`
  - existing non-Marketplace blockers

## Deterministic Outputs

- Pack rows are deduplicated by pack and sorted by `pack` ascending.
- Entitlement summary arrays are sorted ascending and de-duplicated.
- MCP handlers normalize unsupported `source`/`distribution`/entitlement status values to allowed contract values.

## Tests Added Or Updated

- Updated `tests/Unit/PackRequirementResolverTest.php`:
  - local pack rows report local source/distribution and not-required entitlement status
  - free/premium/expired rows expose expected entitlement status summaries
  - missing Marketplace packs map to `blocked_pack_unavailable`
  - malformed entitlement cache state maps to invalid entitlement summary + `ENTITLEMENT_VALIDATION_FAILED`
- Updated `tests/Unit/McpPlanHandlersTest.php`:
  - blocked generate-plan payload mapping includes normalized Marketplace row metadata.

## Verification Commands

- `php bin/foundry spec:validate --json`
- `php bin/foundry verify context --feature=mcp-server --json`
- `php bin/foundry verify context --feature=marketplace --json`
- `php bin/foundry verify features --json`
- `php bin/foundry verify contracts --json`
- `php bin/foundry inspect marketplace --json`
- `php bin/foundry verify marketplace --json`
- `php vendor/bin/phpunit --filter 'CLIMcpServeCommandTest|MCPServerTest|PackRequirementResolverTest|PackEntitlementResolverTest'`
- `php vendor/bin/phpunit`
- `php -d zend_extension=/opt/homebrew/lib/php/pecl/20240924/xdebug.so -d xdebug.mode=coverage vendor/bin/phpunit --coverage-clover build/coverage/clover.xml`
- `php bin/foundry verify coverage --min=90 --clover=build/coverage/clover.xml --json`

## Decisions And Tradeoffs

- Kept Marketplace policy decisions centralized in resolver/runtime services and used MCP-side normalization only for contract stability.
- Treated malformed entitlement/distribution state as `invalid` to fail closed.
- Preserved existing code paths while extending output shape to avoid a parallel Marketplace logic path.

## Reconstruction Notes

- Implemented contract shape changes in shared resolver first.
- Applied MCP handler normalization updates for entitlement summary and execution-state precedence.
- Adjusted unit tests to lock the updated deterministic Marketplace-aware contract behavior.
- Updated module context artifacts and implementation log for spec completion compliance.

## Follow-Up Dependencies

- Spec `005-mcp-plan-explainability-and-dev-ux` can build on these normalized Marketplace fields for richer MCP explainability surfaces.
