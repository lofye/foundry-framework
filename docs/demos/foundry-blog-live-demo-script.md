# Foundry Blog Feature Live Demo Script

Date target: Friday, May 29, 2026

Audience: developers evaluating whether they can start using Foundry immediately.

Demo goal: show a real app-local Foundry feature workflow from a blank app to a tested blog implementation. The blog is not a pack and not a reusable marketplace capability. It is a normal `Features/Blog` feature owned by the application.

## Demo Premise

Say this:

> I am going to start from a blank project, install Foundry with Composer, describe a blog feature in normal product language, ask Codex to turn that conversation into a spec, then implement and verify the feature. There is no pre-made spec in this version of the demo. The point is to show how Foundry turns intent into durable repo artifacts.

Important framing:

- This is a normal app-local feature.
- The feature root is `Features/Blog/`.
- Feature-owned runtime code belongs under `Features/Blog/src/`.
- Feature-owned tests belong under `Features/Blog/tests/`.
- Draft specs are for review.
- Active specs are executable work orders.
- Packs are intentionally out of scope for this demo because they are not ready for the immediate user story.

## Pre-Demo Setup

Run these before the room is watching.

```bash
php -v
composer --version
git --version
```

If you are demoing from the framework repo first, confirm the app scaffold docs mention feature-local work:

```bash
cd /Users/lo_fye/code/foundry-framework
sed -n '1,120p' APP-README.md
sed -n '120,220p' APP-AGENTS.md
```

What to point out:

- `Features/<Feature>/<feature>.spec.md` is durable feature intent.
- `Features/<Feature>/<feature>.md` is current state.
- `Features/<Feature>/<feature>.decisions.md` is append-only decision history.
- `Features/<Feature>/specs/*.md` are executable implementation specs.
- `Features/<Feature>/plans/*.md` are reconstruction notes after implementation.

## Phase 1: Install Foundry With Composer

Say this:

> Foundry installs like a normal Composer package. The project-local `foundry` binary gives us context, feature, graph, generation, inspection, verification, and testing workflows.

Commands:

```bash
mkdir -p ~/Desktop/foundry-demo
cd ~/Desktop/foundry-demo
composer require lofye/foundry-framework
./vendor/bin/foundry
```

Create the app:

```bash
./vendor/bin/foundry new blog-demo --starter=standard --json
cd blog-demo
composer install
```

If the generated app exposes a project-local launcher, use it:

```bash
./foundry
```

If your shell resolves local executables, this may also work:

```bash
foundry
```

Run the first health checks:

```bash
./foundry doctor --json
./foundry compile graph --json
./foundry inspect graph --json
./foundry inspect pipeline --json
./foundry verify graph --json
./foundry verify pipeline --json
./foundry verify contracts --json
```

Artifacts to show:

```bash
ls
find . -maxdepth 3 -type f | sort | sed -n '1,100p'
```

Point out:

- `composer.json`
- `foundry` or `vendor/bin/foundry`
- `AGENTS.md`
- `README.md`
- `Features/`
- `.foundry/`
- app source and generated output directories

## Phase 2: Explain Foundry's Mental Model

Say this:

> In a conventional framework, we often start by editing code. In Foundry, we start by preserving intent. The feature spec says what the system should mean, the execution spec says what we are implementing now, and verification checks whether the repo still matches the story.

Show the loop:

```text
Conversation -> Feature Context -> Draft Spec -> Active Spec -> Implementation -> Tests -> Verification -> Reconstruction
```

Key ideas:

- Specs are contracts, not chat notes.
- Context files survive the chat session.
- Decisions explain why behavior exists.
- Reconstruction notes tell the next agent or developer how the implementation actually landed.
- Verification gates keep agents from silently drifting away from intent.

## Phase 3: Start The Blog Conversation

Say this:

> Now I will describe the feature the way a normal app developer would. I am not going to mention packs, marketplaces, or any prepared spec.

Conversation script:

```text
User:
Please create a blog for this Foundry app. It should have many posts, an RSS feed, and the ability for one admin to log in and compose or edit a post in Markdown, then publish it when ready. Include a default stylesheet and draft and published states. Ask me any clarifying questions you need, then turn the result into a Foundry feature spec and an execution spec.

Codex:
I need a few details before writing the spec:
1. Should posts be stored in the database, markdown files, or a simple repository-local content store?
2. Should the admin authoring UI be browser-based, command-based, or both?
3. Should RSS expose full rendered posts or excerpts?
4. Should drafts be completely hidden from public pages and RSS?
5. Do you want tags, categories, comments, search, or multiple authors in this first version?

User:
Use app-local storage that is easy to inspect in the repo. I want a browser admin UI for the one admin, plus enough command support to seed or test posts. RSS should use excerpts. Drafts should be hidden from public pages and RSS. Keep tags, categories, comments, search, and multiple authors out of v1. This is a normal app-local Feature under Features/Blog, not a pack.

Codex:
I will write a bounded app-local Blog feature spec and an execution spec. The v1 scope is one-admin Markdown authoring, draft and published states, public blog index and post pages, RSS excerpts, default styling, deterministic rendering, meaningful tests, feature docs, a decision ledger, and a reconstruction note after implementation.
```

What to say to the room:

> Notice that the conversation has already constrained scope. The agent is not free-writing. We are forcing ambiguity into explicit decisions before implementation.

## Phase 4: Initialize Feature Context

Say this:

> Foundry feature work starts by creating durable context. These files are not generated code. They are the memory of the feature.

Commands:

```bash
./foundry context init blog --json
./foundry inspect context blog --json
./foundry verify context --feature=blog --json
```

Show the created files:

```bash
find Features/Blog -maxdepth 3 -type f | sort
```

Expected context files:

```text
Features/Blog/blog.spec.md
Features/Blog/blog.md
Features/Blog/blog.decisions.md
```

If context verification fails because the files are empty placeholders, ask Codex:

```text
User:
Fill in the canonical Blog feature context from our conversation: update Features/Blog/blog.spec.md, Features/Blog/blog.md, and append the initial decisions to Features/Blog/blog.decisions.md. Then rerun context verification.
```

Then rerun:

```bash
./foundry verify context --feature=blog --json
```

## Phase 5: Create The Draft Execution Spec

Ask Codex:

```text
User:
Create a draft execution spec for this Blog feature at Features/Blog/specs/drafts/001-posts-markdown-admin-and-rss.md. Follow Foundry's execution spec naming rules. Make it specific enough to implement and test.
```

Expected draft path:

```text
Features/Blog/specs/drafts/001-posts-markdown-admin-and-rss.md
```

Show it:

```bash
sed -n '1,220p' Features/Blog/specs/drafts/001-posts-markdown-admin-and-rss.md
```

Expected first line:

```text
# Execution Spec: 001-posts-markdown-admin-and-rss
```

The draft spec should cover:

- public `GET /blog`
- public `GET /blog/{slug}`
- public `GET /blog/rss.xml`
- admin login or admin-only access gate
- admin post list
- admin compose form
- admin edit form
- admin publish action
- post model or storage record
- Markdown source
- deterministic rendered HTML
- excerpt generation
- draft and published states
- default stylesheet
- tests and verification commands
- feature-local files under `Features/Blog/`

Say this:

> Draft specs are intentionally non-executable. That lets us review and revise without accidentally telling an agent to build half-formed intent.

Try the wrong command on purpose:

```bash
./foundry implement spec blog 001 --json
```

Talking point:

> Because the spec is still in `specs/drafts/`, implementation should refuse or fail to resolve it. Promotion is an explicit safety step.

## Phase 6: Review And Promote The Spec

Say this:

> Promotion is the moment we say this is no longer brainstorming. This is now executable work.

Review checklist before promotion:

- Does the heading match the filename?
- Does the spec say `Features/Blog/`, not `Packs/`?
- Does it exclude comments, search, tags, categories, and multi-author workflows?
- Does it require meaningful tests?
- Does it say drafts are hidden from public routes and RSS?
- Does it define the admin workflow clearly?
- Does it include reconstruction, docs, decisions, and verification?

Promote the draft:

```bash
mkdir -p Features/Blog/specs
cp Features/Blog/specs/drafts/001-posts-markdown-admin-and-rss.md Features/Blog/specs/001-posts-markdown-admin-and-rss.md
```

Show the active spec:

```bash
sed -n '1,120p' Features/Blog/specs/001-posts-markdown-admin-and-rss.md
```

Verify context again:

```bash
./foundry verify context --feature=blog --json
./foundry feature:inspect blog --json
./foundry feature:map --feature=blog --json
```

## Phase 7: Ask Codex To Implement The Spec

This is the exact live prompt:

```text
User:
Implement Features/Blog/specs/001-posts-markdown-admin-and-rss.md using the strict Foundry feature workflow. Read the feature context first, make the smallest app-local implementation that satisfies the spec, add meaningful tests, update docs and decisions, write the reconstruction note, update Features/implementation.log, and run the verification gates before claiming completion.
```

If the generated app includes repo-local skills, use the implementation skill:

```text
User:
Use .skills/implement-spec-and-stabilize.skill.md for Features/Blog/specs/001-posts-markdown-admin-and-rss.md.
```

What Codex should do:

- Read `AGENTS.md`.
- Read the reasoning policy if the app has one.
- Read `Features/Blog/blog.spec.md`.
- Read `Features/Blog/blog.md`.
- Read `Features/Blog/blog.decisions.md`.
- Run `./foundry verify context --feature=blog --json`.
- Implement feature-owned code under `Features/Blog/src/`.
- Add feature-owned tests under `Features/Blog/tests/`.
- Add or update feature docs under `Features/Blog/docs/`.
- Append decisions to `Features/Blog/blog.decisions.md`.
- Update the state document `Features/Blog/blog.md`.
- Create `Features/Blog/plans/001-posts-markdown-admin-and-rss.md`.
- Append `Features/implementation.log`.
- Run focused tests, then broader verification.

Say this:

> This is where Foundry differs from prompt-only coding. The agent is doing implementation, but the repo is demanding durable proof: tests, context, graph output, decisions, reconstruction notes, and verification.

## Phase 8: Watch The Feature Artifacts Appear

After implementation, show the feature root:

```bash
find Features/Blog -maxdepth 5 -type f | sort
```

Expected artifact categories:

```text
Features/Blog/blog.spec.md
Features/Blog/blog.md
Features/Blog/blog.decisions.md
Features/Blog/specs/001-posts-markdown-admin-and-rss.md
Features/Blog/specs/drafts/001-posts-markdown-admin-and-rss.md
Features/Blog/plans/001-posts-markdown-admin-and-rss.md
Features/Blog/docs/
Features/Blog/src/
Features/Blog/tests/
```

Show the reconstruction note:

```bash
sed -n '1,220p' Features/Blog/plans/001-posts-markdown-admin-and-rss.md
```

Show the decision ledger:

```bash
sed -n '1,220p' Features/Blog/blog.decisions.md
```

Say this:

> The reconstruction note is not a plan. It is what actually happened. This is what lets a future developer or future agent resume without needing this demo recording.

## Phase 9: Create Demo Blog Content

If the implementation includes admin seed commands, create a post from the terminal:

```bash
mkdir -p content/blog
cat > content/blog/hello-foundry.md <<'MARKDOWN'
# Hello Foundry

This is a Markdown-authored post.

- It starts as a draft.
- It renders deterministically.
- It appears publicly only after publication.
- It is included in RSS once published.

MARKDOWN
```

Example command flow if the spec creates Blog CLI helpers:

```bash
./foundry blog:post:create --title="Hello Foundry" --file=content/blog/hello-foundry.md --status=draft --json
./foundry blog:post:list --json
./foundry blog:post:publish <id-from-create-output> --json
./foundry blog:post:list --json
```

If the implementation is browser-only, use the admin UI instead:

```text
Open /admin/login
Log in as the seeded admin
Open /admin/blog/posts
Create "Hello Foundry"
Paste the Markdown body
Save as draft
Preview or edit
Publish
```

Show where feature state or records live:

```bash
find . -maxdepth 6 -type f | rg 'blog|post|rss|feed|content'
```

Say this:

