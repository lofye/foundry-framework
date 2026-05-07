# Operator Notes (FOR CODEX EXECUTION)

## What to give Codex
- Entire file

## Verify
- Collectors are pure data (no rendering)
- Analyzers produce structured outputs only
- Plan assembly is centralized and deterministic
- No logic leaks into CLI or renderers

## Anti-patterns to avoid
- Hardcoded special cases in command
- Renderers accessing graph/projections
- Analyzers returning formatted strings
- Collectors doing interpretation instead of collection

## Green-light criteria
- Clean separation: Collect → Analyze → Assemble → Render
- Deterministic output across runs
- ≥90% test coverage maintained

Please fix any issues and ensure that all Green-light criteria are met.

---



In total, you will be receiving 6 specs in order to fully implement `foundry explain`.
They are:
Spec 19A → Architecture (what it is)
Spec 19B → Implementation (how it works end-to-end)
Spec 19C → UX contract (what it feels like)
Spec 19D → Foundation slice (safe starting point)
Spec 19E → Intelligence layer (collectors + analyzers)
Spec 19F → Final polish (rendering + contributors + docs)

You have completed Spec 19D.

Before implementing the main body of Spec 19E, first perform the cleanup identified in the Spec 19D review.

This cleanup is REQUIRED to prevent architectural drift as the explain subsystem becomes more complex.

Do NOT redesign the architecture.
Do NOT collapse layers.
Do NOT move logic into the CLI or renderers.

Perform the following refactors first:

1. Remove raw graph reads from analyzers and summary generation
   - Eliminate all direct access to ExplainContext::$graph from:
     - RouteSubjectAnalyzer
     - RuleBasedSummaryBuilder
     - any other analyzer or builder
   - Analyzers and summary builders must ONLY consume structured, collector-produced context
   - Collectors become the single source of truth for graph/projection-derived data

2. Strengthen the collector boundary
   - If analyzers need data currently pulled from the graph:
     - move that logic into the appropriate collector
     - expose it via ExplainContext in structured form
   - Do NOT allow fallback access to raw graph data

3. Introduce explicit canonical subject kind mapping
   - Replace passthrough handling in ExplainSupport (or equivalent)
   - Map raw graph node types → canonical explain kinds (feature, route, workflow, etc.)
   - Ensure:
     - no raw graph types leak into ExplainSubject
     - no raw graph types leak into JSON output
     - analyzers branch only on canonical kinds

4. Stabilize ExplainContext structure (light refactor, not full redesign)
   - Keep ExplainContext as a central object
   - Begin organizing it into named, intentional slices:
     - graphNeighborhood
     - pipeline
     - commands
     - workflows
     - events
     - schemas
     - extensions
     - diagnostics
     - docs
   - Avoid continuing as an unstructured string-keyed bag

5. Prepare ExplanationPlan for expansion
   - Do NOT rewrite it completely
   - But:
     - identify the most-used substructures (relationships, executionFlow, diagnostics)
     - ensure they are consistently shaped (not ad hoc arrays)
   - This will support stable JSON output in 19E/19F

6. Preserve all architectural constraints from 19D
   - CLI command remains thin
   - ExplainEngine orchestrates only
   - Collectors gather data only (no rendering, no interpretation)
   - Analyzers interpret only (no rendering)
   - Renderer consumes ONLY ExplanationPlan
   - ExplanationPlanAssembler owns section ordering

7. Do not introduce new shortcuts
   - No graph access in renderers
   - No rendering logic in analyzers
   - No analyzer logic in CLI
   - No bypassing collectors “just for convenience”

After completing this cleanup:

Proceed with implementing Spec 19E as written:
- collectors
- subject analyzers
- section analyzers
- expanded ExplanationPlan
- deterministic plan assembly

This cleanup is considered part of 19E, not a separate phase.

Success criteria before continuing:
- No analyzer or summary builder reads raw graph data
- Canonical subject kinds are enforced everywhere
- ExplainContext is structured enough for analyzers to rely on without graph access
- System remains fully deterministic
- Test coverage remains ≥ 90%

-----------------------------------------------------------------------------------------

Spec 19E - Intelligence layer (collectors + analyzers)

Implement this spec exactly as written.

Important constraints:
- Maintain strict separation of concerns
- Do not collapse layers together for convenience
- Do not let renderers access raw graph/projection data
- Do not put explanation logic in the CLI command
- Keep all output deterministic
- Maintain ≥ 90% automated test coverage

# Spec 19E — Foundry Explain Collectors, Analyzers, and Section Mapping

## Preface

Spec 19D established the foundational architecture for `foundry explain`.

Spec 19E adds the **real analytical intelligence** of the subsystem by introducing:

- context collectors
- subject analyzers
- section analyzers
- deterministic section mapping
- richer explanation output

This phase must remain fully deterministic.
It must derive explanations from:

- the canonical application graph
- compiled projections
- diagnostics metadata
- pipeline metadata
- command metadata
- workflow/event/schema/extension metadata

It must not depend on an LLM.

All new code must maintain **≥ 90% automated test coverage**.

---

## Goals

Spec 19E must:

1. introduce normalized context collectors
2. introduce subject-specific analyzers
3. introduce section analyzers for rich explanation output
4. map CLI output sections to exact responsibilities
5. provide deep, graph-aware explanations for representative subject kinds
6. preserve renderer independence and deterministic output

---

## Canonical Output Sections

The explain system must now support the following canonical sections.

Not every subject must show every section, but the section model itself must be stable.

1. Subject
2. Summary
3. Responsibilities
4. Execution Flow
5. Depends On
6. Used By
7. Emits
8. Triggers
9. Permissions
10. Schema Interaction
11. Graph Relationships
12. Related Commands
13. Related Docs
14. Diagnostics
15. Suggested Fixes

---

## Section-to-Responsibility Mapping

### 1. Subject
Owned by:
- `ExplainTargetResolver`
- `ExplainSubjectFactory`

This section must already exist from Spec 19D and continues unchanged.

### 2. Summary
Owned by:
- `SummarySectionBuilder`

Inputs come from:
- subject analyzers
- section analyzers
- relationships
- execution flow
- diagnostics

Summary generation must remain deterministic and centralized.

### 3. Responsibilities
Owned by:
- subject-kind analyzers

Relevant analyzers:
- `FeatureSubjectAnalyzer`
- `WorkflowSubjectAnalyzer`
- `CommandSubjectAnalyzer`
- `ExtensionSubjectAnalyzer`
- `PipelineStageSubjectAnalyzer`

