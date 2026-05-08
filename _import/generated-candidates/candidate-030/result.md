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
