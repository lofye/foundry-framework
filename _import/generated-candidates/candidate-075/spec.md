# Spec 19C

You now have:

feature.spec.md      ← evolving truth
feature.decisions.md ← records 19A, 19B, 19C

⸻

💡 One optional upgrade (very powerful for Foundry)

Inside spec:

## Spec Version
v1.3

Then in decisions:

Updated spec from v1.2 → v1.3

Now you get versioning without fragmentation.

⸻

🎯 Direct answer

Can we split specs or append letters?

👉 Yes — but NOT as multiple .spec.md files

Instead:
	•	keep one spec file
	•	evolve it over time
	•	log every change in the decision ledger
	•	optionally keep execution specs separately

⸻

🚀 My recommendation for Foundry

Do this:

docs/features/<feature>.spec.md        ← canonical truth
docs/features/<feature>.md             ← state
docs/features/<feature>.decisions.md   ← history

docs/specs/35D1.md                     ← execution spec (optional)
docs/specs/35D2.md                     ← execution spec (optional)

⸻

If you want next step

Before you start 35D1, I can:

👉 Add a small “Spec Evolution Rules” section to your AGENTS.md so every LLM follows this correctly

This is one of those tiny additions that prevents massive confusion later.

==========================================================================================

before we do that, just to be clear, when I create:
docs/features/<feature-name>.spec.md as the overall up-to-date spec
I should also create:
docs/specs/<feature-name>/001.md to implement the first one as a record of what was done in the first version
then add
docs/specs/<feature-name>/002.md to implement the second update to the feature
docs/specs/<feature-name>/003.md to implement the third update to the feature

==========================================================================================

Yes — you’re very close, but I want to tighten this so it doesn’t drift or create confusion later.

⸻

✅ The Correct Model (clean + future-proof)

1. Canonical Feature Spec (always ONE)

docs/features/<feature-name>.spec.md

This is:
	•	✅ the current truth
	•	✅ always up-to-date
	•	❌ never versioned via new files
	•	❌ never split into multiple specs

⸻

2. Execution Specs (optional but recommended)

docs/specs/<feature-name>-1.md
docs/specs/<feature-name>-2.md
docs/specs/<feature-name>-3.md

These are:
	•	✅ instructions used to implement changes
	•	✅ historical artifacts of how work was done
	•	❌ NOT the source of truth after implementation

⸻

🧠 The key distinction (this is critical)

File	Role
feature.spec.md	What the feature should be now
docs/specs/*	How the feature was built/changed over time
.decisions.md	Why those changes were made

⸻

⚠️ Important correction

Your version said:

“as a record of what was done in the first version”

👉 That’s not quite right

Execution specs are:

instructions for change, not authoritative records

The real record is:

docs/features/<feature-name>.decisions.md

⸻

✅ Correct workflow

First version
	1.	Create:

docs/features/blog.spec.md

	2.	(Optional) Create execution spec:

docs/specs/blog-1.md

	3.	Implement
	4.	Record decisions in:

docs/features/blog.decisions.md

	5.	Update:

docs/features/blog.md

⸻

Second iteration
	1.	Update:

docs/features/blog.spec.md   ← reflect NEW intent

	2.	Create:

docs/specs/blog-2.md        ← instructions for this change

	3.	Implement
	4.	Append decisions
	5.	Update state doc

⸻

Third iteration

Same pattern.

⸻

❗ Critical rule (prevents chaos)

Execution specs are disposable.
Feature spec + decisions are permanent.

Do NOT rely on:

docs/specs/*

for truth later.

⸻

🔥 Why this matters

If you don’t follow this strictly, you’ll end up with:

blog-v1.spec.md
blog-v2.spec.md
blog-final.spec.md
blog-final-final.spec.md 😄

…and the system collapses.

⸻

🧠 One refinement I strongly recommend

Instead of:

docs/specs/blog-1.md

Use:

docs/specs/blog/001-initial.md
docs/specs/blog/002-add-comments.md
docs/specs/blog/003-add-rss.md

Why this is better
	•	scalable
	•	readable
	•	sortable
	•	self-documenting

⸻

✅ Final answer

👉 Yes — your idea is correct
👉 BUT:
	•	execution specs = instructions, not truth
	•	decisions ledger = history of truth
	•	feature spec = current truth

⸻

🔥 Simple rule to remember

Spec = truth
State = reality
Decisions = why
Execution specs = how we got there (optional)

⸻

If you want next:

👉 I can now write 35D7 so it uses this exact model cleanly (including execution specs vs feature specs).