### 4. Execution Flow
Owned by:
- `ExecutionFlowAnalyzer`

### 5. Depends On
Owned by:
- `DependencyAnalyzer`

### 6. Used By
Owned by:
- `DependentAnalyzer`

### 7. Emits
Owned by:
- `EventEmissionAnalyzer`

### 8. Triggers
Owned by:
- `TriggerAnalyzer`

### 9. Permissions
Owned by:
- `PermissionAnalyzer`

### 10. Schema Interaction
Owned by:
- `SchemaInteractionAnalyzer`

### 11. Graph Relationships
Owned by:
- `GraphRelationshipsAnalyzer`

### 12. Related Commands
Owned by:
- `RelatedCommandsAnalyzer`

### 13. Related Docs
Owned by:
- `RelatedDocsAnalyzer`

### 14. Diagnostics
Owned by:
- `DiagnosticsAnalyzer`

### 15. Suggested Fixes
Owned by:
- `SuggestedFixesBuilder`

---

## Required File / Class Layout

Implement the following classes or their equivalent.

```text
src/Explain/
  Collector/
    GraphNeighborhoodCollector.php
    PipelineContextCollector.php
    CommandContextCollector.php
    WorkflowContextCollector.php
    EventContextCollector.php
    SchemaContextCollector.php
    ExtensionContextCollector.php
    DiagnosticsContextCollector.php
    DocsContextCollector.php

  Analyzer/
    Contract/
      SubjectAnalyzerInterface.php

    Subject/
      FeatureSubjectAnalyzer.php
      RouteSubjectAnalyzer.php
      CommandSubjectAnalyzer.php
      PipelineStageSubjectAnalyzer.php
      WorkflowSubjectAnalyzer.php
      EventSubjectAnalyzer.php
      JobSubjectAnalyzer.php
      SchemaSubjectAnalyzer.php
      ExtensionSubjectAnalyzer.php

    Section/
      ExecutionFlowAnalyzer.php
      DependencyAnalyzer.php
      DependentAnalyzer.php
      EventEmissionAnalyzer.php
      TriggerAnalyzer.php
      PermissionAnalyzer.php
      SchemaInteractionAnalyzer.php
      GraphRelationshipsAnalyzer.php
      RelatedCommandsAnalyzer.php
      RelatedDocsAnalyzer.php
      DiagnosticsAnalyzer.php

  Builder/
    SummarySectionBuilder.php
    SuggestedFixesBuilder.php
```

Codex may adapt namespaces to existing project conventions, but the role separation must remain.

---

## Collector Responsibilities

Collectors gather normalized raw context.
They must not render output.
They must not produce final prose.
They must not decide final section ordering.

### GraphNeighborhoodCollector
Must collect:
- node data for the subject
- inbound edges
- outbound edges
- adjacent nodes
- enough graph neighborhood to support dependency/dependent analysis

### PipelineContextCollector
Must collect:
- pipeline stages
- guards
- stage ordering
- route-to-stage relationships
- action/pipeline bindings where available

### CommandContextCollector
Must collect:
- CLI metadata
- command names
- aliases
- patterns/categories where available
- subject-to-command relevance candidates

### WorkflowContextCollector
Must collect:
- workflow metadata
- workflow triggers
- workflow outputs
- conditions if available from projections

### EventContextCollector
Must collect:
- event nodes
- event emitters
- event consumers/listeners
- event-related relationships

### SchemaContextCollector
Must collect:
- schema identity
- field summaries where available
- schema usage by subject
- read/write associations where derivable

### ExtensionContextCollector
Must collect:
- extension identity
- dependencies
- provided capabilities
- subject-to-extension relationships

### DiagnosticsContextCollector
Must collect:
- diagnostics relevant to subject
- severity
- code
- message
- related fix metadata if already represented

### DocsContextCollector
Must collect:
- related docs pages/slugs
- docs summaries if available
- docs links relevant to subject kind and ID

---

## Subject Analyzer Contract

Create or use a common contract such as:

```php
interface SubjectAnalyzerInterface
{
    public function supports(ExplainSubject $subject): bool;

    public function analyze(
        ExplainSubject $subject,
        ExplainContext $context,
        ExplainOptions $options
    ): SubjectAnalysisResult;
}
```

`SubjectAnalysisResult` should contain structured outputs, not final rendered strings.

---

## Subject Analyzer Responsibilities

### FeatureSubjectAnalyzer
Responsible for:
- responsibilities
- feature-scoped interpretation of dependencies
- feature-level relationship interpretation
- feature summary inputs

### RouteSubjectAnalyzer
Responsible for:
- route subject interpretation
- route responsibilities where useful
- route-specific execution relevance
- route summary inputs

### CommandSubjectAnalyzer
Responsible for:
- command responsibilities
- command relationship interpretation
- command summary inputs

### PipelineStageSubjectAnalyzer
Responsible for:
- stage responsibilities
- stage position interpretation
- stage summary inputs

### WorkflowSubjectAnalyzer
Responsible for:
- workflow responsibilities
- workflow trigger/emission interpretation
- workflow summary inputs

### EventSubjectAnalyzer
Responsible for:
- event identity and meaning
- emitter/consumer framing
- event summary inputs

### JobSubjectAnalyzer
Responsible for:
- job responsibilities
- trigger/emission framing
- job summary inputs

### SchemaSubjectAnalyzer
Responsible for:
- schema purpose framing
- schema usage relationships
- schema summary inputs

### ExtensionSubjectAnalyzer
Responsible for:
- extension responsibilities
- extension dependency framing
- extension summary inputs

---

## Section Analyzer Responsibilities

### ExecutionFlowAnalyzer
Must produce structured execution flow data.

For supported subjects (especially routes and pipeline stages), it should output ordered flow entries such as:

- guard
- stage
- action
- event
- workflow
- job

It must not render arrow strings directly.
It should produce typed entries for renderers to display.

### DependencyAnalyzer
Must determine what the subject depends on.

This must be meaningful architecture dependency, not just adjacency.

### DependentAnalyzer
Must determine what uses the subject.

### EventEmissionAnalyzer
Must determine which events are emitted by the subject.

### TriggerAnalyzer
Must determine which workflows/jobs/notifications are triggered downstream.

### PermissionAnalyzer
Must identify:
- required permissions
- where enforced
- where defined if derivable
- missing permission mappings when detectably invalid

### SchemaInteractionAnalyzer
Must identify:
- schemas read
- schemas written
- schema validation touchpoints where derivable

### GraphRelationshipsAnalyzer
Must provide:
- inbound relationships
- outbound relationships
- lateral/adjacent relationships for architecture exploration

