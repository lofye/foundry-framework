# Execution Spec: 002-mcp-plan-generation-and-validation

## Purpose

Introduce deterministic MCP plan generation and plan validation tools that let an MCP client ask Foundry what would change before any source mutation is attempted.

This spec turns MCP from a read-only inspection surface into a guarded planning surface only. It must not apply generated files, install packs, modify app source, or perform replay/apply behavior.

## Feature

`mcp-server`

## Reasoning Target

This spec must be implementable by GPT-5.3-Codex at Medium reasoning.

Keep the implementation local to existing MCP, Generate, Marketplace, and persisted-plan services. Do not introduce a parallel planning engine.

## Depends On

- `Modules/McpServer/specs/001-read-layer.md`
- Existing `src/MCP/MCPServer.php`
- Existing `src/MCP/ToolRegistry.php`
- Existing `src/MCP/ToolHandler.php`
- Existing Generate planning/runtime contracts:
  - `src/Generate/GenerateEngine.php`
  - `src/Generate/GenerationPlan.php`
  - `src/Generate/PlanValidator.php`
  - `src/Generate/PlanRecordStore.php`
  - `src/Generate/PackRequirementResolver.php`
- Existing Marketplace entitlement resolver when pack hints require Marketplace state:
  - `src/Marketplace/PackEntitlementResolver.php`

## Goals

1. Register deterministic MCP planning tools.
2. Reuse Generate planning and validation behavior instead of reimplementing it.
3. Return machine-readable plan payloads with stable ordering and stable field names.
4. Surface pack requirement and entitlement readiness metadata when available.
5. Persist or reference plans only through the existing persisted-plan contract.
6. Make validation callable independently from generation.
7. Preserve explicit non-application: this spec does not mutate app source or generated output.

## Non-Goals

- Do not implement `apply_plan`; that belongs to spec `003-mcp-apply-layer-and-guard-enforcement`.
- Do not add purchase, checkout, or entitlement acquisition flows.
- Do not create a new plan storage location outside `.foundry/plans/`.
- Do not change existing `foundry generate`, `plan:list`, `plan:show`, `plan:replay`, or `plan:undo` public contracts except where a small additive field is required by this spec.
- Do not add network calls.
- Do not auto-install packs.
- Do not silently substitute premium or missing packs with free alternatives.

## Tool Surface

Register these MCP tools in deterministic manifest order with the existing registry sort behavior:

```text
generate_plan
validate_plan
```

If `generate_plan` already exists, tighten it to this contract rather than creating a duplicate handler.

## `generate_plan` Input Contract

Accepted input:

```json
{
  "intent": "add blog with authentication",
  "mode": "new",
  "target": "blog",
  "packs": ["foundry/blog", "foundry/auth"],
  "allow_pack_install": false,
  "allow_premium_packs": false
}
```

Required fields:

- `intent`: non-empty string.

Optional fields:

- `mode`: one of `new`, `modify`, `repair`; default `new`.
- `target`: non-empty string when supplied.
- `packs`: string list or comma-separated string; normalize by trimming, deduplicating, and sorting.
- `allow_pack_install`: boolean; default `false`.
- `allow_premium_packs`: boolean; default `false`.

Input validation failures must throw `FoundryError` with:

```text
MCP_INPUT_INVALID
```

The error details must include the tool name and the invalid field.

## `generate_plan` Behavior

The handler must:

1. Validate and normalize input deterministically.
2. Route planning through the existing Generate planning path.
3. Force dry-run style planning so no source files are written.
4. Include persisted plan identity when the Generate path creates one.
5. Include validation results in the response.
6. Include entitlement and pack requirement metadata returned by Generate/Marketplace services.
7. Return blocked plans as successful MCP tool responses with `status: "blocked"` rather than crashing the MCP server for known planning blockers.
8. Rethrow unexpected internal errors so existing command error handling remains visible.

Repository-local plan record persistence is allowed only if it reuses `PlanRecordStore` and the existing `.foundry/plans/` contract. This is considered plan history, not app source mutation.

## `generate_plan` Output Contract

The MCP wrapper from `MCPServer::invoke()` remains:

```json
{
  "tool": "generate_plan",
  "data": {}
}
```

The `data` object must use this shape:

```json
{
  "status": "planned",
  "plan_id": "plan_abc123",
  "plan_record_path": ".foundry/plans/20260527T120000Z_plan_abc123.json",
  "execution_state": "executable",
  "validation": {
    "status": "valid",
    "errors": [],
    "warnings": []
  },
  "entitlements": {
    "status": "complete",
    "required": [],
    "granted": [],
    "missing": [],
    "expired": [],
    "unknown": []
  },
  "pack_requirements": [],
  "plan": {},
  "error": null
}
```

Required fields:

- `status`: `planned` or `blocked`.
- `plan_id`: string or `null`.
- `plan_record_path`: repository-relative string or `null`.
- `execution_state`: one of the allowed execution states below.
- `validation`: validation summary object.
- `entitlements`: entitlement summary object.
- `pack_requirements`: ordered list.
- `plan`: deterministic plan payload or empty object.
- `error`: structured error object or `null`.

## `validate_plan` Input Contract

Accepted input by id:

```json
{
  "plan_id": "plan_abc123"
}
```

Accepted input by inline plan:

```json
{
  "plan": {}
}
```

Rules:

- Exactly one of `plan_id` or `plan` must be supplied.
- `plan_id` must load through `PlanRecordStore`.
- `plan` must be an object/associative array.
- Validation must not mutate source files.

