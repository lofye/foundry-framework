# Spec 19N WS - Docs Pipeline Cleanup and Formalization

Do First

Use the docs usage audit as source of truth. Do not guess.
Current pipeline already reads:
	•	framework/docs/
	•	content/docs/imported/
	•	content/docs/authored/
	•	content/docs/generated/

and already writes public/docs/. Do not replace the pipeline.  ￼  ￼

⸻

Goals
	1.	Formalize the current docs pipeline instead of redesigning it
	2.	Define canonical vs website-only docs clearly
	3.	Eliminate duplicate-risk docs
	4.	Migrate execution-model to the canonical framework doc
	5.	Keep the build working throughout

⸻

Rules
	•	Do not replace scripts/build-docs.php
	•	Do not remove imported legacy docs until replacement mapping is live
	•	Do not move website landing HTML pages into the framework repo
	•	Do not manually edit generated output
	•	Treat framework/docs/ as canonical for framework truth
	•	Treat content/docs/authored/ as website-only unless explicitly migrated

⸻

Required Canonical Framework Docs

Ensure these canonical docs exist in the framework repo and are treated as source of truth:
	•	framework/docs/philosophy/foundry-philosophy.md
	•	framework/docs/architecture/execution-model.md
	•	framework/docs/architecture/graph-spec.md

If equivalent flat files already exist, support them temporarily but prefer the namespaced paths.

⸻

Required Website-Owned Docs

Keep these in content/docs/authored/:
	•	getting-started.md
	•	docs-index.md
	•	framework-capabilities.md
	•	core-concepts.md
	•	ai-development.md
	•	architecture-explorer.md
	•	contributing.md

Do not migrate these into the framework repo in this spec.

⸻

Duplicate Cleanup

Resolve the known duplicate-risk pair:
	•	framework/docs/reference.md
	•	content/docs/authored/architecture-reference.md

Choose one owner.

Preferred approach:
	•	keep canonical framework reference in framework/docs/
	•	keep website page only if it is a curated wrapper that clearly derives from framework/generated sources
	•	otherwise retire the authored duplicate

⸻

Execution Model Migration

Current public/docs/execution-model/ is sourced from:

content/docs/imported/legacy/execution-pipeline.md

Change this safely.

Required sequence
	1.	Add/confirm canonical framework execution model doc
	2.	Update import/mapping logic so execution-model page uses canonical execution-model source
	3.	Verify public/docs/execution-model/index.html
	4.	Only after verification, remove or retire old legacy/execution-pipeline.md mapping

Do not delete or rename the legacy file before the new mapping is live.

⸻

Imported Docs Structure

Preserve imported docs.

Use:
	•	content/docs/imported/philosophy/
	•	content/docs/imported/architecture/
	•	content/docs/imported/whitepapers/
	•	content/docs/imported/legacy/

Map canonical framework docs into namespaced imported paths.

Keep existing legacy imports working until replacements are confirmed.

⸻

Build Behavior

Make only minimal build changes needed to:
	1.	prefer canonical namespaced framework docs where available
	2.	preserve legacy fallback where needed
	3.	keep existing public slugs stable unless explicitly changed

⸻

Acceptance Criteria
	•	Current docs build still works
	•	Canonical framework docs are clearly defined
	•	Website-only docs remain in authored/
	•	Duplicate-risk reference overlap is resolved
	•	execution-model page is migrated to canonical framework source
	•	Legacy execution-pipeline import is only removed after migration succeeds
	•	No generated output is manually edited
	•	AGENTS.md is updated to match actual pipeline

⸻

This cleanup should be selective and mapping-first, not file-first.

==========================================================================================

RESULT

