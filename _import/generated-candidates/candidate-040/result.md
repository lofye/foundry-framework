The linker pass is in place, and all changes stayed in the website repo only. [slug-map.json](/Users/lo_fye/code/foundry-website/content/docs/slug-map.json#L1) now defines the 11 required slugs with explicit `framework-owned` and `website-owned` entries, [navigation.json](/Users/lo_fye/code/foundry-website/content/docs/navigation.json#L57) now routes `core-concepts` and `framework-capabilities` from imported framework mirrors, splits `execution-model` from `execution-pipeline`, and keeps `reference` separate from `architecture-reference`, and [DocsPipeline.php](/Users/lo_fye/code/foundry-website/scripts/lib/DocsPipeline.php#L1144) now runs a pre-render slug validation pass that loads the slug map, writes [slug-resolution-report.json](/Users/lo_fye/code/foundry-website/docs-build/slug-resolution-report.json#L1), and fails on missing mapped sources, multiple active claims, framework/website ownership conflicts, or source mismatches. I did not edit anything under `framework/`.

Verification passed. `php scripts/build-docs.php` completed successfully, and [slug-resolution-report.json](/Users/lo_fye/code/foundry-website/docs-build/slug-resolution-report.json#L1) shows `entry_count: 11`, `error_count: 0`, and `warning_count: 0`, with `core-concepts`, `execution-model`, `execution-pipeline`, `framework-capabilities`, and `reference` resolving from imported framework sources while `architecture-reference` stays website-owned. The live generated navigation confirms the active sources in [public/docs/generated/navigation.json](/Users/lo_fye/code/foundry-website/public/docs/generated/navigation.json#L69), and the rendered pages exist at [core-concepts](/Users/lo_fye/code/foundry-website/public/docs/core-concepts/index.html), [framework-capabilities](/Users/lo_fye/code/foundry-website/public/docs/framework-capabilities/index.html), [execution-model](/Users/lo_fye/code/foundry-website/public/docs/execution-model/index.html), [execution-pipeline](/Users/lo_fye/code/foundry-website/public/docs/execution-pipeline/index.html), [reference](/Users/lo_fye/code/foundry-website/public/docs/reference/index.html), and [architecture-reference](/Users/lo_fye/code/foundry-website/public/docs/architecture-reference/index.html). The old authored `core-concepts.md` and `framework-capabilities.md` copies are absent, so there is no remaining website-side active source for those moved docs, and the build preserved version snapshot behavior by reusing the existing `v0.5.2` snapshot instead of regenerating it.

3 files changed
+343
-20
Undo
content/docs/navigation.json
content/docs/slug-map.json
scripts/lib/DocsPipeline.php

------------------------------------------------------------------------------------------

Regarding updating the slug owner values, i want them to be framework instead of framework-owned, and website instead of website-owned. Also, if the public page is public/docs/index.html, then please change the docs-index slug from docs-index to index or whatever the pipeline actually uses as the canonical docs-home slug. After these changes are made, the slug-map should look just like the one I gave you earlier:

{
  "core-concepts": {
    "owner": "framework",
    "source": "content/docs/imported/architecture/core-concepts.md"
  },
  "framework-capabilities": {
    "owner": "framework",
    "source": "content/docs/imported/legacy/framework-capabilities.md"
  },
  "execution-model": {
    "owner": "framework",
    "source": "content/docs/imported/architecture/execution-model.md"
  },
  "execution-pipeline": {
    "owner": "framework",
    "source": "content/docs/imported/legacy/execution-pipeline.md"
  },
  "reference": {
    "owner": "framework",
    "source": "content/docs/imported/legacy/reference.md"
  },
  "architecture-reference": {
    "owner": "website",
    "source": "content/docs/authored/architecture-reference.md"
  },
  "getting-started": {
    "owner": "website",
    "source": "content/docs/authored/getting-started.md"
  },
  "index": {
    "owner": "website",
    "source": "content/docs/authored/docs-index.md"
  },
  "ai-development": {
    "owner": "website",
    "source": "content/docs/authored/ai-development.md"
  },
  "architecture-explorer": {
    "owner": "website",
    "source": "content/docs/authored/architecture-explorer.md"
  },
  "contributing": {
    "owner": "website",
    "source": "content/docs/authored/contributing.md"
  }
}

------------------------------------------------------------------------------------------

[slug-map.json](/Users/lo_fye/code/foundry-website/content/docs/slug-map.json#L1) now matches your JSON exactly: owner values are `framework` and `website`, and the docs-home slug is `index` instead of `docs-index`. I also updated the validator special case in [DocsPipeline.php](/Users/lo_fye/code/foundry-website/scripts/lib/DocsPipeline.php#L1587) so the canonical docs-home slug resolves as `index` to `content/docs/authored/docs-index.md`.

I rebuilt the docs and rechecked the report. [slug-resolution-report.json](/Users/lo_fye/code/foundry-website/docs-build/slug-resolution-report.json#L1) now shows the `index` slug as website-owned, with `entry_count: 11`, `error_count: 0`, and `warning_count: 0`, and `public/docs/index.html` remains the rendered docs home.

2 files changed
+13
-13
Undo
content/docs/slug-map.json
scripts/lib/DocsPipeline.php


------------------------------------------------------------------------------------------

git commit -m "implement Spec 19O-B WS"