### RelatedCommandsAnalyzer
Must suggest commands useful for deeper inspection or debugging.

This should be pragmatic, not exhaustive.

### RelatedDocsAnalyzer
Must identify relevant docs pages.

### DiagnosticsAnalyzer
Must provide diagnostics relevant to the subject, including:
- code
- severity
- message
- structured issue data where available

---

## ExplainContext Requirements

Expand `ExplainContext` to include normalized slots for at least:

- subjectNode
- graphNeighborhood
- pipeline
- commands
- workflows
- events
- schemas
- extensions
- diagnostics
- docs

Codex may choose the exact shape, but it must be a structured object or well-defined DTO model rather than arbitrary ad hoc arrays.

---

## ExplanationPlan Requirements

Expand `ExplanationPlan` so it can now hold:

- subject
- summary
- responsibilities
- executionFlow
- dependencies
- dependents
- emits
- triggers
- permissions
- schemaInteraction
- graphRelationships
- relatedCommands
- relatedDocs
- diagnostics
- suggestedFixes
- metadata

The plan should omit truly empty sections where appropriate, but the contract itself must remain deliberate and stable.

---

## Plan Assembly Requirements

Update `ExplanationPlanAssembler` so it now:

1. invokes the relevant subject analyzer
2. invokes the applicable section analyzers
3. merges their outputs deterministically
4. builds summary through `SummarySectionBuilder`
5. builds suggested fixes through `SuggestedFixesBuilder`
6. orders sections consistently

The assembler owns section ordering.
Analyzers do not.

---

## Suggested Fixes Requirements

`SuggestedFixesBuilder` must remain deterministic.

It should use:
- diagnostics
- missing dependencies
- unresolved permissions
- missing workflows/events/extensions

It must not make speculative LLM-style suggestions.

If no trustworthy fix can be inferred, it should omit or minimize this section.

---

## Section Presence Matrix

### Feature
Should now support:
- Subject
- Summary
- Responsibilities
- Depends On
- Used By
- Emits
- Triggers
- Graph Relationships
- Related Commands
- Related Docs
- Diagnostics
- Suggested Fixes

### Route / route_action
Should now support:
- Subject
- Summary
- Responsibilities (optional/light)
- Execution Flow
- Depends On
- Emits
- Triggers
- Permissions
- Schema Interaction
- Related Commands
- Related Docs
- Diagnostics
- Suggested Fixes

### Workflow
Should support:
- Subject
- Summary
- Responsibilities
- Depends On
- Used By
- Emits
- Triggers
- Graph Relationships
- Related Commands
- Related Docs
- Diagnostics
- Suggested Fixes

### Event
Should support:
- Subject
- Summary
- Used By
- Emits (if relevant to upstream framing)
- Triggers
- Graph Relationships
- Related Docs
- Diagnostics

### Pipeline stage
Should support:
- Subject
- Summary
- Responsibilities
- Execution Flow
- Depends On
- Used By
- Related Commands
- Diagnostics

### Schema
Should support:
- Subject
- Summary
- Schema Interaction
- Used By
- Related Docs
- Diagnostics

### Extension
Should support:
- Subject
- Summary
- Responsibilities
- Depends On
- Used By
- Graph Relationships
- Related Commands
- Diagnostics

---

## CLI UX Expectations for This Phase

### Example: route action

```bash
foundry explain thresholds.create
```

Expected shape:

```text
Subject
  thresholds.create
  kind: route_action

Summary
  Creates a new threshold for the authenticated user and triggers downstream
  milestone tracking and notification workflows.

Execution Flow
  request
  -> auth guard
  -> permission guard (thresholds.create)
  -> thresholds feature action
  -> threshold.created event
  -> streak.update workflow
  -> notification.dispatch job

Depends On
  feature:account
  feature:thresholds
  schema:threshold

Emits
  event: threshold.created

Triggers
  workflow: streak.update
  job: notification.dispatch

Related Commands
  foundry inspect pipeline --json
  foundry doctor
```

### Example: broken case

```bash
foundry explain thresholds.create
```

Expected shape if issues exist:

```text
Diagnostics
  ✗ Missing permission mapping: thresholds.create
  ✗ Unresolved workflow: streak.update

Suggested Fixes
  - Add permission mapping in account manifest
  - Register workflow: streak.update
```

### Example: workflow

```bash
foundry explain workflow:streak.update
```

Expected shape:

```text
Subject
  streak.update
  kind: workflow

Summary
  Updates user streak counts based on threshold activity.

Triggered By
  event: threshold.created

Emits
  event: streak.milestone_reached

Triggers
  job: notification.dispatch
```

Codex does not need to match every word exactly, but the structure and semantics must be similar.

---

## Testing Requirements

Add tests for:

### Collectors
- graph neighborhood collection
- pipeline metadata collection
- diagnostics context collection
- docs context collection

### Subject analyzers
- feature analysis
- route analysis
- workflow analysis
- event analysis

### Section analyzers
- execution flow analysis
- dependency analysis
- dependent analysis
- permission analysis
- schema interaction analysis
- diagnostics analysis
- related commands/docs analysis

### Plan assembly
- merged section composition
- deterministic ordering
- omission of empty sections
- correct summary inputs

### CLI
- richer text output
- richer JSON output
- representative subject kinds

Coverage must remain **≥ 90%**.

---

## Deliverables

Codex must implement:

- all listed collectors
- the subject analyzer contract
- representative subject analyzers
- section analyzers
- expanded explain context
- expanded explanation plan
- richer plan assembler
- deterministic summary and suggested-fix builders
- tests

---

## Final Instruction

This phase is where `foundry explain` becomes genuinely useful.

Do not let the implementation devolve into:
- a few hardcoded special cases
- strings glued together in the command
- renderer-driven logic

Build a real analysis subsystem that the framework can reuse later for:
- docs
- AI tooling
- graph exploration
- diagnostics integration

------------------------------------------------------------------------------------------	

RESULT (Spec 19E)

**19E Complete**

The required 19D cleanup is done and 19E is implemented.

