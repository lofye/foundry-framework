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