> We can inspect not only the code, but the state and generated outputs. Foundry treats the system as observable, not as a pile of files that appeared after a prompt.

## Phase 10: Run The App

Start the local server:

```bash
php -S 127.0.0.1:8000 public/index.php
```

In a second terminal:

```bash
curl -s http://127.0.0.1:8000/blog
curl -s http://127.0.0.1:8000/blog/hello-foundry
curl -s http://127.0.0.1:8000/blog/rss.xml
```

Show in a browser:

```text
http://127.0.0.1:8000/blog
http://127.0.0.1:8000/blog/hello-foundry
http://127.0.0.1:8000/blog/rss.xml
```

Admin surfaces to show if implemented:

```text
http://127.0.0.1:8000/admin/login
http://127.0.0.1:8000/admin/blog/posts
http://127.0.0.1:8000/admin/blog/posts/new
```

## Phase 11: Inspect And Explain The Feature

Commands:

```bash
./foundry explain feature:blog --json
./foundry explain feature:blog --markdown
./foundry inspect feature blog --json
./foundry feature:inspect blog --json
./foundry feature:map --feature=blog --json
./foundry inspect graph --json
./foundry inspect dependencies feature:blog --json
./foundry inspect impact feature:blog --json
./foundry inspect pipeline --json
```

Good things to point at:

- feature identity: `blog`
- feature root: `Features/Blog`
- public routes
- admin routes
- schemas or storage contracts
- tests owned by the feature
- docs, decisions, and reconstruction note
- graph dependencies

Say this:

> The explain output is for humans and automation. It lets a developer ask "what exists, why does it exist, and where did it come from?" without spelunking through the whole app.

## Phase 12: Generate Documentation And Inspection UI

Commands:

```bash
./foundry generate docs --format=markdown --json
./foundry generate inspect-ui --json
```

Show artifacts:

```bash
find docs -maxdepth 4 -type f | sort | sed -n '1,120p'
find docs/generated -maxdepth 4 -type f | sort
find docs/inspect-ui -maxdepth 4 -type f | sort
```

Talking point:

> The same source of truth can feed code, docs, inspection, and automation. That is the system-compilation idea.

## Phase 13: Verification Gate

Feature-focused commands:

```bash
./foundry context doctor --feature=blog --json
./foundry context check-alignment --feature=blog --json
./foundry verify context --feature=blog --json
./foundry verify features --feature=blog --json
./foundry feature:map --feature=blog --json
```

Graph and contract commands:

```bash
./foundry compile graph --json
./foundry inspect graph --json
./foundry inspect pipeline --json
./foundry verify graph --json
./foundry verify pipeline --json
./foundry verify contracts --json
```

Test commands:

```bash
php vendor/bin/phpunit Features/Blog/tests
php vendor/bin/phpunit
```

Coverage command, if a coverage driver is available:

```bash
XDEBUG_MODE=coverage php vendor/bin/phpunit --coverage-clover build/coverage/clover.xml
./foundry verify coverage --min=90 --clover=build/coverage/clover.xml --json
```

If the default PHP binary has no coverage driver, use a PHP binary that has Xdebug or PCOV:

```bash
XDEBUG_MODE=coverage /opt/homebrew/bin/php vendor/bin/phpunit --coverage-clover build/coverage/clover.xml
./foundry verify coverage --min=90 --clover=build/coverage/clover.xml --json
```

Say this:

> Foundry does not consider implementation complete because the agent says it is done. It is complete when the repo passes the gates.

## Phase 14: Show The Diff

Commands:

```bash
git status --short
git diff --stat
git diff -- Features/Blog
```

Show the durable artifacts:

```bash
find Features/Blog -maxdepth 5 -type f | sort
sed -n '1,220p' Features/Blog/plans/001-posts-markdown-admin-and-rss.md
sed -n '1,160p' Features/implementation.log
```

Narrate the artifact trail:

- Conversation clarified intent.
- Canonical feature context preserved the product meaning.
- Draft spec captured the proposed implementation.
- Active spec became the implementation contract.
- Source files implemented behavior.
- Tests proved behavior.
- Docs explained behavior.
- Decisions preserved why.
- Reconstruction note preserved how.
- Inspect, explain, and verify made the result machine-readable.

