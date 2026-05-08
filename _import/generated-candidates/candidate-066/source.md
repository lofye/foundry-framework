Spec 30F — Confidence Scoring for Explain, Generate, and Iteration

Preface

Foundry already explains systems and generates changes.
To make that loop trustworthy, the framework should also estimate:

how confident it is that the explanation is complete,
how safe the generation plan is,
and how reliable the resulting change appears to be.

This spec introduces a deterministic confidence scoring system.

⸻

Goals
	1.	Provide confidence signals for explain output
	2.	Provide confidence signals for generation plans
	3.	Provide post-generation confidence signals after verification
	4.	Make confidence visible to both humans and LLMs
	5.	Keep confidence deterministic and evidence-based

⸻

Non-Goals
	•	Do not use probabilistic AI guesses
	•	Do not invent confidence from vague heuristics
	•	Do not hide low confidence
	•	Do not replace verification with confidence scoring

⸻

Core Principle

Confidence must be derived from:
	•	available architectural evidence
	•	graph completeness
	•	target resolution certainty
	•	policy and validation results
	•	verification outcomes

Not from:
	•	intuition
	•	LLM self-reporting
	•	hidden weighting

⸻

Confidence Layers

1. Explain Confidence

How complete and trustworthy the current explanation is.

2. Plan Confidence

How safe and well-grounded the proposed generation plan is.

3. Outcome Confidence

How trustworthy the final result is after execution and verification.

⸻

Required Data Model

Create a normalized structure such as:

{
  "score": 0.91,
  "band": "high",
  "factors": [
    {
      "name": "target_resolution",
      "score": 1.0,
      "reason": "Target resolved uniquely."
    },
    {
      "name": "graph_coverage",
      "score": 0.9,
      "reason": "Most related nodes were available."
    }
  ],
  "warnings": [],
  "metadata": {
    "schema_version": 1
  }
}


⸻

Confidence Bands

Use deterministic bands:
	•	very_high
	•	high
	•	medium
	•	low
	•	very_low

Do not expose only a raw number. Always include band + factors.

⸻

Part 1 — Explain Confidence

Calculate based on at least:
	•	target resolved uniquely
	•	subject normalization completeness
	•	collector/domain coverage
	•	graph neighborhood completeness
	•	execution flow availability
	•	diagnostics availability
	•	docs/command linkage availability

Lower confidence when:
	•	target is ambiguous
	•	key domains are missing
	•	graph data is incomplete
	•	execution path is only partially explainable

Explain confidence must be returned in:
	•	foundry explain --json
	•	explain snapshots
	•	explain diff output where relevant

⸻

Part 2 — Plan Confidence

Calculate based on at least:
	•	explain confidence of relevant targets
	•	completeness of GenerationContextPacket
	•	policy violations/warnings
	•	plan size/scope
	•	risk level
	•	number of inferred vs explicit changes
	•	number of affected files/subsystems
	•	availability of relevant tests/docs/verification paths

Lower confidence when:
	•	many files are affected
	•	multiple subsystems are touched
	•	policy overrides are required
	•	target context is incomplete
	•	required validation surfaces are missing

Plan confidence must be included in:
	•	GenerationPlan
	•	interactive plan review (Spec 36)
	•	persisted plans (Spec 37)

⸻

Part 3 — Outcome Confidence

Calculate after execution based on:
	•	compile success
	•	doctor success
	•	verify success
	•	tests run / tests passed
	•	policy overrides used
	•	explain diff coherence
	•	unexpected side effects or warnings

Lower confidence when:
	•	verification is skipped
	•	warnings remain
	•	tests are missing
	•	contracts were changed without docs/tests updates
	•	generation succeeded but verification is partial

Outcome confidence must be included in:
	•	generate results
	•	persisted plan execution records
	•	replay/undo summaries where relevant

⸻

Part 4 — Confidence Engine

Introduce:

src/Confidence/ConfidenceEngine.php

Responsibilities:
	•	compute explain confidence
	•	compute plan confidence
	•	compute outcome confidence
	•	emit normalized score structures

Also introduce:
	•	factor calculators
	•	stable weighting rules
	•	deterministic scoring policy

⸻

Part 5 — Weighting Rules

Weighting must be:
	•	explicit
	•	documented
	•	testable
	•	deterministic

Do not hide scoring logic in scattered conditionals.

Preferred approach:
	•	central weighting table or calculator registry

⸻

Part 6 — CLI Surface

Explain

foundry explain --json must include confidence.

Generate

foundry generate --json must include:
	•	plan confidence
	•	outcome confidence

Optional new command

foundry confidence <target> --json

This command may be added if it meaningfully improves discoverability.

⸻

Part 7 — Interactive UX

In interactive generate mode:
	•	show confidence band in plan summary
	•	show top reasons for low confidence
	•	require stronger confirmation for low-confidence plans

⸻

Part 8 — LLM Use

Confidence output must be machine-readable and stable so LLMs can use it to:
	•	decide whether to proceed
	•	ask for clarification
	•	prefer smaller safer plans
	•	identify weak architecture visibility

⸻

Part 9 — Testing

