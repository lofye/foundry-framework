Spec 25A — Framework Examples Alignment

Purpose

Update the examples in the Foundry framework repo so they reflect the current best way to build with Foundry after Specs 20–24.

Examples must become:
- current
- teachable
- minimal
- canonical

They must not preserve outdated patterns just because they still work.

---

Goals

1. Align all framework examples with the current Foundry architecture
2. Remove legacy patterns and deprecated structures
3. Make examples easier for humans and LLMs to learn from
4. Ensure examples demonstrate the intended modern Foundry workflow
5. Keep examples smaller and clearer than real apps

---

Non-Goals

- Do not turn examples into production apps
- Do not add unnecessary complexity
- Do not preserve outdated patterns for backward-compatibility demonstration
- Do not make examples solve every edge case

---

Core Principle

Examples are not historical artifacts.

Examples are:
> the cleanest, clearest expression of how Foundry should be used today

---

Required Work

1. Audit all example apps and example docs in the framework repo

For each example:
- identify legacy patterns
- identify deprecated concepts
- identify places where newer Foundry capabilities should now be used
- identify places where the example is more complex than necessary

---

2. Update examples to current architecture

Examples must align with current:
- graph semantics
- execution model
- pipeline model
- diagnostics model
- CLI usage
- spec/source-of-truth conventions
- generated/runtime boundaries

If Specs 20–24 introduced better primitives or better conventions, the examples must use them.

---

3. Keep examples intentionally minimal

Each example must:
- teach one or a small number of key ideas
- avoid production-only complexity unless that complexity is the lesson
- prefer clarity over breadth
- be easy for an LLM to inspect and extend correctly

If complexity is not serving the teaching goal, remove it.

---

4. Make examples strongly inspectable

Each example should work cleanly with the framework’s current inspection and verification flow.

Where relevant, examples should support and demonstrate:
- compile graph
- inspect graph
- inspect feature
- doctor
- verify graph
- verify pipeline
- verify contracts

Do not fake this in docs. Ensure it actually works.

---

5. Align example documentation

Any README, example docs, inline instructions, or walkthrough text associated with examples must be updated to match actual implementation.

Do not leave:
- stale commands
- old terminology
- old file paths
- outdated explanations

---

6. Prefer current canonical terminology

Update examples to match the current terminology established by:
- philosophy docs
- execution model docs
- graph docs
- current CLI/docs contracts

Avoid mixed terminology from earlier phases.

---

7. Make examples good LLM material

Optimize examples so that an LLM reading them can correctly infer:
- source of truth
- what is generated
- how to inspect the system
- how to safely modify the example

This means:
- explicit structure
- clean naming
- strong boundaries
- minimal ambiguity

---

8. Preserve deterministic output

Any example changes must preserve Foundry’s expectations around determinism:
- same input → same output
- no hidden state
- no hand-edited generated artifacts

---

Acceptance Criteria

- all examples are aligned with Specs 20–24
- no obvious legacy/deprecated patterns remain
- examples are smaller/clearer, not messier
- example docs match actual implementation
- examples work cleanly with current inspect/verify flows
- examples teach current best practice, not historical behavior
- examples are LLM-friendly and deterministic

---

Implementation Bias

Prefer:
- fewer examples that are excellent
over
- many examples that are stale or confusing

If an example is redundant, weak, or misleading, simplify or remove it.

---

Done Means

A developer or LLM can look at the examples and reasonably conclude:

> this is how Foundry wants applications to be built now

------------------------------------------------------------------------------------------

RESULT



------------------------------------------------------------------------------------------



------------------------------------------------------------------------------------------



------------------------------------------------------------------------------------------



------------------------------------------------------------------------------------------



------------------------------------------------------------------------------------------



------------------------------------------------------------------------------------------