The main architectural fixes are in [ExplainContext.php](/Users/lo_fye/code/foundry-framework/src/Explain/ExplainContext.php), [ExplainSupport.php](/Users/lo_fye/code/foundry-framework/src/Explain/ExplainSupport.php), [ExplainSubjectFactory.php](/Users/lo_fye/code/foundry-framework/src/Explain/ExplainSubjectFactory.php), and [ExplanationPlan.php](/Users/lo_fye/code/foundry-framework/src/Explain/ExplanationPlan.php). `ExplainContext` is now structured into named slices, canonical kind mapping is explicit, raw graph types no longer leak into subjects or JSON, and the old raw-graph summary path was removed by replacing [RuleBasedSummaryBuilder.php](/Users/lo_fye/code/foundry-framework/src/Explain/RuleBasedSummaryBuilder.php) with [SummarySectionBuilder.php](/Users/lo_fye/code/foundry-framework/src/Explain/SummarySectionBuilder.php). `rg` now shows no remaining `context->graph`, `context->artifacts`, or bag-style `context->get(...)` access in `src/Explain`.

The intelligence layer now lives where it should: collectors in [src/Explain/Collectors](/Users/lo_fye/code/foundry-framework/src/Explain/Collectors), subject analyzers and section analyzers in [src/Explain/Analyzers](/Users/lo_fye/code/foundry-framework/src/Explain/Analyzers), deterministic fix building in [SuggestedFixesBuilder.php](/Users/lo_fye/code/foundry-framework/src/Explain/SuggestedFixesBuilder.php), and centralized assembly in [ExplanationPlanAssembler.php](/Users/lo_fye/code/foundry-framework/src/Explain/ExplanationPlanAssembler.php). The engine in [ExplainEngine.php](/Users/lo_fye/code/foundry-framework/src/Explain/ExplainEngine.php) still only orchestrates, and the renderers in [TextExplanationRenderer.php](/Users/lo_fye/code/foundry-framework/src/Explain/Renderers/TextExplanationRenderer.php) and [MarkdownExplanationRenderer.php](/Users/lo_fye/code/foundry-framework/src/Explain/Renderers/MarkdownExplanationRenderer.php) still consume only the assembled plan. I also fixed two real 19E bugs while tightening the suite: permission-definition lookup in [PipelineContextCollector.php](/Users/lo_fye/code/foundry-framework/src/Explain/Collectors/PipelineContextCollector.php) and nested extension capability handling in [ExtensionSubjectAnalyzer.php](/Users/lo_fye/code/foundry-framework/src/Explain/Analyzers/ExtensionSubjectAnalyzer.php).

**Verification**

Focused and integration coverage/tests were updated in [ExplainFoundationTest.php](/Users/lo_fye/code/foundry-framework/tests/Unit/ExplainFoundationTest.php), [ExplainEngineTest.php](/Users/lo_fye/code/foundry-framework/tests/Unit/ExplainEngineTest.php), [ExplainRenderersTest.php](/Users/lo_fye/code/foundry-framework/tests/Unit/ExplainRenderersTest.php), [ExplainArchitectureCoverageTest.php](/Users/lo_fye/code/foundry-framework/tests/Unit/ExplainArchitectureCoverageTest.php), [ProAnalysisToolsTest.php](/Users/lo_fye/code/foundry-framework/tests/Unit/ProAnalysisToolsTest.php), and [CLIProCommandsTest.php](/Users/lo_fye/code/foundry-framework/tests/Integration/CLIProCommandsTest.php).

Verified with:
- `php vendor/bin/phpunit tests/Unit/ExplainFoundationTest.php tests/Unit/ExplainRenderersTest.php tests/Unit/ExplainEngineTest.php tests/Unit/ExplainArchitectureCoverageTest.php tests/Unit/ProAnalysisToolsTest.php tests/Unit/ApiSurfaceRegistryTest.php tests/Unit/CLIApplicationEdgeCasesTest.php tests/Unit/CLICommandMatchesTest.php tests/Integration/CLIProCommandsTest.php tests/Integration/CLIApplicationTest.php`
- `php -d zend_extension=/opt/homebrew/lib/php/pecl/20240924/xdebug.so -d xdebug.mode=coverage vendor/bin/phpunit --coverage-clover /tmp/explain-19e.xml ...`
- `php bin/foundry compile graph --json`
- `php bin/foundry verify graph --json`
- `php bin/foundry verify pipeline --json`
- `php bin/foundry verify contracts --json`

Coverage is above the required threshold:
- explain slice: `92.97%` (`2274/2446`) for `src/Explain` plus [ArchitectureExplainer.php](/Users/lo_fye/code/foundry-framework/src/Pro/ArchitectureExplainer.php) and [ExplainCommand.php](/Users/lo_fye/code/foundry-framework/src/Pro/CLI/ExplainCommand.php)
- including adjacent CLI/help layers: `92.66%` (`2803/3025`) with [Application.php](/Users/lo_fye/code/foundry-framework/src/CLI/Application.php) and [ApiSurfaceRegistry.php](/Users/lo_fye/code/foundry-framework/src/Support/ApiSurfaceRegistry.php)

This is safe to build on for 19F. The cleanup blockers from 19D are resolved, and the explain subsystem is now collector-driven, analyzer-driven, plan-driven, and still deterministic.


54 files changed
+3030
-2407
Undo
src/Explain/Analyzers/CommandSubjectAnalyzer.php
src/Explain/Analyzers/DependencyAnalyzer.php
src/Explain/Analyzers/DependentAnalyzer.php
src/Explain/Analyzers/DiagnosticsAnalyzer.php
src/Explain/Analyzers/EventEmissionAnalyzer.php
src/Explain/Analyzers/EventSubjectAnalyzer.php
src/Explain/Analyzers/ExecutionFlowAnalyzer.php
src/Explain/Analyzers/ExtensionSubjectAnalyzer.php
src/Explain/Analyzers/FeatureSubjectAnalyzer.php
src/Explain/Analyzers/GenericGraphSubjectAnalyzer.php
src/Explain/Analyzers/GraphRelationshipsAnalyzer.php
src/Explain/Analyzers/JobSubjectAnalyzer.php
src/Explain/Analyzers/PermissionAnalyzer.php
src/Explain/Analyzers/PipelineStageSubjectAnalyzer.php
src/Explain/Analyzers/RelatedCommandsAnalyzer.php
src/Explain/Analyzers/RelatedDocsAnalyzer.php
src/Explain/Analyzers/RouteSubjectAnalyzer.php
src/Explain/Analyzers/SchemaInteractionAnalyzer.php
src/Explain/Analyzers/SchemaSubjectAnalyzer.php
src/Explain/Analyzers/SectionAnalyzerInterface.php
src/Explain/Analyzers/SubjectAnalysisResult.php
src/Explain/Analyzers/SubjectAnalyzerInterface.php
src/Explain/Analyzers/TriggerAnalyzer.php
src/Explain/Analyzers/WorkflowSubjectAnalyzer.php
src/Explain/Collectors/CommandContextCollector.php
src/Explain/Collectors/DiagnosticsContextCollector.php
src/Explain/Collectors/DocsContextCollector.php
src/Explain/Collectors/EventContextCollector.php
src/Explain/Collectors/ExtensionContextCollector.php
src/Explain/Collectors/GraphNeighborhoodCollector.php
src/Explain/Collectors/ImpactContextCollector.php
src/Explain/Collectors/PipelineContextCollector.php
src/Explain/Collectors/SchemaContextCollector.php
src/Explain/Collectors/WorkflowContextCollector.php
src/Explain/ExplainContext.php
src/Explain/ExplainEngine.php
src/Explain/ExplainEngineFactory.php
src/Explain/ExplainSubjectFactory.php
src/Explain/ExplainSupport.php
src/Explain/ExplainTarget.php
src/Explain/ExplainTargetResolver.php
src/Explain/ExplanationPlan.php
src/Explain/ExplanationPlanAssembler.php
src/Explain/Renderers/MarkdownExplanationRenderer.php
src/Explain/Renderers/TextExplanationRenderer.php
src/Explain/RuleBasedSummaryBuilder.php
src/Explain/SuggestedFixesBuilder.php
src/Explain/SummarySectionBuilder.php
tests/Integration/CLIProCommandsTest.php
tests/Unit/ExplainArchitectureCoverageTest.php
tests/Unit/ExplainEngineTest.php
tests/Unit/ExplainFoundationTest.php
tests/Unit/ExplainRenderersTest.php
tests/Unit/ProAnalysisToolsTest.php

