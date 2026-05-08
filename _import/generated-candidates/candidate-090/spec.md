# Spec 15 — Official Example Applications

Proves Foundry works in real-world scenarios.

⸻

Implementation Philosophy

1. Lock Before Expand

Before adding new capabilities:
	•	define the public API
	•	stabilize extension boundaries
	•	validate config rigorously

⸻

2. Make the System Explain Itself

Every major system should be:
	•	inspectable
	•	diagnosable
	•	explainable via CLI + docs

⸻

3. Prefer Determinism Over Magic

Foundry should:
	•	behave predictably
	•	avoid hidden state
	•	make all transformations traceable

⸻

4. Treat Extensions as First-Class Citizens

If extensions are not stable:
	•	the ecosystem cannot grow
	•	the framework cannot scale

⸻

5. Optimize for Understanding

A Foundry app should be:
	•	readable by humans
	•	understandable by LLMs
	•	inspectable via tools

⸻

Exit Criteria for 1.0

Foundry is ready for 1.0 when:

API
	•	Public API is clearly defined and documented
	•	Internal vs public boundaries are enforced

Extensions
	•	Extensions load deterministically
	•	Dependency conflicts are clearly reported

Diagnostics
	•	foundry doctor catches common failures
	•	upgrade-check prevents breaking upgrades

DX
	•	foundry new creates a correct, inspectable app
	•	graph CLI explains system structure clearly

Config
	•	Config is schema-validated and produces actionable errors

Performance
	•	Compile/build steps are cached deterministically

Proof
	•	At least one real app (Thresholds) demonstrates:
	•	features
	•	workflows
	•	events
	•	graph inspection
	•	extension usage

⸻

What 1.0 Explicitly Does NOT Require

To avoid over-scoping:
	•	Telemetry / analytics
	•	Enterprise features
	•	Complex UI tooling
	•	Plugin marketplaces
	•	Visual editors

These can come after 1.0.

⸻

The Core Insight

Foundry’s value is not just that it works.

It’s that:

You can see how it works, trust how it works, and safely extend how it works.

That is what 1.0 must deliver.

⸻

Suggested Execution Order (Condensed)
