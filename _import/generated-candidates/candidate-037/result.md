Create a report listing:
	•	used framework docs
	•	unused framework docs
	•	used website-authored docs
	•	unused website-authored docs
	•	generated vs static public pages

Do NOT delete or move anything until this report exists.

⸻

Step 2 — Introduce Imported Docs Layer

Create:

content/docs/imported/

This directory is:
	•	populated from framework/docs/
	•	NEVER manually edited

⸻

Step 3 — Sync Framework Docs

Add logic (in build-docs.php or a pre-step) to:
	1.	Read from:

framework/docs/

	2.	Copy selected docs into:

content/docs/imported/

Mapping rules:
	•	Preserve directory structure if present
	•	Flat files go under:

content/docs/imported/legacy/


⸻

Step 4 — Rendering

Ensure build pipeline renders BOTH:

content/docs/authored/
content/docs/imported/

into:

public/docs/{slug}/index.html


⸻

Step 5 — Framework Docs Structure (Forward Only)

DO NOT reorganize all existing docs.

ONLY enforce structure for new canonical docs:

framework/docs/
  philosophy/
  architecture/
  whitepapers/

Required placements:
	•	foundry-philosophy.md → philosophy/
	•	execution-model.md → architecture/
	•	graph-spec.md → architecture/
	•	why-foundry-wins.md → whitepapers/

⸻

Step 6 — Website Docs Boundary

Define clearly:

Website-authored docs (keep in authored/)

Examples:
	•	getting-started
	•	core-concepts
	•	cli-reference
	•	tutorials
	•	onboarding
	•	marketing explanations

Framework docs (must come from framework/docs/)

Examples:
	•	execution model
	•	graph spec
	•	philosophy
	•	architecture internals

⸻

Step 7 — Public HTML Pages

Keep these ONLY in website repo:

public/index.html
public/build.html
public/contributing.html
public/how-foundry-works.html

Do NOT move them into the framework repo.

⸻

Step 8 — AGENTS.md Update

Add this exactly:

⸻

Docs Source of Truth
	•	framework/docs/ is the canonical source of framework documentation
	•	Everything in framework/docs/ is authored (not generated)

⸻

Website Docs Model
	•	content/docs/imported/ → synced from framework (read-only)
	•	content/docs/authored/ → website-authored docs
	•	public/docs/ → generated output

⸻

Rules
	•	Never edit imported docs manually
	•	Never duplicate framework docs in authored/
	•	Always update framework docs in the framework repo
	•	Website only renders and presents docs

⸻

Mental Model

framework/docs/         = truth
content/docs/imported/  = synced copy
content/docs/authored/  = website docs
public/docs/            = output


⸻

Step 9 — Acceptance Criteria
	•	Audit report exists
	•	content/docs/imported/ exists
	•	Framework docs are copied from submodule
	•	Build renders imported + authored docs
	•	No manual duplication of framework docs remains
	•	New canonical docs use subdirectories
	•	Website HTML pages remain in website repo
	•	AGENTS.md updated

⸻

Final Clarification

YES:

Treat everything in framework/docs/ as authored canonical content.

NO:

Do not treat anything in the website repo as canonical for framework architecture.

==========================================================================================

RESULT