**Changed**
- Added the missing canonical framework docs at [foundry-philosophy.md](/Users/lo_fye/code/foundry-website/framework/docs/philosophy/foundry-philosophy.md#L1), [execution-model.md](/Users/lo_fye/code/foundry-website/framework/docs/architecture/execution-model.md#L1), and [graph-spec.md](/Users/lo_fye/code/foundry-website/framework/docs/architecture/graph-spec.md#L1).
- Updated [navigation.json](/Users/lo_fye/code/foundry-website/content/docs/navigation.json#L57) so `philosophy` and `graph-spec` are explicit canonical imported pages, and `execution-model` now prefers `architecture/execution-model.md` with `legacy/execution-pipeline.md` only as fallback.
- Updated [DocsPipeline.php](/Users/lo_fye/code/foundry-website/scripts/lib/DocsPipeline.php#L1247) to resolve imported sections from preferred+fallback candidates and suppress duplicate auto-discovery during the migration.
- Reframed [architecture-reference.md](/Users/lo_fye/code/foundry-website/content/docs/authored/architecture-reference.md#L1) as a website-owned companion instead of a competing source of truth, and aligned [AGENTS.md](/Users/lo_fye/code/foundry-website/AGENTS.md#L44) with the actual pipeline inputs and required canonical docs.

**Verified**
- `php scripts/build-docs.php` succeeded. The current docs alias now includes `/docs/philosophy/` and `/docs/graph-spec/`, and [public/docs/generated/navigation.json](/Users/lo_fye/code/foundry-website/public/docs/generated/navigation.json#L133) shows `execution-model` sourced from `content/docs/imported/architecture/execution-model.md` -> `framework/docs/architecture/execution-model.md`.
- `php scripts/audit-docs-usage.php` succeeded. [build/docs-usage-audit.json](/Users/lo_fye/code/foundry-website/build/docs-usage-audit.json#L474) reports `content/docs/imported/architecture/execution-model.md` as `rendered_by_build`, [build/docs-usage-audit.json](/Users/lo_fye/code/foundry-website/build/docs-usage-audit.json#L573) reports `legacy/execution-pipeline.md` as `present_but_unused`, and [build/docs-usage-audit.json](/Users/lo_fye/code/foundry-website/build/docs-usage-audit.json#L2974) reports `duplicate_pairs: 0`.
- The existing `v0.5.1` versioned snapshots were preserved and not regenerated by design, so the canonical migration is reflected in the current alias pages, not retroactively rewritten into preserved snapshots.
- One follow-up to keep in mind: the new canonical framework docs live inside the `framework` submodule, and `git -C framework status --short` shows them as new files. If you want this landed cleanly, the submodule will need its own commit/pointer update alongside the website repo changes.

8 files changed
+481
-22
Undo
AGENTS.md
content/docs/authored/architecture-reference.md
content/docs/navigation.json
framework/docs/architecture/execution-model.md
framework/docs/architecture/graph-spec.md
framework/docs/philosophy/foundry-philosophy.md
scripts/audit-docs-usage.php
scripts/lib/DocsPipeline.php

------------------------------------------------------------------------------------------

QUESTION

1. Finish 19N WS (safe refactor, no semantic changes)
2. Ask Question 1 (doc ownership + placement)
3. Move files accordingly (or generate a move plan)

QUESTION 1:

You are analyzing the Foundry framework repo and the website repo.

Goal:
Determine which documents currently in:

content/docs/authored/

should be moved into the framework repository under:

framework/docs/

and where they belong structurally.

---

Inputs:

• Website-authored docs:
content/docs/authored/*.md

• Framework docs structure:
framework/docs/

Subdirectories:
- architecture/
- philosophy/
- (root of docs/)

---

Instructions:

1. Read ALL files in content/docs/authored/

2. For EACH file, classify it as one of:

A) Framework canonical document
   → belongs in framework/docs/

B) Website-only document
   → should remain in content/docs/authored/

C) Hybrid/unclear
   → explain reasoning and recommend placement

---

3. For every file classified as (A), determine:

• exact destination path:
  framework/docs/{subdirectory}/{filename}.md

Where subdirectory must be one of:
- architecture/
- philosophy/
- (root)

---

4. Use these decision rules:

Framework docs contain:
• canonical system behavior
• architectural rules
• execution model definitions
• compiler semantics
• reference definitions

Website docs contain:
• onboarding
• tutorials
• navigation pages
• explanations of generated systems
• UX-oriented explanations

---

5. Output MUST be:

### Section 1 — Move to Framework

- filename.md  
  → framework/docs/{path}  
  Reason: ...

### Section 2 — Keep in Website

- filename.md  
  Reason: ...

### Section 3 — Ambiguous / Needs Decision

- filename.md  
  Recommendation: ...
  Reason: ...

---

6. Do NOT modify files.
7. Do NOT move anything.
8. This is an analysis + plan only.

---

Important:

Be decisive.

Avoid “could go either way” unless absolutely necessary.

------------------------------------------------------------------------------------------

ANSWER