------------------------------------------------------------------------------------------	

## Verify
- Collectors are pure data (no rendering)
- Analyzers produce structured outputs only
- Plan assembly is centralized and deterministic
- No logic leaks into CLI or renderers

## Anti-patterns to avoid
- Hardcoded special cases in command
- Renderers accessing graph/projections
- Analyzers returning formatted strings
- Collectors doing interpretation instead of collection

## Green-light criteria
- Clean separation: Collect → Analyze → Assemble → Render
- Deterministic output across runs
- ≥90% test coverage maintained

Please fix any issues and ensure that all Green-light criteria are met.

------------------------------------------------------------------------------------------	

No findings.

**Verified**
- Collectors remain pure data collectors under [src/Explain/Collectors](/Users/lo_fye/code/foundry-framework/src/Explain/Collectors). Raw graph and artifact access is confined there; analyzers only consume structured slices from [src/Explain/ExplainContext.php](/Users/lo_fye/code/foundry-framework/src/Explain/ExplainContext.php).
- Subject and section analyzers under [src/Explain/Analyzers](/Users/lo_fye/code/foundry-framework/src/Explain/Analyzers) return structured arrays and [SubjectAnalysisResult.php](/Users/lo_fye/code/foundry-framework/src/Explain/Analyzers/SubjectAnalysisResult.php), not rendered CLI text.
- Plan assembly is centralized in [src/Explain/ExplanationPlanAssembler.php](/Users/lo_fye/code/foundry-framework/src/Explain/ExplanationPlanAssembler.php). Summary building stays in [src/Explain/SummarySectionBuilder.php](/Users/lo_fye/code/foundry-framework/src/Explain/SummarySectionBuilder.php), suggested fixes in [src/Explain/SuggestedFixesBuilder.php](/Users/lo_fye/code/foundry-framework/src/Explain/SuggestedFixesBuilder.php), and section ordering is owned there.
- The engine in [src/Explain/ExplainEngine.php](/Users/lo_fye/code/foundry-framework/src/Explain/ExplainEngine.php) still does `resolve -> collect -> assemble`. It does not render.
- The CLI in [src/Pro/CLI/ExplainCommand.php](/Users/lo_fye/code/foundry-framework/src/Pro/CLI/ExplainCommand.php) is still thin: parse flags, load graph, delegate to [src/Pro/ArchitectureExplainer.php](/Users/lo_fye/code/foundry-framework/src/Pro/ArchitectureExplainer.php).
- Renderers in [src/Explain/Renderers/TextExplanationRenderer.php](/Users/lo_fye/code/foundry-framework/src/Explain/Renderers/TextExplanationRenderer.php), [src/Explain/Renderers/MarkdownExplanationRenderer.php](/Users/lo_fye/code/foundry-framework/src/Explain/Renderers/MarkdownExplanationRenderer.php), and [src/Explain/Renderers/JsonExplanationRenderer.php](/Users/lo_fye/code/foundry-framework/src/Explain/Renderers/JsonExplanationRenderer.php) consume only `ExplanationPlan`.

**Fixes Confirmed**
- Raw graph reads were removed from analyzers and summary generation.
- Canonical subject-kind mapping is enforced through [src/Explain/ExplainSupport.php](/Users/lo_fye/code/foundry-framework/src/Explain/ExplainSupport.php) and [src/Explain/ExplainSubjectFactory.php](/Users/lo_fye/code/foundry-framework/src/Explain/ExplainSubjectFactory.php).
- The permission-definition lookup bug was corrected in [src/Explain/Collectors/PipelineContextCollector.php](/Users/lo_fye/code/foundry-framework/src/Explain/Collectors/PipelineContextCollector.php).
- The extension capability flattening bug was corrected in [src/Explain/Analyzers/ExtensionSubjectAnalyzer.php](/Users/lo_fye/code/foundry-framework/src/Explain/Analyzers/ExtensionSubjectAnalyzer.php).
- `--no-neighbors` now cleanly suppresses graph-relationship output in [src/Explain/Analyzers/GraphRelationshipsAnalyzer.php](/Users/lo_fye/code/foundry-framework/src/Explain/Analyzers/GraphRelationshipsAnalyzer.php).

**Checks**
- `php vendor/bin/phpunit tests/Unit/ExplainFoundationTest.php tests/Unit/ExplainRenderersTest.php tests/Unit/ExplainEngineTest.php tests/Unit/ExplainArchitectureCoverageTest.php tests/Unit/ProAnalysisToolsTest.php tests/Unit/ApiSurfaceRegistryTest.php tests/Unit/CLIApplicationEdgeCasesTest.php tests/Unit/CLICommandMatchesTest.php tests/Integration/CLIProCommandsTest.php tests/Integration/CLIApplicationTest.php`
- `php bin/foundry compile graph --json`
- `php bin/foundry verify graph --json`
- `php bin/foundry verify pipeline --json`
- `php bin/foundry verify contracts --json`

