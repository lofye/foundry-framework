# Implementation Plan: 002-roadmap-phase-2-basic-features

## Historical Provenance

- Imported spec path: `Modules/PreCanonical/specs/002-roadmap-phase-2-basic-features.md`
- Source archive: `_import/pre-canonical-specs.md`
- Legacy name: `2 - Roadmap Phase 2 - Basic Features`
- Legacy id: `2`
- Canonical pre-canonical id: `002`

## Historical Specification Summary

The original pre-canonical specification body is preserved in the imported execution spec. This reconstruction note records adjacent marked context and result evidence without inferring modern module ownership.

## Historical Preamble Context

### Preamble Block 1

- Name: `2 - Roadmap Phase 2 - Basic Features`

PHASE 2

Phase 0A, 0B, 0C, and 0D are now canonical.

In addition to integrating with the semantic compiler, canonical application graph, extension system, migration/versioning model, doctor/analysis tooling, and graph visualization system, all new capabilities in this phase must also integrate with the execution pipeline, feature guard model, interceptor system, and execution-plan inspection/verification tools introduced in Phase 0D.

Important rules:
- Do not introduce ad hoc middleware stacks or parallel runtime request-processing systems.
- Any auth, permission, CSRF, rate-limiting, request-validation, content-negotiation, webhook-verification, locale-resolution, streaming, or other cross-cutting behavior must use the canonical pipeline/guard/interceptor architecture where appropriate.
- New features should emit graph-visible execution plans and participate in pipeline diagnostics, inspection, and visualization.
- Where useful, new capabilities should also integrate with doctor, graph visualization, and prompt-context extraction so that LLMs and humans can inspect the resulting system structure.

In short:
All future phases must be graph-native, extension-native, migration-aware, and pipeline-native.

Before implementing this phase, adapt all new capabilities to the Foundry Phase 0 semantic compiler and canonical application graph.

Important rules for this phase:
- Notifications, API resources, OpenAPI export, docs generation, and test generation v2 must all derive from the compiled graph or graph projections.
- Do not create separate parsers or registries for notifications, APIs, docs, or tests if the same information can be represented in the graph.
- Any new source specs or config files introduced in this phase must:
  - be versioned
  - compile into explicit graph nodes and edges
  - participate in diagnostics
  - support future codemod/migration handling
- OpenAPI export must be generated from graph-linked routes, schemas, auth metadata, and response contracts.
- Docs generation must be generated from the graph, not from ad hoc filesystem scans.
- Test generation v2 must use graph knowledge such as feature dependencies, schemas, auth, events, jobs, and routes.
- Any new verify commands must operate over the graph where practical.
- Any new inspect commands must surface graph-backed reality.
- Any new notification/API/docs/test capabilities that could be extensions should be implemented in a graph-aware extension-friendly way.

In short:
Phase 2 should treat the application graph as the canonical substrate for export, documentation, notification definition, API generation, and test intelligence.

Here’s a single master prompt for Codex for Foundry Roadmap Phase 2.

This phase builds the next layer developers are highly likely to ask their LLMs for once auth, CRUD, forms, admin, uploads, and listings exist:
	•	notifications and mail
	•	API generation and OpenAPI export
	•	docs generation from source contracts
	•	deeper test generation

## Historical Implementation Evidence

### Result Block 1

- Name: `2 - Roadmap Phase 2 - Basic Features`

Coverage is now above the 90% requirement.

- Full suite + coverage command run: `/bin/zsh -lc 'XDEBUG_MODE=coverage php vendor/bin/phpunit --coverage-text'`
- Result: **Lines 90.28% (10650/11797)**
- Suite status: **204 tests**, **1046 assertions**, **3 skipped**, no failures

Targeted coverage tests added/expanded in:

- [tests/Unit/NotificationTemplateRendererTest.php](/Users/lo_fye/code/foundry/tests/Unit/NotificationTemplateRendererTest.php)
- [tests/Unit/NotificationPreviewerTest.php](/Users/lo_fye/code/foundry/tests/Unit/NotificationPreviewerTest.php)
- [tests/Unit/DeepTestGeneratorTest.php](/Users/lo_fye/code/foundry/tests/Unit/DeepTestGeneratorTest.php)
- [tests/Unit/PhaseTwoVerifiersTest.php](/Users/lo_fye/code/foundry/tests/Unit/PhaseTwoVerifiersTest.php)

This keeps overall coverage compliant for continuing into Phase 1/2 work.


worked for 38 mins

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
