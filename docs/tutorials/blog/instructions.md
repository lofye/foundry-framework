# Build A Blog With Foundry

This tutorial walks through a complete Foundry application workflow from a blank directory to a small Blog feature with durable context, an executable spec, implementation artifacts, graph inspection, verification, and a local Valet site.

The blog is intentionally ordinary. The point is the workflow around it: product intent becomes repository-local context, context becomes a draft execution spec, the draft is promoted into executable work, implementation happens inside the owning feature boundary, and verification decides whether the work is complete.

Foundry is different from a prompt-only coding loop because the important knowledge is not trapped in chat. The feature spec, current state, decision ledger, execution specs, implementation log, tests, and reconstruction notes all live in the repository. A developer or future agent can inspect those files later and understand what was intended, what was built, why it was built that way, and how completion was verified.

## What You Will Build

You will create a Foundry app at `~/code/tutorials/blog` and serve it locally at `https://blog.test`.

The Blog feature should support:

- many posts
- draft and published states
- public blog pages
- public RSS output
- one admin who can compose and edit Markdown posts
- enough local storage and test support to make the feature easy to inspect

The first version intentionally excludes tags, categories, comments, search, and multiple authors. That keeps the feature bounded enough to show Foundry's workflow without turning the tutorial into a CMS project.

## Prerequisites

Confirm the local tools before creating the app:

```bash
php -v
composer --version
git --version
valet --version
```

If you are working inside the Foundry framework repository first, you can also run:

```bash
cd /Users/lo_fye/code/foundry-framework
./foundry doctor --ready --json
```

In the framework repository, commands use `./foundry ...`. In a generated Foundry app, commands use the app-local `foundry` launcher. If your shell does not resolve current-directory executables, use `./foundry ...` from the app root.

## 1. Create The App

Create a blank project directory, install Foundry as a normal Composer package, scaffold the app, install dependencies, and run the first health check.

```bash
mkdir -p ~/code/tutorials/blog
cd ~/code/tutorials/blog
composer require lofye/foundry-framework
./vendor/bin/foundry new .
composer install
./foundry doctor --ready --json
```

Foundry scaffolds a project-local `foundry` launcher into the app. That launcher is important because the developer and the agent now share the same command surface. Both can inspect context, compile the graph, promote specs, and run verification without relying on a global binary or unstated local setup.

The standard starter creates the top-level workspace roots used by the recent feature-boundary refactor:

```bash
ls
find Features Modules Packs -maxdepth 2 -type d | sort
```

Those roots have separate meanings:

- `Features/` is where application-owned features live.
- `Modules/` is reserved for Foundry or framework governance context.
- `Packs/` is reserved for installed extension packs.

For this tutorial, the blog is application behavior, so it belongs under `Features/Blog/`. It is not a framework module and it is not a pack.

## 2. Link The Valet Site

Use Valet to serve the app locally:

```bash
valet link blog
valet secure blog
```

After the feature is implemented, the app should be reachable at:

```text
https://blog.test
```

The exact web pages do not exist yet. Foundry starts by anchoring intent before writing the feature.

## 3. Create Durable Blog Context

Ask your coding agent to create the Blog feature context. The prompt should describe product intent in ordinary language and keep the scope narrow:

```text
Please create a blog feature for this Foundry app. It should support many posts, an RSS feed, and one admin who can log in, compose or edit a post in Markdown, and publish it when ready. Include a default stylesheet and draft and published states.

Use app-local storage that is easy to inspect in the repo. I want a browser admin UI for the one admin, plus enough command support to seed or test posts. RSS should use excerpts. Drafts should be hidden from public pages and RSS. Keep tags, categories, comments, search, and multiple authors out of v1. This is a normal app-local Feature under Features/Blog, not a pack.
```

Then initialize and verify the canonical context files:

```bash
./foundry context bootstrap blog --json
find Features/Blog -maxdepth 2 -type f | sort
```

`context bootstrap` is a batch workflow. It creates missing context when appropriate, inspects the resulting context, and verifies whether the feature is safe to proceed with.

After this step, the feature should have these canonical files:

- `Features/Blog/blog.spec.md`
- `Features/Blog/blog.md`
- `Features/Blog/blog.decisions.md`

The spec records intended behavior. The state file records current reality. The decision ledger records why choices were made. Together, those files are the durable context an agent should read before changing the feature later.

If you want to inspect the lower-level context gates directly, run:

```bash
./foundry context doctor --feature=blog --json
./foundry context check-alignment --feature=blog --json
./foundry inspect context blog --json
./foundry verify context --feature=blog --json
```

Meaningful feature work should not proceed when `verify context` fails. Repair context first so implementation does not build on stale or contradictory intent.

## 4. Create A Draft Execution Spec

An execution spec is a bounded work order. In the current workflow, drafts are review space and active specs are executable work. A draft may be discussed, rewritten, or discarded. It should not be implemented until it is promoted.

Ask your coding agent to create the draft:

```text
Create a draft execution spec for this Blog feature. Use the slug posts-markdown-admin-and-rss. Follow Foundry's execution spec naming rules, keep it specific enough to implement and test, and make sure it matches the canonical Blog context.
```

You can also use the CLI to create the initial draft file:

```bash
./foundry spec:new blog posts-markdown-admin-and-rss --json
```

Inspect the generated draft:

```bash
find Features/Blog/specs -maxdepth 3 -type f | sort
sed -n '1,160p' Features/Blog/specs/drafts/001-posts-markdown-admin-and-rss.md
```

Execution spec identity comes from the filename. The heading must mirror the filename:

```text
# Execution Spec: 001-posts-markdown-admin-and-rss
```

