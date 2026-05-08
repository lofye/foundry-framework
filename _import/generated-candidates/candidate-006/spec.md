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