Add tests for:
	•	confidence band boundaries
	•	deterministic scoring
	•	low-confidence ambiguous targets
	•	low-confidence large/risky plans
	•	high-confidence fully verified changes
	•	JSON output contract stability

⸻

Acceptance Criteria
	•	explain includes confidence
	•	generate includes plan and outcome confidence
	•	confidence is deterministic and evidence-based
	•	scoring factors are explicit
	•	interactive generate shows confidence
	•	persisted plans retain confidence data

⸻

Done Means

Foundry can say not only:
	•	what the system is
	•	what will change
	•	what changed

but also:

how trustworthy that understanding and change appears to be.

------------------------------------------------------------------------------------------

RESULT

Implemented Spec 30F with a single deterministic scoring layer in [src/Confidence/ConfidenceEngine.php](/Users/lo_fye/code/foundry-framework/src/Confidence/ConfidenceEngine.php). Explain now carries additive `confidence` data through [src/Explain/ExplainModel.php](/Users/lo_fye/code/foundry-framework/src/Explain/ExplainModel.php), [src/Explain/ExplanationPlan.php](/Users/lo_fye/code/foundry-framework/src/Explain/ExplanationPlan.php), and [src/Explain/ExplainEngine.php](/Users/lo_fye/code/foundry-framework/src/Explain/ExplainEngine.php); snapshots and architectural diffs now persist confidence via [src/Explain/Snapshot/ExplainSnapshotService.php](/Users/lo_fye/code/foundry-framework/src/Explain/Snapshot/ExplainSnapshotService.php) and [src/Explain/Diff/ExplainDiffService.php](/Users/lo_fye/code/foundry-framework/src/Explain/Diff/ExplainDiffService.php); and generate now emits both `plan_confidence` and `outcome_confidence` from [src/Generate/GenerateEngine.php](/Users/lo_fye/code/foundry-framework/src/Generate/GenerateEngine.php) with the confidence contract embedded in [src/Generate/GenerationPlan.php](/Users/lo_fye/code/foundry-framework/src/Generate/GenerationPlan.php). Human-facing output was updated too in [src/CLI/Commands/GenerateCommand.php](/Users/lo_fye/code/foundry-framework/src/CLI/Commands/GenerateCommand.php), [src/Explain/Renderers/TextExplanationRenderer.php](/Users/lo_fye/code/foundry-framework/src/Explain/Renderers/TextExplanationRenderer.php), and [src/Explain/Renderers/MarkdownExplanationRenderer.php](/Users/lo_fye/code/foundry-framework/src/Explain/Renderers/MarkdownExplanationRenderer.php).

I also aligned the docs in [README.md](/Users/lo_fye/code/foundry-framework/README.md), [docs/explain.md](/Users/lo_fye/code/foundry-framework/docs/explain.md), [docs/generate.md](/Users/lo_fye/code/foundry-framework/docs/generate.md), [docs/architecture-tools.md](/Users/lo_fye/code/foundry-framework/docs/architecture-tools.md), [APP-README.md](/Users/lo_fye/code/foundry-framework/APP-README.md), and [APP-AGENTS.md](/Users/lo_fye/code/foundry-framework/APP-AGENTS.md), and added coverage in [tests/Unit/ConfidenceEngineTest.php](/Users/lo_fye/code/foundry-framework/tests/Unit/ConfidenceEngineTest.php) plus the explain/generate integration tests. One intentional boundary: there still is not a separate Spec 36 interactive review surface or persisted-plan store in this repo, so confidence is now carried in the existing explain/plan/generate payloads those later phases can build on.

Verification passed:
- `php bin/foundry verify cli-surface --json` -> `131` valid, `0` invalid, `0` ambiguous, `0` orphan handlers, coverage `1`
- `php bin/foundry compile graph --json` -> success
- `php bin/foundry verify graph --json` -> `ok: true`
- `php vendor/bin/phpunit` -> `415` tests, `3314` assertions

26 files changed
+1174
-25
Undo
APP-AGENTS.md
APP-README.md
README.md
docs/architecture-tools.md
docs/explain.md
docs/generate.md
src/CLI/Commands/GenerateCommand.php
src/Confidence/ConfidenceEngine.php
src/Explain/Diff/ExplainDiffService.php
src/Explain/ExplainEngine.php
src/Explain/ExplainModel.php
src/Explain/ExplanationPlan.php
src/Explain/Renderers/MarkdownExplanationRenderer.php
src/Explain/Renderers/TextExplanationRenderer.php
src/Explain/Snapshot/ExplainSnapshotService.php
src/Generate/GenerateEngine.php
src/Generate/GenerationContextPacket.php
src/Generate/GenerationPlan.php
src/Support/ApiSurfaceRegistry.php
tests/Integration/CLIGenerateCommandTest.php
tests/Integration/CLILicensedCommandsTest.php
tests/Unit/ConfidenceEngineTest.php
tests/Unit/ExplainDiffServiceTest.php
tests/Unit/ExplainEngineTest.php
tests/Unit/ExplainPolishTest.php
tests/Unit/ExplainSnapshotServiceTest.php


------------------------------------------------------------------------------------------



------------------------------------------------------------------------------------------



------------------------------------------------------------------------------------------
