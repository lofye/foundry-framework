### Decision: separate intent, state, and reasoning into three files
Timestamp: 2026-04-07T12:00:00-04:00

**Context**
- Chat history is ephemeral and does not reliably preserve feature intent across sessions.

**Decision**
- Use three canonical feature files: spec, state, and decision ledger.

**Reasoning**
- This keeps intent, current reality, and historical reasoning distinct and easier to validate.

**Alternatives Considered**
- Keep everything in one file.
- Use only execution specs.
- Rely on chat history and code only.

**Impact**
- The system is more structured and easier to resume, but requires disciplined updates.

**Spec Reference**
- Purpose
- Goals
- Constraints

### Decision: introduce CLI surface for context initialization and validation
Timestamp: 2026-04-07T12:30:00-04:00

**Context**
- Context artifacts exist but must currently be created and validated manually.
- This limits usability and prevents consistent enforcement.

**Decision**
- Introduce CLI commands to initialize and validate feature context:
    - context init
    - context doctor

**Reasoning**
- A CLI surface makes the system usable for both humans and LLMs.
- Deterministic outputs allow future automation and enforcement layers.

**Alternatives Considered**
- Keep context creation manual.
- Delay CLI until later phases.
- Use non-deterministic or conversational tooling.

**Impact**
- Enables consistent creation and validation of feature context.
- Forms the foundation for later enforcement and execution phases.

**Spec Reference**
- Goals
- Expected Behavior
- Acceptance Criteria