Coverage remains above threshold:
- explain slice: `92.97%` (`2274/2446`)
- explain slice plus adjacent CLI/help layers: `92.66%` (`2803/3025`)

This is green-lit against the checklist: clean `Collect -> Analyze -> Assemble -> Render`, deterministic output, no CLI/renderer leakage, and coverage maintained above 90%.

------------------------------------------------------------------------------------------	

REVIEW (Spec 19D)

# Global Review Rules (apply after every phase)

Before declaring the phase complete, verify all of the following:

## Architecture integrity
- Confirm the implementation still follows the intended layered architecture.
- Confirm the CLI command is not doing graph analysis work directly.
- Confirm renderers do not access raw graph/projection data.
- Confirm collectors do not generate prose.
- Confirm analyzers do not emit final CLI-formatted strings.
- Confirm section ordering is centralized, not scattered.

## Determinism
- Confirm identical inputs produce identical outputs.
- Confirm there is no hidden randomness, timestamp leakage, or unstable ordering.
- Confirm JSON output is deterministic and structured deliberately.

## Separation of concerns
- Confirm resolution, collection, analysis, assembly, and rendering are still distinct.
- Confirm no “temporary shortcuts” were introduced that collapse layers together.
- Confirm data contracts remain explicit and testable.

## Public behavior
- Confirm error messages are clear and actionable.
- Confirm the command fails safely for invalid or ambiguous input.
- Confirm the command help/output remains coherent and public-facing.

## Tests
- Confirm automated test coverage remains ≥ 90%.
- Confirm new tests cover the intended behavior rather than implementation trivia.
- Confirm representative success and failure cases are tested.

## Documentation
- Confirm docs/help text match the actual implementation.
- Confirm any newly added flags, output sections, or subject kinds are documented.

----------------------------------------------------------------------------------------

No remaining findings.

I did find one real determinism issue during this review: explain metadata still included `graph.compiled_at`, which made otherwise-identical recompiles produce different JSON. I removed that from [ExplainEngine.php](/Users/lo_fye/code/foundry-framework/src/Explain/ExplainEngine.php) and added a stability test in [ExplainEngineTest.php](/Users/lo_fye/code/foundry-framework/tests/Unit/ExplainEngineTest.php) that compares two equivalent graphs with different compile timestamps.

**Architecture Integrity**
- The layering is still clean: CLI in [ExplainCommand.php](/Users/lo_fye/code/foundry-framework/src/Pro/CLI/ExplainCommand.php) only parses flags, loads the graph, and delegates.
- Resolution stays in [ExplainTargetResolver.php](/Users/lo_fye/code/foundry-framework/src/Explain/ExplainTargetResolver.php), collection in [src/Explain/Collectors](/Users/lo_fye/code/foundry-framework/src/Explain/Collectors), analysis in [src/Explain/Analyzers](/Users/lo_fye/code/foundry-framework/src/Explain/Analyzers), assembly in [ExplanationPlanAssembler.php](/Users/lo_fye/code/foundry-framework/src/Explain/ExplanationPlanAssembler.php), and rendering in [src/Explain/Renderers](/Users/lo_fye/code/foundry-framework/src/Explain/Renderers).
- Renderers do not access graph/projection data; they only consume [ExplanationPlan.php](/Users/lo_fye/code/foundry-framework/src/Explain/ExplanationPlan.php).
- Collectors do not generate prose.
- Analyzers return structured data only through [SubjectAnalysisResult.php](/Users/lo_fye/code/foundry-framework/src/Explain/Analyzers/SubjectAnalysisResult.php) and section payloads.
- Section ordering is centralized in [ExplanationPlanAssembler.php](/Users/lo_fye/code/foundry-framework/src/Explain/ExplanationPlanAssembler.php).

**Determinism And Public Behavior**
- Raw graph reads are no longer present in analyzers or summary builders.
- JSON output is deliberate and stable through [JsonExplanationRenderer.php](/Users/lo_fye/code/foundry-framework/src/Explain/Renderers/JsonExplanationRenderer.php).
- Error handling for invalid, unsupported, and ambiguous targets remains explicit and test-covered through the resolver and CLI integration tests.
- Docs/help still match the implementation in [README.md](/Users/lo_fye/code/foundry-framework/README.md), [docs/architecture-tools.md](/Users/lo_fye/code/foundry-framework/docs/architecture-tools.md), [docs/extension-author-guide.md](/Users/lo_fye/code/foundry-framework/docs/extension-author-guide.md), and [ApiSurfaceRegistry.php](/Users/lo_fye/code/foundry-framework/src/Support/ApiSurfaceRegistry.php).

**Verification**
- `php vendor/bin/phpunit tests/Unit/ExplainFoundationTest.php tests/Unit/ExplainRenderersTest.php tests/Unit/ExplainEngineTest.php tests/Unit/ExplainArchitectureCoverageTest.php tests/Unit/ProAnalysisToolsTest.php tests/Unit/ApiSurfaceRegistryTest.php tests/Unit/CLIApplicationEdgeCasesTest.php tests/Unit/CLICommandMatchesTest.php tests/Integration/CLIProCommandsTest.php tests/Integration/CLIApplicationTest.php`
- `php bin/foundry compile graph --json`
- `php bin/foundry verify graph --json`
- `php bin/foundry verify pipeline --json`
- `php bin/foundry verify contracts --json`

Coverage remains above threshold:
- explain slice: `92.97%` (`2273/2445`)
- explain slice plus adjacent CLI/help layers: `92.66%` (`2802/3024`)

This phase is green under the review rules.


2 files changed
+25
-3
Undo
src/Explain/ExplainEngine.php
tests/Unit/ExplainEngineTest.php


----------------------------------------------------------------------------------------

# Checkpoint Review After Spec 19E

Review the implementation of collectors, analyzers, richer sections, and plan assembly.

## Collectors
- Are the collectors implemented as separate classes with normalized outputs?
- Do collectors gather context without rendering or summarizing?
- Are graph, pipeline, diagnostics, command, workflow, event, schema, extension, and docs context separated cleanly?

## Subject analyzers
- Are subject analyzers implemented by kind?
- Do they produce structured section data instead of preformatted text?
- Are responsibilities and kind-specific interpretation handled there rather than in renderers?

## Section analyzers
- Are section analyzers implemented separately from subject analyzers?
- Are the following clearly separated:
  - execution flow
  - dependencies
  - dependents
  - event emission
  - triggers
  - permissions
  - schema interaction
  - graph relationships
  - related commands
  - related docs
  - diagnostics