## Phase 15: Clean Demo Close

Say this:

> What you saw is not just code generation. Foundry gives the agent a constrained environment: explicit context, spec discipline, graph compilation, inspectable outputs, feature boundaries, and verification gates. That is how we make LLM work repeatable enough for real teams.

Short summary for developers:

- Install with Composer.
- Create or open a Foundry app.
- Describe the feature.
- Initialize feature context.
- Turn the conversation into a draft spec.
- Promote the spec.
- Let Codex implement against the spec.
- Inspect the graph and feature state.
- Run verification.
- Keep every important artifact in the repo.

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

## Near-Future Features To Mention

These are based on current framework direction and open work, not public promises:

- Stronger localized feature-boundary enforcement and migration tooling.
- Richer inspect UI surfaces for feature context, graph dependencies, and verification status.
- Better generation previews and undo/restoration flows.
- More complete policy templates for generated changes.
- Deeper context repair and alignment workflows.
- Pack and marketplace workflows once packs are ready; do not position them as part of this immediate app-local demo.
- Event-system expansion such as async dispatch or richer listener patterns if promoted by specs.
- MCP transport expansion beyond the current local/stdio-focused path if promoted.

## Fast Recovery If Something Goes Sideways

If context is missing:

```bash
./foundry context init blog --json
./foundry inspect context blog --json
./foundry verify context --feature=blog --json
```

If context verification fails:

```bash
./foundry context doctor --feature=blog --json
./foundry context check-alignment --feature=blog --json
./foundry context repair --feature=blog --json
./foundry verify context --feature=blog --json
```

If feature boundaries fail:

```bash
./foundry verify features --feature=blog --json
./foundry feature:map --feature=blog --json
```

If graph compilation fails:

```bash
./foundry compile graph --json
./foundry inspect graph --json
./foundry doctor --json
```

If tests fail:

```bash
php vendor/bin/phpunit Features/Blog/tests
php vendor/bin/phpunit --filter Blog
php vendor/bin/phpunit
```

If you need to explain the failure to the room:

> This is actually part of the point. Foundry is designed to fail with structured diagnostics instead of letting an agent quietly drift. The output tells us whether the issue is context, spec shape, feature boundaries, graph compilation, tests, or coverage.

## One-Screen Command Recap

```bash
mkdir -p ~/Desktop/foundry-demo
cd ~/Desktop/foundry-demo
composer require lofye/foundry-framework
./vendor/bin/foundry new blog-demo --starter=standard --json
cd blog-demo
composer install

./foundry doctor --json
./foundry compile graph --json
./foundry verify graph --json

./foundry context init blog --json
./foundry inspect context blog --json
./foundry verify context --feature=blog --json

# Live Codex prompt:
# Please create a blog for this Foundry app. It should have many posts, an RSS feed,
# and the ability for one admin to log in and compose or edit a post in Markdown,
# then publish it when ready. Include a default stylesheet and draft and published states.
# Ask me any clarifying questions you need, then turn the result into a Foundry
# feature spec and an execution spec.

sed -n '1,220p' Features/Blog/specs/drafts/001-posts-markdown-admin-and-rss.md
mkdir -p Features/Blog/specs
cp Features/Blog/specs/drafts/001-posts-markdown-admin-and-rss.md Features/Blog/specs/001-posts-markdown-admin-and-rss.md

./foundry verify context --feature=blog --json
./foundry feature:inspect blog --json
./foundry feature:map --feature=blog --json

# Live Codex prompt:
# Implement Features/Blog/specs/001-posts-markdown-admin-and-rss.md using the strict
# Foundry feature workflow.

find Features/Blog -maxdepth 5 -type f | sort
./foundry explain feature:blog --json
./foundry inspect graph --json
./foundry inspect pipeline --json
./foundry verify features --feature=blog --json

php vendor/bin/phpunit Features/Blog/tests
php vendor/bin/phpunit
XDEBUG_MODE=coverage php vendor/bin/phpunit --coverage-clover build/coverage/clover.xml
./foundry verify coverage --min=90 --clover=build/coverage/clover.xml --json
```