## `validate_plan` Behavior

The handler must:

1. Resolve the plan by id or use the supplied inline plan.
2. Validate plan shape with the existing Generate validation contract.
3. Validate graph freshness when the persisted record includes graph/input fingerprints.
4. Validate pack availability through existing pack requirement resolution.
5. Validate entitlement summary consistency when entitlement data exists.
6. Return deterministic validation results without applying the plan.

If persisted plan data cannot be loaded, return a validation result with `status: "invalid"` and a deterministic error code rather than applying any fallback generation.

## `validate_plan` Output Contract

```json
{
  "status": "valid",
  "plan_id": "plan_abc123",
  "execution_state": "executable",
  "validation": {
    "status": "valid",
    "errors": [],
    "warnings": []
  },
  "entitlements": {
    "status": "complete",
    "required": [],
    "granted": [],
    "missing": [],
    "expired": [],
    "unknown": []
  },
  "pack_requirements": []
}
```

`status` values:

```text
valid
blocked
stale
invalid
```

## Execution States

Allowed `execution_state` values:

```text
executable
blocked_missing_entitlement
blocked_expired_entitlement
blocked_unknown_entitlement
blocked_pack_unavailable
blocked_conflict
stale
invalid
```

Mapping rules:

- Missing required entitlement -> `blocked_missing_entitlement`.
- Expired required entitlement -> `blocked_expired_entitlement`.
- Unknown entitlement state -> `blocked_unknown_entitlement`.
- Marketplace pack not found or unavailable -> `blocked_pack_unavailable`.
- Plan conflict from Generate validation -> `blocked_conflict`.
- Fingerprint or graph drift -> `stale`.
- Malformed input/record/plan -> `invalid`.
- No blockers -> `executable`.

## Entitlement Metadata Contract

Per-pack requirement rows must use:

```json
{
  "pack": "foundry/auth",
  "source": "marketplace",
  "version": "1.0.0",
  "distribution": "premium",
  "entitlement": {
    "required": true,
    "status": "missing",
    "tier": "premium",
    "expires_at": null
  },
  "executable": false
}
```

Plan-level summary must use:

```json
{
  "status": "incomplete",
  "required": ["foundry/auth"],
  "granted": [],
  "missing": ["foundry/auth"],
  "expired": [],
  "unknown": []
}
```

Ordering rules:

- Pack names sorted ascending.
- Summary arrays sorted ascending.
- Error arrays sorted by `code`, then `pack`, then `message`.

## Determinism Requirements

For identical:

- normalized input
- app graph state
- installed pack state
- marketplace index state
- entitlement cache state
- Generate policy state

the MCP response must have the same decoded JSON structure and ordering after re-encoding with the repository's canonical JSON encoder.

No field may depend on wall-clock time except existing persisted-plan timestamp fields created by `PlanRecordStore`. Tests that assert deterministic output must compare stable response sections when timestamps are present.

## Backward Compatibility

- Preserve the existing MCP wrapper shape `{"tool":"<name>","data":{...}}`.
- Preserve existing read-only tools from spec `001-read-layer`.
- If an earlier `generate_plan` payload already exists, add missing fields without removing existing stable fields unless tests and docs are updated together.

## Required Tests

Add or update focused tests covering:

- `MCPServer::boot()` manifest includes `generate_plan` and `validate_plan` exactly once.
- `generate_plan` rejects missing `intent`.
- `generate_plan` normalizes `packs` deterministically from array and comma-separated input.
- `generate_plan` returns `status: "planned"` and `execution_state: "executable"` for an entitlement-free plan.
- `generate_plan` returns `status: "blocked"` and deterministic entitlement metadata for a missing premium entitlement.
- `validate_plan` validates by `plan_id`.
- `validate_plan` validates inline plan input.
- `validate_plan` returns `stale` for graph/input fingerprint drift when the persisted record contains enough fingerprint data.
- `validate_plan` returns `invalid` for missing or malformed persisted plan records.
- No app source files are modified by either tool.

Test locations should follow existing patterns:

- `tests/Unit/MCPServerTest.php`
- `tests/Integration/CLIMcpServeCommandTest.php`
- focused Generate/Plan tests only when existing lower-level behavior must be tightened.

## Acceptance Criteria

- `generate_plan` and `validate_plan` are registered and callable through `php bin/foundry mcp:serve --tool=<tool> --json`.
- Both tools return deterministic JSON under the existing MCP wrapper.
- Plan generation reuses existing Generate planning behavior.
- Plan validation reuses existing Generate validation/persisted-plan behavior.
- Known planning blockers are represented as structured MCP responses.
- Entitlement and pack requirement metadata are visible when Marketplace-dependent pack hints are evaluated.
- No source mutation occurs in this spec.
- Existing read-only MCP tools keep passing.

## Required Verification

Run:

```bash
php bin/foundry spec:validate --json
php bin/foundry verify context --feature=mcp-server --json
php bin/foundry verify features --json
php bin/foundry verify contracts --json
php vendor/bin/phpunit --filter 'MCPServerTest|CLIMcpServeCommandTest|GenerationPlanAndValidatorTest|PlanRecordStoreTest'
php vendor/bin/phpunit
XDEBUG_MODE=coverage php vendor/bin/phpunit --coverage-clover build/coverage/clover.xml
php bin/foundry verify coverage --min=90 --clover=build/coverage/clover.xml --json
```

All commands must exit `0` before this spec is reported complete.
