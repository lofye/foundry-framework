SPEC 30C-2-WS — Website Realignment

Purpose

Align messaging with new philosophy:

Foundry is fully powerful out of the box
Monetization comes from ecosystem participation

⸻

Required Changes

1. Remove ALL Feature Gating Language

Delete phrases like:
	•	“unlock”
	•	“upgrade”
	•	“Pro features”
	•	“advanced features require license”

⸻

2. Replace with:

“Foundry is fully functional locally.
Licensing is only required for ecosystem features like the marketplace.”

⸻

3. Update Pricing Page Direction

Instead of:
	•	feature tiers

Introduce:

Future-oriented sections:
	•	Marketplace access (future)
	•	Verified packs (future)
	•	Ecosystem participation

⸻

4. Reinforce Identity

Change positioning to:

LLM-first, fully capable locally, ecosystem-powered when connected

⸻

Acceptance Criteria
	•	No mention of gated features anywhere
	•	Messaging is consistent across:
	•	homepage
	•	docs
	•	CLI references
	
	
------------------------------------------------------------------------------------------

RESULT

Updated the site to the new local-first ecosystem model. [public/index.html](/Users/lo_fye/code/foundry-website/public/index.html) and [public/pricing/index.html](/Users/lo_fye/code/foundry-website/public/pricing/index.html) now position Foundry as “LLM-first, fully capable locally, ecosystem-powered when connected,” with pricing reframed around marketplace access, verified packs, and ecosystem participation instead of gated feature tiers. The licensing docs in [content/docs/authored/licensing.md](/Users/lo_fye/code/foundry-website/content/docs/authored/licensing.md) and [content/docs/navigation.json](/Users/lo_fye/code/foundry-website/content/docs/navigation.json) now consistently say that Foundry is fully functional locally and licensing is only for ecosystem features like the marketplace.

I also tightened the build layer so current generated docs don’t leak the old feature-gating model from the pinned framework checkout. [scripts/lib/DocsPipeline.php](/Users/lo_fye/code/foundry-website/scripts/lib/DocsPipeline.php) now rewrites the presented `architecture-tools` copy and the small docs callouts to the new wording, and [scripts/lib/FrameworkInternalsGenerator.php](/Users/lo_fye/code/foundry-website/scripts/lib/FrameworkInternalsGenerator.php) now excludes monetization from the current public internals surface and filters it out of public subsystem relationships. That removed the current [public/docs/framework-internals-monetization/index.html](/Users/lo_fye/code/foundry-website/public/docs/framework-internals-monetization/index.html) page.

`php scripts/build-docs.php` completes successfully. I also ran focused greps across the current tree and found no remaining hits for `unlock`, `Pro features`, `advanced features require a license`, `licensed commands`, `feature gating`, or similar old gating language outside preserved version snapshots. One caveat: existing historical snapshots under [public/docs/versions](/Users/lo_fye/code/foundry-website/public/docs/versions) were left intact on purpose to respect the version-preservation rules, so they may still contain older wording.

12 files changed
+175
-109
Undo
content/docs/authored/ai-development.md
content/docs/authored/cli-reference.md
content/docs/authored/docs-index.md
content/docs/authored/licensing.md
content/docs/navigation.json
public/build.html
public/how-foundry-works.html
public/index.html
public/pricing/index.html
scripts/lib/DocsPipeline.php
scripts/lib/FrameworkInternalsGenerator.php
templates/layout.html


------------------------------------------------------------------------------------------

sorry i forgot to update your copy of the framework before having you do that spec. i have now updated the framework. can you please tweak the changes you made based on the updated version of the framework, as well as make any additional changes you need to make per Spec 30C-2-WS?

------------------------------------------------------------------------------------------