## ExplainContext
- Did `ExplainContext` expand into a coherent normalized object/structure?
- Is it usable by analyzers without them reaching back into raw graph APIs?

## ExplanationPlan
- Does the plan now contain richer structured sections?
- Is it still deliberate and stable rather than becoming a dump of random analyzer output?

## Plan assembly
- Is section ordering still centralized in the assembler?
- Does the assembler merge analyzer results deterministically?
- Does it omit truly empty sections cleanly?

## Suggested fixes
- Is `SuggestedFixesBuilder` deterministic?
- Does it only emit fixes when the architecture clearly implies them?

## CLI UX
- Does human-readable output now resemble the intended UX shape?
- Are richer sections appearing for representative subject kinds such as:
  - feature
  - route
  - workflow
  - event
  - pipeline stage

## Anti-pattern check
- Did Codex sneak formatting logic into analyzers?
- Did Codex sneak graph queries into renderers?
- Did Codex hardcode special cases directly in the command?

## 19E exit criteria
Only move on to 19F if:
- collectors are clean
- analyzers are separated properly
- richer sections are working
- plan assembly remains centralized and deterministic
- the implementation still feels extensible rather than tangled

If not, stop and refactor before 19F.

----------------------------------------------------------------------------------------

**Findings**
- [P3] [ExplainContext.php](/Users/lo_fye/code/foundry-framework/src/Explain/ExplainContext.php) is now coherent and named, but it is still array-backed rather than using typed slice DTOs. This is not a blocker for 19F, but it is the main place shape drift could reappear if the contracts expand a lot.
- [P3] [SuggestedFixesBuilder.php](/Users/lo_fye/code/foundry-framework/src/Explain/SuggestedFixesBuilder.php) is deterministic and safe, but still conservative. It covers diagnostics-supplied fixes, missing permission mappings, and the “no subscribers” event case, not the full universe of possible missing-relationship fixes.

**Checkpoint**
- Collectors are cleanly separated under [src/Explain/Collectors](/Users/lo_fye/code/foundry-framework/src/Explain/Collectors). Graph, pipeline, command, workflow, event, schema, extension, diagnostics, docs, and impact context are gathered independently and normalized into [ExplainContext.php](/Users/lo_fye/code/foundry-framework/src/Explain/ExplainContext.php). They do not render and they do not generate prose.
- Subject analyzers are separated by kind under [src/Explain/Analyzers](/Users/lo_fye/code/foundry-framework/src/Explain/Analyzers) and return [SubjectAnalysisResult.php](/Users/lo_fye/code/foundry-framework/src/Explain/Analyzers/SubjectAnalysisResult.php). Responsibilities and kind-specific interpretation live there, not in the renderers.
- Section analyzers are separate from subject analyzers and the responsibilities are split correctly: execution flow, dependencies, dependents, emits, triggers, permissions, schema interaction, graph relationships, related commands, related docs, and diagnostics each have their own analyzer classes.
- `ExplainContext` is now usable without raw graph reach-through. The earlier cleanup goal is satisfied: analyzers and summary generation no longer read raw graph state directly.
- [ExplanationPlan.php](/Users/lo_fye/code/foundry-framework/src/Explain/ExplanationPlan.php) is richer and still deliberate. Canonical sections are first-class top-level fields, and `sections` remains reserved for extra/custom contributor content rather than becoming a random dump.
- [ExplanationPlanAssembler.php](/Users/lo_fye/code/foundry-framework/src/Explain/ExplanationPlanAssembler.php) still owns section ordering, result merging, deduplication, empty-section omission, summary building, and suggested-fix building. That centralization is intact.
- [SummarySectionBuilder.php](/Users/lo_fye/code/foundry-framework/src/Explain/SummarySectionBuilder.php) and [SuggestedFixesBuilder.php](/Users/lo_fye/code/foundry-framework/src/Explain/SuggestedFixesBuilder.php) are deterministic. The summary path no longer depends on raw graph reads.
- Human-readable output is now structurally aligned with the intended UX for representative subject kinds. [TextExplanationRenderer.php](/Users/lo_fye/code/foundry-framework/src/Explain/Renderers/TextExplanationRenderer.php) and [MarkdownExplanationRenderer.php](/Users/lo_fye/code/foundry-framework/src/Explain/Renderers/MarkdownExplanationRenderer.php) render route, feature, workflow, event, and pipeline-stage plans from the assembled plan only.
- Anti-pattern check is clean: no graph/projection access in renderers, no analysis in the CLI command at [ExplainCommand.php](/Users/lo_fye/code/foundry-framework/src/Pro/CLI/ExplainCommand.php), and no analyzer-owned CLI formatting.
- Docs/help are aligned with the implementation in [README.md](/Users/lo_fye/code/foundry-framework/README.md), [docs/architecture-tools.md](/Users/lo_fye/code/foundry-framework/docs/architecture-tools.md), [docs/extension-author-guide.md](/Users/lo_fye/code/foundry-framework/docs/extension-author-guide.md), and [ApiSurfaceRegistry.php](/Users/lo_fye/code/foundry-framework/src/Support/ApiSurfaceRegistry.php).

**Determinism**
- I removed `compiled_at` from explain metadata in [ExplainEngine.php](/Users/lo_fye/code/foundry-framework/src/Explain/ExplainEngine.php), so explain output is no longer timestamp-sensitive across equivalent recompiles.
- Determinism is explicitly covered in [ExplainEngineTest.php](/Users/lo_fye/code/foundry-framework/tests/Unit/ExplainEngineTest.php) and [ExplainArchitectureCoverageTest.php](/Users/lo_fye/code/foundry-framework/tests/Unit/ExplainArchitectureCoverageTest.php).

**Verification**
- `php vendor/bin/phpunit tests/Unit/ExplainFoundationTest.php tests/Unit/ExplainRenderersTest.php tests/Unit/ExplainEngineTest.php tests/Unit/ExplainArchitectureCoverageTest.php tests/Unit/ProAnalysisToolsTest.php tests/Unit/ApiSurfaceRegistryTest.php tests/Unit/CLIApplicationEdgeCasesTest.php tests/Unit/CLICommandMatchesTest.php tests/Integration/CLIProCommandsTest.php tests/Integration/CLIApplicationTest.php`
- `php bin/foundry verify graph --json`
- `php bin/foundry verify pipeline --json`
- `php bin/foundry verify contracts --json`

Coverage remains above threshold:
- explain slice: `92.97%` (`2273/2445`)
- explain slice plus adjacent CLI/help layers: `92.66%` (`2802/3024`)

