# Foundry Blog Demo Short Script

Target length: 20 minutes including banter.

Audience: developers evaluating whether they can start using Foundry immediately.

Demo goal: show the critical path from a blank Foundry app at `~/code/demo` to an app-local Blog feature with durable context, an executable spec, implementation, inspection, verification, and a local Valet site at `https://demo.test`.

## Short Demo Rule

Keep this demo on rails.

Do show:

- Composer install and app creation.
- Valet linking and HTTPS at `https://demo.test`.
- The scaffolded top-level `Features/`, `Modules/`, and `Packs/` roots.
- Feature context under `Features/Blog/`.
- Draft spec, promotion, implementation prompt, and verification.
- The implemented Blog surfaces and durable artifacts.
- The ideas that make Foundry different.

Do not show:

- Pack or marketplace workflows.
- Long file listings.
- Full test logs unless something fails.
- Every generated file.
- Deep implementation details unless the room asks.

## Pre-Demo Setup

Run these before people are watching:

```bash
php -v
composer --version
git --version
valet --version
```

Optional framework-repo sanity check:

```bash
cd /Users/lo_fye/code/foundry-framework
./foundry doctor --ready --json
```

## 0:00 - 2:00: Frame The Demo

Say this:

> I am going to start from a blank project, install Foundry with Composer, describe a blog feature in normal product language, turn that conversation into durable repo artifacts, then implement and verify it. The point is not a blog. The point is the workflow: intent becomes context, context becomes specs, specs become implementation, and verification decides whether we are done.

One-sentence mental model:

```text
Conversation -> Feature Context -> Draft Spec -> Active Spec -> Implementation -> Tests -> Verification -> Reconstruction
```

## 2:00 - 5:00: Install, Create, And Link The App

```bash
mkdir -p ~/code/demo
cd ~/code/demo
composer require lofye/foundry-framework
./vendor/bin/foundry new .
composer install
./foundry doctor --ready --json
valet link demo
valet secure demo
```

Note:

- `standard` is the default starter, so the demo omits `--starter=standard`.
- `--json` stays on verification and inspection commands because those outputs are useful for agents and automation.

Show only the important app roots:

```bash
ls
find Features -maxdepth 2 -type d | sort
```

Say this:

> Foundry installs like a normal Composer package. In this demo, `~/code/demo` becomes the app root, the project-local `foundry` binary gives the agent and the developer the same structured workflow, and Valet serves the app at `https://demo.test`.

## 5:00 - 8:00: Create Durable Blog Context

Ask Codex live:

```text
Please create a blog feature for this Foundry app. It should be able to have many posts, and have an RSS feed, and the ability for one admin to log in and compose or edit a post in Markdown, then publish it when ready. Include a default stylesheet and draft and published states. Ask me any clarifying questions you need.
```

Use these answers to keep scope tight:

```text
Use app-local storage that is easy to inspect in the repo. I want a browser admin UI for the one admin, plus enough command support to seed or test posts. RSS should use excerpts. Drafts should be hidden from public pages and RSS. Keep tags, categories, comments, search, and multiple authors out of v1. This is a normal app-local Feature under Features/Blog, not a pack.
```

Initialize context:

```bash
./foundry context bootstrap blog --json
find Features/Blog -maxdepth 2 -type f | sort
```

Point out:

- `Features/Blog/blog.spec.md` preserves intent.
- `Features/Blog/blog.md` records current state.
- `Features/Blog/blog.decisions.md` preserves why decisions were made.

## 8:00 - 11:00: Draft And Promote The Execution Spec

Ask Codex live:

```text
Create a draft execution spec for this Blog feature at Features/Blog/specs/drafts/001-posts-markdown-admin-and-rss.md. Follow Foundry's execution spec naming rules. Make it specific enough to implement and test.
```

Show the top of the draft:

```bash
sed -n '1,120p' Features/Blog/specs/drafts/001-posts-markdown-admin-and-rss.md
```

Say this:

> Draft specs are review space. They are not executable work orders yet.

Promote it:

```bash
./foundry spec:promote blog 001 --json
./foundry verify feature-work blog --json
```

Say this:

> Promotion is the moment we say this is no longer brainstorming. This is now executable work.

## 11:00 - 15:00: Implement The Spec

Ask Codex live:

```text
Implement Features/Blog/specs/001-posts-markdown-admin-and-rss.md using the strict Foundry feature workflow. Read the feature context first, make the smallest app-local implementation that satisfies the spec, add meaningful tests, update docs and decisions, write the reconstruction note, update Features/implementation.log, and run the verification gates before claiming completion.
```

While Codex works, narrate the contract:

- Feature-owned code belongs under `Features/Blog/src/`.
- Feature-owned tests belong under `Features/Blog/tests/`.
- `Modules/` is reserved for Foundry/framework governance, and `Packs/` is reserved for future extension packs.
- Drafts must be hidden from public pages and RSS.
- Tests, decisions, docs, reconstruction, and verification are part of done.

After implementation, show the artifact trail:

```bash
find Features/Blog -maxdepth 5 -type f | sort
sed -n '1,180p' Features/Blog/outcomes/001-posts-markdown-admin-and-rss.md
sed -n '1,160p' Features/Blog/blog.decisions.md
```

Say this:

> The reconstruction note is not a plan. It is what actually happened. That is what lets a future developer or future agent resume without needing this room, this chat, or this recording.

## 15:00 - 18:00: Run, Inspect, Verify

If the implementation includes Blog CLI helpers, create and publish one post:

```bash
./foundry blog:post:create --title="Hello Foundry" --body="# Hello Foundry" --status=draft --json
./foundry blog:post:list --json
./foundry blog:post:publish <id-from-create-output> --json
```

If it is browser-only, use the admin UI:

```text
Open /admin/login
Open /admin/blog/posts
Create "Hello Foundry"
Save as draft
Publish
```

Open the Valet site and show the public surfaces:

```text
https://demo.test/blog
https://demo.test/blog/hello-foundry
https://demo.test/blog/rss.xml
```

Inspect and verify:

```bash
./foundry explain feature blog --full --json
./foundry verify done --feature=blog --coverage-min=90 --json
```

If the demo machine has no coverage driver:

```bash
./foundry verify done --feature=blog --skip-coverage --json
```

Say this:

> Foundry does not consider implementation complete because the agent says it is done. It is complete when the repo passes the gates.

## 18:00 - 20:00: Close

Show the diff:

```bash
git status --short
git diff --stat
```

Say this:

> What you saw is not just code generation. Foundry gives the agent a constrained environment: explicit context, spec discipline, graph compilation, inspectable outputs, feature boundaries, and verification gates. That is how we make LLM work repeatable enough for real teams.

Short close:

- Install with Composer.
- Describe the feature.
- Anchor the feature context.
- Draft and promote an execution spec.
- Let Codex implement against the spec.
- Inspect the feature.
- Verify before calling it done.

## Unique Foundry Ideas To Highlight

- LLM-first framework: Foundry is designed around agents needing context, constraints, and deterministic feedback.
- App-local feature roots: a developer can inspect one feature in one place instead of loading the whole app into their head.
- Spec as source of truth: implementation is downstream from durable intent.
- Context anchoring: feature spec, state, and decisions survive beyond chat.
- Draft versus active specs: brainstorming and executable work have different paths.
- Reconstruction notes: completed work records how the implementation actually landed.
- Canonical graph: features, routes, actions, schemas, pipelines, guards, events, and generated outputs become inspectable structure.
- Refuse-to-proceed gates: agents stop when context is missing, stale, or non-consumable.
- Feature boundary verification: app-specific behavior stays inside the owning feature.
- Explain surfaces: developers can ask what exists, why it exists, and where it came from.
- Quality enforcement: tests and coverage are part of the definition of done.

## Fast Recovery Lines

If context is missing:

```bash
./foundry context bootstrap blog --json
```

If context verification fails:

```bash
./foundry context recover blog --json
```

If feature work verification fails:

```bash
./foundry verify feature-work blog --json
```

If tests fail:

```bash
./foundry test feature blog --json
```

Say this if anything goes sideways:

> This is actually part of the point. Foundry is designed to fail with structured diagnostics instead of letting an agent quietly drift.

## One-Screen Command Recap

```bash
mkdir -p ~/code/demo
cd ~/code/demo
composer require lofye/foundry-framework
./vendor/bin/foundry new .
composer install
./foundry doctor --ready --json
valet link demo
valet secure demo

./foundry context bootstrap blog --json

# Live Codex prompt:
# Please create a blog feature for this Foundry app. It should have many posts, an RSS feed,
# and the ability for one admin to log in and compose or edit a post in Markdown,
# then publish it when ready. Include a default stylesheet and draft and published states.
# Ask me any clarifying questions you need.

sed -n '1,120p' Features/Blog/specs/drafts/001-posts-markdown-admin-and-rss.md
./foundry spec:promote blog 001 --json
./foundry verify feature-work blog --json

# Live Codex prompt:
# Implement Features/Blog/specs/001-posts-markdown-admin-and-rss.md using the strict
# Foundry feature workflow.

find Features/Blog -maxdepth 5 -type f | sort
./foundry explain feature blog --full --json
./foundry verify done --feature=blog --coverage-min=90 --json
```
