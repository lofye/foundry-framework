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