**19E Exit**
Safe to move to 19F.

The implementation meets the 19E exit criteria: collectors are clean, analyzers are properly separated, richer sections work, plan assembly remains centralized and deterministic, and the subsystem still feels extensible rather than tangled. The only non-blocking follow-ups I would carry into 19F are tighter typing around `ExplainContext` slices and broader but still deterministic suggested-fix inference.

----------------------------------------------------------------------------------------

Review the implementation against the checklist above and explicitly report:

1. what is complete
2. what is partially complete
3. what architectural shortcuts were taken, if any
4. what should be refactored before the next phase
5. whether the implementation is safe to build on

Do not simply say “done.”
Perform the review honestly and concretely.

----------------------------------------------------------------------------------------

1. **What is complete**

The core 19E architecture is in place and cleanly separated.

- Collection is separate under [src/Explain/Collectors](/Users/lo_fye/code/foundry-framework/src/Explain/Collectors). Graph, pipeline, commands, workflows, events, schemas, extensions, diagnostics, docs, and impact are gathered into [ExplainContext.php](/Users/lo_fye/code/foundry-framework/src/Explain/ExplainContext.php) as normalized slices.
- Subject analysis is separate under [src/Explain/Analyzers](/Users/lo_fye/code/foundry-framework/src/Explain/Analyzers). Subject analyzers return [SubjectAnalysisResult.php](/Users/lo_fye/code/foundry-framework/src/Explain/Analyzers/SubjectAnalysisResult.php), not rendered output.
- Section analysis is separate and explicit:
  - execution flow
  - dependencies
  - dependents
  - emits
  - triggers
  - permissions
  - schema interaction
  - graph relationships
  - related commands
  - related docs
  - diagnostics
- Canonical subject-kind mapping is enforced in [ExplainSupport.php](/Users/lo_fye/code/foundry-framework/src/Explain/ExplainSupport.php) and [ExplainSubjectFactory.php](/Users/lo_fye/code/foundry-framework/src/Explain/ExplainSubjectFactory.php). Raw graph types no longer leak into explain subjects or JSON.
- Plan assembly is centralized in [ExplanationPlanAssembler.php](/Users/lo_fye/code/foundry-framework/src/Explain/ExplanationPlanAssembler.php). It merges analyzer output, builds summary/fixes, deduplicates, omits empty sections, and owns ordering.
- The plan contract is deliberate in [ExplanationPlan.php](/Users/lo_fye/code/foundry-framework/src/Explain/ExplanationPlan.php). Canonical sections are first-class fields, not just arbitrary `sections`.
- The engine in [ExplainEngine.php](/Users/lo_fye/code/foundry-framework/src/Explain/ExplainEngine.php) still only orchestrates. The CLI in [ExplainCommand.php](/Users/lo_fye/code/foundry-framework/src/Pro/CLI/ExplainCommand.php) is still thin. Renderers in [src/Explain/Renderers](/Users/lo_fye/code/foundry-framework/src/Explain/Renderers) still consume only the plan.
- Determinism is strong. I removed timestamp leakage from explain metadata in [ExplainEngine.php](/Users/lo_fye/code/foundry-framework/src/Explain/ExplainEngine.php), and the tests now cover repeated-run stability.
- Coverage is above threshold: `92.97%` for the explain slice and `92.66%` including adjacent CLI/help layers.

2. **What is partially complete**

A few areas are correct but still somewhat lightweight.

- [ExplainContext.php](/Users/lo_fye/code/foundry-framework/src/Explain/ExplainContext.php) is coherent and named, but it is still array-backed rather than using typed slice DTOs.
- [ExplanationPlan.php](/Users/lo_fye/code/foundry-framework/src/Explain/ExplanationPlan.php) is explicit, but many substructures are still arrays rather than typed value objects.
- [SuggestedFixesBuilder.php](/Users/lo_fye/code/foundry-framework/src/Explain/SuggestedFixesBuilder.php) is deterministic and safe, but conservative. It handles strong cases only, not a broad set of inferred architecture repairs.
- Subject richness is uneven by kind. Feature/route/workflow/event/pipeline-stage coverage is solid; command/schema/extension/job are structurally correct but less rich.

3. **What architectural shortcuts were taken, if any**

Nothing I found is a blocking shortcut, but there are two real ones.

- The biggest shortcut is typedness, not layering: [ExplainContext.php](/Users/lo_fye/code/foundry-framework/src/Explain/ExplainContext.php) and several `ExplanationPlan` substructures are still normalized arrays instead of dedicated DTOs.
- Wiring is concrete in [ExplainEngineFactory.php](/Users/lo_fye/code/foundry-framework/src/Explain/ExplainEngineFactory.php). Collectors and analyzers are registered manually there rather than through a registry/container abstraction. That is acceptable at this size, but it is still a hardwired composition point.
- Extra contributor/custom sections remain generic payloads in `sections`, and renderers fall back to generic key/value rendering for those. That is acceptable now, but it is a weaker contract than the canonical top-level sections.

I did **not** find the problematic shortcuts you were trying to avoid:
- no graph access in renderers
- no graph access in analyzers/summary builders
- no analysis logic in CLI
- no scattered section ordering

4. **What should be refactored before the next phase**

Nothing here blocks 19F, but these are the highest-value refactors to keep the system from drifting.

- Promote the hottest `ExplainContext` slices into typed objects first:
  - `graphNeighborhood`
  - `pipeline`
  - `diagnostics`
  - `docs`
- Tighten the `ExplanationPlan` subcontracts next:
  - `dependencies`
  - `dependents`
  - `executionFlow`
  - `diagnostics`
  - `graphRelationships`
- If 19F expands contributor support, define a stricter contract for contributor-added sections so renderers do not rely too much on generic key/value fallback.
- Expand [SuggestedFixesBuilder.php](/Users/lo_fye/code/foundry-framework/src/Explain/SuggestedFixesBuilder.php) only with strongly derivable cases. Do not let it turn speculative.
- Keep [ExplainEngineFactory.php](/Users/lo_fye/code/foundry-framework/src/Explain/ExplainEngineFactory.php) under watch. If 19F adds many more analyzers/builders/contributors, a registry abstraction may become worth it.

5. **Whether the implementation is safe to build on**

Yes.

It is safe to build on because the important boundaries are already correct:
- collect
- analyze
- assemble
- render

Those phases are distinct in code, tested, and not quietly collapsed. The remaining weaknesses are mostly about stronger typing and richer contracts, not about architectural leaks. That means 19F can extend rendering, contributors, and docs without first having to undo bad structure.
















