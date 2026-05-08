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