The numeric ID is an ordered contract. Foundry validates contiguous IDs so agents do not skip work accidentally or create ambiguous spec histories.

## 5. Promote The Spec

Once the draft describes the intended work accurately, promote it:

```bash
./foundry spec:promote blog 001 --json
./foundry verify feature-work blog --json
```

Promotion moves the spec from `Features/Blog/specs/drafts/` into `Features/Blog/specs/`. That is the moment the file becomes executable work rather than planning material.

`verify feature-work` is another batch workflow. It checks the feature context, feature boundaries, and feature mapping in one deterministic pass. If it fails, fix the reported context or boundary issue before implementation.

## 6. Implement The Blog Feature

Ask your coding agent to implement the promoted spec:

```text
Implement Features/Blog/specs/001-posts-markdown-admin-and-rss.md using the strict Foundry feature workflow. Read the Blog feature context first, make the smallest app-local implementation that satisfies the active spec, add meaningful tests, update state and decisions, write the reconstruction note, update Features/implementation.log, and run the verification gates before claiming completion.
```

The important contract during implementation is feature locality:

- Blog-owned runtime code belongs under `Features/Blog/src/`.
- Blog-owned tests belong under `Features/Blog/tests/`.
- Blog context, specs, docs, decisions, and outcomes belong under `Features/Blog/`.
- Shared app or framework surfaces should contain only thin registration glue.
- Generated output should be regenerated, not hand-edited.

This feature-local layout is what lets a developer or agent load one feature directory and get the relevant intent, implementation, tests, and history without searching the whole application.

After implementation, inspect the artifact trail:

```bash
find Features/Blog -maxdepth 5 -type f | sort
sed -n '1,220p' Features/Blog/outcomes/001-posts-markdown-admin-and-rss.md
sed -n '1,180p' Features/Blog/blog.decisions.md
sed -n '1,120p' Features/implementation.log
```

The reconstruction note under `Features/Blog/outcomes/` is not a speculative plan. It records what actually changed: files added, files modified, runtime contracts, tests, deterministic outputs, verification commands, decisions, tradeoffs, and follow-up dependencies. This is what lets future work resume without depending on the original chat.

## 7. Compile, Inspect, And Verify

Compile the canonical application graph:

```bash
./foundry compile graph --json
```

Compilation is one of Foundry's core differences from ordinary code generation. The compiler turns source-of-truth feature files into a canonical graph and build artifacts under `app/.foundry/build/`. That graph makes features, routes, schemas, permissions, events, jobs, tests, and generated outputs inspectable.

Inspect the graph and the Blog feature:

```bash
./foundry inspect graph --json
./foundry feature:inspect blog --json
./foundry feature:map --json
./foundry explain feature blog --full --json
```

Then run the verification gates:

```bash
./foundry verify context --feature=blog --json
./foundry verify features --json
./foundry verify graph --json
./foundry verify pipeline --json
./foundry verify contracts --json
./foundry verify done --feature=blog --coverage-min=90 --json
```

`verify done` is the completion-oriented gate. It includes feature-work verification, tests, and coverage checks when coverage is available. If the local machine does not have a coverage driver installed, use the explicit skip flag and treat that as a local-environment exception rather than a clean production-quality completion:

```bash
./foundry verify done --feature=blog --skip-coverage --json
```

Foundry does not consider implementation complete because the agent says it is done. Completion is tied to repository evidence: context, boundaries, graph integrity, contracts, tests, coverage, implementation logs, and reconstruction notes.

## 8. Try The Blog

If the implementation includes Blog CLI helpers, create and publish a post:

```bash
./foundry blog:post:create --title="Hello Foundry" --body="# Hello Foundry" --status=draft --json
./foundry blog:post:list --json
./foundry blog:post:publish <id-from-create-output> --json
```

If the implementation is browser-only, use the admin UI instead:

```text
Open https://blog.test/admin/login
Open https://blog.test/admin/blog/posts
Create "Hello Foundry"
Save as draft
Publish
```

Open the public surfaces:

```text
https://blog.test/blog
https://blog.test/blog/hello-foundry
https://blog.test/blog/rss.xml
```

Draft posts should stay hidden from public pages and RSS. Published posts should appear in the public list, detail page, and feed.

## 9. Review The Resulting Diff

Before committing, inspect what changed:

```bash
git status --short
git diff --stat
```

The diff should show a feature-local implementation rather than scattered app behavior. Some shared glue may exist, but the Blog feature's context, implementation, tests, specs, and outcomes should be easy to find under `Features/Blog/`.

## Recovery Commands

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

Failures are expected to be structured and actionable. That is part of the design: Foundry should fail with diagnostics instead of letting an agent quietly drift away from the feature contract.

## Command Recap

```bash
mkdir -p ~/code/tutorials/blog
cd ~/code/tutorials/blog
composer require lofye/foundry-framework
./vendor/bin/foundry new .
composer install
./foundry doctor --ready --json
valet link blog
valet secure blog

./foundry context bootstrap blog --json
./foundry spec:new blog posts-markdown-admin-and-rss --json
sed -n '1,160p' Features/Blog/specs/drafts/001-posts-markdown-admin-and-rss.md
./foundry spec:promote blog 001 --json
./foundry verify feature-work blog --json

# Ask your coding agent to implement:
# Features/Blog/specs/001-posts-markdown-admin-and-rss.md

find Features/Blog -maxdepth 5 -type f | sort
./foundry compile graph --json
./foundry inspect graph --json
./foundry explain feature blog --full --json
./foundry verify done --feature=blog --coverage-min=90 --json
```
