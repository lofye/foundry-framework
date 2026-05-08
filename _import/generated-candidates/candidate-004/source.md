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
