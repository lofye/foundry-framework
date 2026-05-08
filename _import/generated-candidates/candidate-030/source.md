ISSUED AFTER Spec 19F

------------------------------------------------------------------------------------------

STARTING NOW I AM ISSUING A SPEC FREEZE + VERSIONING RULE

Once a spec has been:
1. implemented
2. reviewed
3. aligned with examples

…it becomes a FROZEN CONTRACT.

From that point forward:

1. Specs are NOT casually edited
- No “improvements” to wording or examples
- No silent behavioral drift

2. Any behavioral change MUST follow this flow:
- propose change
- update spec first
- implement change
- re-align examples
- verify determinism + tests

3. Versioning rules:
- Patch (x.y.Z)
  - bug fixes only
  - no contract changes
- Minor (x.Y.0)
  - additive features
  - must be backward compatible
  - spec extended, not rewritten
- Major (X.0.0)
  - breaking changes to:
    - CLI behavior
    - JSON contract
    - section structure
    - explain semantics

4. JSON contract is strictly versioned
- Output shape must remain stable across minor/patch releases
- Any breaking JSON change requires a major version bump

5. Docs are a contract, not marketing
- Documentation must reflect real behavior
- Examples must be accurate and deterministic
- No aspirational or fictional output

6. No hidden behavior
- If it matters, it must exist in:
  - spec
  - implementation
  - tests
  - docs

7. Determinism is mandatory
- Same input → same output
- No timestamps, randomness, or environment leakage

RESULT

Specs become:
- stable
- trustworthy
- enforceable

The system becomes:
- predictable for developers
- safe to build on
- resistant to drift

------------------------------------------------------------------------------------------

The freeze/versioning rule is now encoded in [AGENTS.md](/Users/lo_fye/code/foundry-framework/AGENTS.md) and [docs/public-api-policy.md](/Users/lo_fye/code/foundry-framework/docs/public-api-policy.md). The contributor guide now treats implemented-reviewed-example-aligned contracts as frozen, requires docs-first for behavioral changes, and makes determinism mandatory. The public API policy now spells out patch/minor/major semantics, strict stable JSON versioning, and the rule that docs/examples are contract surface rather than aspirational copy.

Validation: `vendor/bin/phpunit tests/Phrasing/ForbiddenInternalTerminologyTest.php` passed. No runtime code changed. If you want this enforced beyond documentation, the next step is CI gates around stable JSON fixtures and release-type checks.

2 files changed
+42
-3
Undo
AGENTS.md
docs/public-api-policy.md
