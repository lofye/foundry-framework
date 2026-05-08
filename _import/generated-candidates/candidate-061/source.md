Spec 30C-WS — Monetization UX & Product Layer (Website, LLM-First Positioning)

Preface

This spec defines how monetization is presented on:

https://foundryframework.org

This spec is self-contained and does not depend on framework repository specs.

The website must accurately reflect Foundry’s identity as:

an LLM-first, architecture-native framework

Developers are first-class users, but the system is optimized for:
	•	LLM interaction
	•	structured generation
	•	architecture-aware workflows

⸻

Goals
	1.	Make pricing clear and credible
	2.	Maintain trust with technical users
	3.	Reflect Foundry’s LLM-first identity
	4.	Support self-serve adoption
	5.	Avoid SaaS-style marketing patterns

⸻

Core Messaging Principles

1. LLM-first positioning

All messaging must reinforce:
	•	Foundry is designed for LLMs to build and modify applications
	•	Developers guide, review, and collaborate with the system
	•	The architecture exists to make LLM behavior reliable

⸻

2. Neutral monetization language

Use:
	•	“license”
	•	“licensed features”
	•	“advanced capabilities”

Avoid:
	•	“Pro mode”
	•	“upgrade pressure”
	•	marketing-heavy phrasing

⸻

3. Trust over conversion

Prioritize:
	•	clarity
	•	transparency
	•	technical credibility

Over:
	•	urgency
	•	persuasion tactics
	•	conversion tricks

⸻

Required Pages

⸻

A. /pricing

Hero
Headline:
Simple licensing. No surprises.

Subtext:
Foundry is fully usable without a license.
Advanced capabilities are available with a license.

⸻

Positioning Section (NEW — critical)
Add a short section:

How licensing fits into Foundry

Foundry is designed to help LLMs understand and modify real systems safely.

Some advanced capabilities—such as deeper analysis, structured generation, and extended diagnostics—require a license.

Licensing does not change how Foundry works.
It only expands what it can do.

⸻

⸻

Plans Table

Tier	Description
Free	Core architecture system, explain, basic workflows
Licensed	Advanced explain, generate, deep diagnostics, extended capabilities


⸻

⸻

Guarantees Section

Must include:
	•	No background network calls
	•	No hidden telemetry
	•	Works fully offline
	•	License is local-first
	•	Deterministic behavior is preserved

⸻

⸻

CLI Integration Section (important for LLM-first identity)

Show:

foundry license activate --key=YOUR_KEY

Framed as:

Foundry integrates licensing directly into its CLI and workflow.

⸻

⸻

B. /docs/licensing

Must explain:
	•	what licensing affects (capabilities, not core behavior)
	•	local license files
	•	environment variables
	•	optional validation
	•	feature access model (high-level, not internal details)

Include:

Important clarification:

Licensing does not affect:
	•	your code ownership
	•	your application structure
	•	your ability to run Foundry locally

⸻

⸻

C. Homepage Integration

Add section:

LLM-first by design

Foundry is built for LLMs to understand and evolve applications.

Developers remain in control—but the system is optimized for structured, reliable machine interaction.

⸻

Add monetization subsection:

Free by default

Foundry is fully usable without a license.

When you need more advanced capabilities, you can activate a license—no lock-in, no surprises.

⸻

⸻

D. Docs Integration

In relevant pages (generate, explain, diagnostics):

Add small, consistent note:

Some advanced capabilities may require a license.

Do not over-emphasize.

⸻

⸻

Language Rules

Always say:
	•	license
	•	licensed features
	•	advanced capabilities

Never say:
	•	Pro mode
	•	upgrade now
	•	unlock everything
	•	limited-time
	•	growth plan
	•	“for serious developers”

⸻

⸻

Tone Guidelines

Write like:
	•	Stripe docs
	•	early Vercel
	•	Rust docs

Avoid:
	•	startup hype
	•	emotional persuasion
	•	exaggerated claims

⸻

⸻

Acceptance Criteria
	•	No references to “Pro” anywhere
	•	Website stands alone (no spec references)
	•	LLM-first positioning is explicit
	•	Pricing page exists and is clear
	•	Licensing docs exist and are accurate
	•	Homepage reflects identity and monetization subtly
	•	Messaging matches CLI behavior conceptually

⸻

⸻

Result

The website:
	•	correctly positions Foundry as LLM-first
	•	aligns with actual framework behavior
	•	builds trust with technical users
	•	introduces monetization without friction or confusion

⸻

⸻

Final Insight

If the framework says:

“This system is built for LLMs”

Then the website must say the same thing—clearly, consistently, and without dilution.

Otherwise, users will misunderstand what Foundry actually is.

------------------------------------------------------------------------------------------

RESULT

Implemented the monetization layer across the current site. [public/pricing/index.html](/Users/lo_fye/code/foundry-website/public/pricing/index.html) now provides the new pricing page, and [public/index.html](/Users/lo_fye/code/foundry-website/public/index.html), [public/build.html](/Users/lo_fye/code/foundry-website/public/build.html), [public/how-foundry-works.html](/Users/lo_fye/code/foundry-website/public/how-foundry-works.html), and [public/contributing.html](/Users/lo_fye/code/foundry-website/public/contributing.html) now carry the pricing nav/footer language and the LLM-first, free-by-default positioning. The docs side now has a website-owned licensing reference at [content/docs/authored/licensing.md](/Users/lo_fye/code/foundry-website/content/docs/authored/licensing.md), wired through [content/docs/navigation.json](/Users/lo_fye/code/foundry-website/content/docs/navigation.json) and [content/docs/slug-map.json](/Users/lo_fye/code/foundry-website/content/docs/slug-map.json), with subtle licensing notes injected on the relevant explain/generate/diagnostics pages via [scripts/lib/DocsPipeline.php](/Users/lo_fye/code/foundry-website/scripts/lib/DocsPipeline.php).

I also updated [scripts/lib/FrameworkInternalsGenerator.php](/Users/lo_fye/code/foundry-website/scripts/lib/FrameworkInternalsGenerator.php) and [scripts/build-docs.php](/Users/lo_fye/code/foundry-website/scripts/build-docs.php) so current docs builds prune stale generated pages instead of leaving old `framework-internals-pro` artifacts behind. `php scripts/build-docs.php` completes successfully, and a grep over the current site/docs tree found no remaining `Pro` or banned marketing language outside preserved historical version snapshots under `public/docs/versions/`.

15 files changed
+597
-22
Undo
content/docs/authored/ai-development.md
content/docs/authored/cli-reference.md
content/docs/authored/docs-index.md
content/docs/authored/licensing.md
content/docs/navigation.json
content/docs/slug-map.json
public/build.html
public/contributing.html
public/how-foundry-works.html
public/index.html
public/pricing/index.html
scripts/build-docs.php
DocsPipeline.php
+68
-3
Too large to render inline

Review
scripts/lib/FrameworkInternalsGenerator.php
templates/layout.html


------------------------------------------------------------------------------------------



------------------------------------------------------------------------------------------



------------------------------------------------------------------------------------------
