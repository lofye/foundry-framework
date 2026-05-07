# Foundry Explain — Specs 19A–19F (Corrected Master File)

This file consolidates Specs 19A–19F into a **clean, correctly ordered, non-overlapping, implementation-ready sequence**.

Key fixes applied:
- Removed overlap between phases
- Enforced strict architectural layering
- Normalized naming (ExplainEngine, ExplanationPlan, etc.)
- Ensured each phase builds cleanly on the previous
- Prevented Codex ambiguity between responsibilities
- Aligned CLI, analyzers, and renderers with a single data contract

---

# Spec 19A — CLI Entry + Target Resolution

## Purpose
Introduce `foundry explain` as a CLI entry point with deterministic target resolution.

## Responsibilities
- Parse CLI args
- Build ExplainOptions
- Resolve target → ExplainSubject (typed)
- Handle ambiguity + suggestions
- Call ExplainEngine
- Delegate rendering (no logic here)

## MUST NOT
- Perform graph analysis
- Generate output text directly

---

# Spec 19B — Core Models + Engine Skeleton

## Introduce
- ExplainTarget
- ExplainSubject
- ExplainOptions
- ExplainContext (empty shell)
- ExplanationPlan (core contract)

## ExplainEngine
Pipeline:
1. Resolve subject (already done)
2. Create context (empty for now)
3. Create minimal plan (summary only)

## Output
- Deterministic summary
- Basic JSON support

---

# Spec 19C — Plan Assembly + Renderers

## Add
- ExplanationPlanAssembler
- TextRenderer
- JsonRenderer

## Rules
- Renderers ONLY render
- Assembler defines section ordering

## Output sections (initial)
- summary
- subject metadata

---

# Spec 19D — Context Collection Layer

## Introduce collectors
Each collector gathers raw data ONLY.

Collectors:
- GraphCollector
- PipelineCollector
- EventCollector
- WorkflowCollector
- CommandCollector
- SchemaCollector
- ExtensionCollector
- DiagnosticsCollector

## Output
All data merged into ExplainContext

## Rules
- No formatting
- No summarization
- No CLI concerns

---

# Spec 19E — Analyzers + Section Builders

## Introduce analyzers

Two layers:

### 1. SubjectAnalyzers
Per type:
- FeatureAnalyzer
- RouteAnalyzer
- WorkflowAnalyzer
- EventAnalyzer
- CommandAnalyzer

### 2. SectionAnalyzers
Cross-cutting:
- ExecutionFlowAnalyzer
- DependenciesAnalyzer
- DependentsAnalyzer
- EventsAnalyzer
- TriggersAnalyzer
- PermissionsAnalyzer
- SchemaUsageAnalyzer
- GraphAnalyzer
- RelatedItemsAnalyzer
- DiagnosticsAnalyzer

## Output
Structured sections → fed into ExplanationPlanAssembler

## Rules
- No CLI formatting
- No direct graph access (use context)

---

# Spec 19F — Final UX, Contributors, and Deep Mode

## Add

### Deep Mode
- `--deep` enriches sections (never replaces them)

### Markdown Renderer
- Docs-friendly output

### Contributors
- ExplainContributorInterface
- Registry-based
- Deterministic ordering

### SuggestedFixesBuilder
- Deterministic suggestions only

## CLI Final Form

```
foundry explain <target>
foundry explain <target> --json
foundry explain <target> --markdown
foundry explain <target> --deep
```

---

# Final Architecture (ENFORCED)

CLI
 → Engine
   → Collectors
   → Analyzers
   → PlanAssembler
 → Renderer

STRICT RULES:
- CLI = orchestration only
- Collectors = data only
- Analyzers = interpretation only
- Assembler = structure only
- Renderers = presentation only

---

# Critical Fixes From Original File

The original combined file had these issues:

1. Phase leakage (rendering logic inside analyzers)
2. Missing hard boundary between collectors and analyzers
3. Inconsistent naming (plan vs output vs result)
4. CLI doing too much work
5. No enforced data contract (ExplanationPlan)
6. Contributors not constrained by architecture
7. Deep mode not clearly defined (risk of duplication)

All corrected above.

---

# Implementation Order (MANDATORY)

1. 19A
2. 19B
3. 19C
4. 19D
5. 19E
6. 19F

Do NOT skip or merge phases.

---

# End of Spec
