# Execution Spec: 003-common-workflow-batch-commands

## Feature
- cli-experience

## Purpose
- Reduce repetitive Foundry CLI typing by adding first-class batch workflow commands for command groups users commonly run together.
- Shorten routine user workflows and demos without turning Foundry workflows into opaque shell macros.
- Preserve deterministic command behavior, structured JSON output, and existing safety gates while making common workflows easier to invoke.

## Scope
- Add batch commands for the repeated command groups visible in `docs/demos/foundry-blog-live-demo-script.md`.
- Implement batch commands as Foundry CLI commands or options that internally run existing command services in deterministic order.
- Return a structured summary of child command results for each batch command.
- Stop on blocking failures unless an explicit inspect-only command is designed to collect diagnostics after failure.
- Keep active/draft execution-spec rules, feature boundary rules, context gates, and quality gates intact.

## Constraints
- Do not implement shell aliases, shell scripts, or hidden user-local shortcuts as the primary solution.
- Do not weaken or skip any existing verification command semantics.
- Do not change public behavior of existing child commands except where explicitly required to compose them safely.
- Do not make batch command success depend on timestamps, random ordering, terminal state, or interactive prompts.
- Do not hide failures behind a green aggregate status.
- Do not promote draft specs, update implementation logs, run destructive operations, publish, deploy, push, or commit changes in this spec.
- Preserve stable JSON shapes for existing commands.
- If a new command is marked stable, add it to the CLI surface registry, help output, tests, and CLI surface verification.
- If a command is too broad for stable status immediately, expose it as experimental and label it clearly in help and JSON metadata.

## Inputs

Expect inputs such as:

```bash
foundry doctor --ready --json
foundry context bootstrap blog --json
foundry spec:promote blog 001 --json
foundry verify architecture --json
foundry verify feature-work blog --json
foundry verify done --feature=blog --coverage-min=90 --json
foundry explain feature blog --full --json
foundry generate docs --all --json
foundry test feature blog --json
foundry context recover blog --json
```

If any critical input is missing:
- fail clearly and deterministically
- include the missing input in structured error details
- do not partially apply state-changing operations unless the command explicitly documents that earlier safe steps may have completed

## Requested Changes

### 1. Add A Shared Batch Runner Contract

Introduce a small CLI-owned service for running existing command handlers as ordered child steps.

Requirements:
- accept a deterministic ordered list of child command argument arrays
- pass through the repository root and JSON expectation consistently
- capture each child command's:
  - label
  - command signature or rendered argument list
  - exit status
  - structured payload when available
  - error details when available
- stop on first non-zero status by default
- support a `continue_on_failure` mode only for diagnostic workflows that explicitly need multiple reports
- return an aggregate payload with:
  - `ok`
  - `status`
  - `workflow`
  - `steps`
  - `failed_step`
  - `summary`
  - `next_actions`

The batch runner must invoke existing command classes or shared services directly rather than shelling out to `foundry`.

### 2. Add `doctor --ready`

Introduce:

```bash
foundry doctor --ready
```

With JSON:

```bash
foundry doctor --ready --json
```

This command runs the first-run readiness batch:

```bash
foundry doctor --json
foundry compile graph --json
foundry inspect graph --json
foundry inspect pipeline --json
foundry verify graph --json
foundry verify pipeline --json
foundry verify contracts --json
```

Behavior:
- fail if any child step fails
- include the graph and pipeline inspection summaries in the aggregate payload
- identify the first blocking step and suggested next action
- preserve existing `doctor` behavior when `--ready` is not present

### 3. Add `context bootstrap <feature>`

Introduce:

```bash
foundry context bootstrap <feature>
```

This command runs:

```bash
foundry context init <feature> --json
foundry inspect context <feature> --json
foundry verify context --feature=<feature> --json
```

Behavior:
- create missing canonical context files without overwriting existing files
- inspect context after initialization
- verify that context is consumable
- fail if `verify context` returns `can_proceed=false` or `requires_repair=true`
- report created files, existing files, context status, and required actions

### 4. Add `spec:promote <feature> <id>`

Introduce:

```bash
foundry spec:promote <feature> <id>
```

Also support a full spec identity when consistent with existing spec commands:

```bash
foundry spec:promote <feature>/<id>-<slug>
foundry spec:promote <id>-<slug>
```

This command replaces manual draft promotion steps such as:

```bash
mkdir -p Features/Blog/specs
cp Features/Blog/specs/drafts/001-posts-markdown-admin-and-rss.md Features/Blog/specs/001-posts-markdown-admin-and-rss.md
foundry verify context --feature=blog --json
foundry feature:inspect blog --json
foundry feature:map --feature=blog --json
```

Behavior:
- resolve exactly one matching draft execution spec
- refuse when the matching active spec path already exists unless `--force` is explicitly introduced and justified by tests
- move the draft into the active spec directory without changing the filename or heading
- validate naming, heading, duplicate ID, and active/draft placement rules
- run:
  - `verify context --feature=<feature>`
  - `feature:inspect <feature>`
  - `feature:map --feature=<feature>`
- fail if context verification blocks progress
- do not create a reconstruction note, implementation-log entry, or code changes

### 5. Add `verify architecture`

Introduce:

```bash
foundry verify architecture
```

This command runs:

```bash
foundry compile graph --json
foundry inspect graph --json
foundry inspect pipeline --json
foundry verify graph --json
foundry verify pipeline --json
foundry verify contracts --json
```

Behavior:
- provide a concise aggregate architecture health summary
- include graph and pipeline inspection summaries
- fail on the first blocking compile or verification failure
- keep this command independent of feature-specific context checks

### 6. Add `verify feature-work <feature>`

Introduce:

```bash
foundry verify feature-work <feature>
```

This command runs:

```bash
foundry context doctor --feature=<feature> --json
foundry context check-alignment --feature=<feature> --json
foundry verify context --feature=<feature> --json
foundry verify features --feature=<feature> --json
foundry feature:map --feature=<feature> --json
```

Behavior:
- fail if doctor status is `repairable` or `non_compliant`
- fail if alignment status is `mismatch`
- fail if `verify context` reports `can_proceed=false` or `requires_repair=true`
- fail if feature boundary verification fails
- include warnings explicitly without treating them as success-only noise

### 7. Add `verify done --feature=<feature>`

Introduce:

```bash
foundry verify done --feature=<feature>
```

Support:

```bash
foundry verify done --feature=<feature> --coverage-min=90
foundry verify done --feature=<feature> --skip-coverage
foundry verify done --feature=<feature> --phpunit=<path>
```

This command represents the completion quality gate for meaningful feature work.

Default behavior:
- run `verify feature-work <feature>`
- run `verify architecture`
- run focused feature tests when a feature test directory exists
- run the full PHPUnit suite
- run coverage and verify the configured coverage minimum when coverage support is available

Expected child commands:

```bash
foundry verify feature-work <feature> --json
foundry verify architecture --json
php vendor/bin/phpunit Features/<Feature>/tests
php vendor/bin/phpunit
XDEBUG_MODE=coverage php vendor/bin/phpunit --coverage-clover build/coverage/clover.xml
foundry verify coverage --min=<coverage-min> --clover=build/coverage/clover.xml --json
```

Behavior:
- fail if any required verification or test step fails
- report coverage as blocked, not passed, when no coverage driver is available and `--skip-coverage` was not provided
- never silently downgrade the completion gate
- include exact commands or equivalent step labels in JSON so agents can explain what ran

### 8. Add `explain feature <feature> --full`

Extend the explain surface with:

```bash
foundry explain feature <feature> --full
foundry explain feature:<feature> --full
```

The full feature explanation should aggregate the inspection tour:

```bash
foundry explain feature:<feature> --json
foundry inspect feature <feature> --json
foundry feature:inspect <feature> --json
foundry feature:map --feature=<feature> --json
foundry inspect graph --feature=<feature> --json
foundry inspect dependencies feature:<feature> --json
foundry inspect impact feature:<feature> --json
foundry inspect pipeline --json
```

Behavior:
- return one coherent feature dossier rather than raw concatenated output
- include feature identity, root, routes, owned files, tests, dependencies, impacted nodes, pipeline summary, docs, decisions, specs, and plans where available
- support JSON and Markdown output
- keep the existing non-full explain behavior unchanged

### 9. Add `generate docs --all`

Extend:

```bash
foundry generate docs --all
```

This command runs:

```bash
foundry generate docs --format=markdown --json
foundry generate inspect-ui --json
```

Behavior:
- return generated artifact paths for docs, generated docs, and inspect UI output
- fail if either generation step fails
- preserve current `generate docs --format=<format>` behavior when `--all` is not present

### 10. Add `test feature <feature>`

Introduce:

```bash
foundry test feature <feature>
```

Support:

```bash
foundry test feature <feature> --full
foundry test feature <feature> --coverage --coverage-min=90
foundry test feature <feature> --filter=<filter>
```

Behavior:
- resolve the localized feature test directory deterministically
- run only feature-owned tests by default
- with `--full`, run feature tests and then the full PHPUnit suite
- with `--coverage`, generate Clover coverage and run `verify coverage`
- return structured test status, command labels, and failure details
- fail clearly when the feature has no test directory unless `--allow-missing` is explicitly added and tested

### 11. Add `context recover <feature>`

Introduce:

```bash
foundry context recover <feature>
```

This command runs:

```bash
foundry context doctor --feature=<feature> --json
foundry context check-alignment --feature=<feature> --json
foundry context repair --feature=<feature> --json
foundry verify context --feature=<feature> --json
```

Behavior:
- run doctor and alignment first so users can see the pre-repair state
- apply only existing safe normalization repairs
- verify context after repair
- fail if repair cannot make context consumable
- do not implement feature behavior while context is blocked

### 12. Update Help, Completion, And CLI Surface Metadata

For every new or extended command:
- update CLI help output
- update command metadata and stability classification
- update shell completion candidates
- update CLI surface verification expectations
- include examples in human-facing docs

If a command is experimental:
- label it as experimental in help and JSON metadata
- explain why it is not stable yet

### 13. Update Documentation And Demo Script

Update documentation to explain:
- batch workflow commands are first-class Foundry commands, not shell macros
- child steps still run and fail deterministically
- JSON output includes child step results
- which commands are intended for daily use versus completion gates

Update `docs/demos/foundry-blog-live-demo-script.md` to use the new batch commands where appropriate after implementation.

The demo script should become shorter because command batches are real user workflows, not demo-only shortcuts.

### 14. Tests

Add meaningful PHPUnit coverage for:
- batch runner stop-on-failure behavior
- aggregate JSON payload shape
- `doctor --ready`
- `context bootstrap <feature>`
- `spec:promote <feature> <id>`
- `verify architecture`
- `verify feature-work <feature>`
- `verify done --feature=<feature>` success and failure paths
- `explain feature <feature> --full`
- `generate docs --all`
- `test feature <feature>`
- `context recover <feature>`
- help output for new commands
- completion output includes new command surfaces
- CLI surface verification remains green

Tests must assert observable behavior and failure handling. Avoid tests that only assert command existence.

## Non-Goals
- Do not implement these changes while this spec remains in `specs/drafts/`.
- Do not add shell aliases as the primary interface.
- Do not add interactive prompts.
- Do not change execution-spec naming rules.
- Do not weaken draft versus active spec boundaries.
- Do not change feature boundary enforcement semantics.
- Do not remove existing granular commands.
- Do not make the demo script the source of truth for command behavior.
- Do not add push, deploy, release, tag, or commit automation.

## Canonical Context
- Canonical module spec: `Modules/CliExperience/cli-experience.spec.md`
- Canonical module state: `Modules/CliExperience/cli-experience.md`
- Canonical decision ledger: `Modules/CliExperience/cli-experience.decisions.md`

## Authority Rule
- Existing granular commands remain the authoritative behavior units.
- Batch commands are ergonomic orchestration surfaces over existing deterministic commands.
- A batch command must never claim success unless every required child step passed.
- Draft execution specs remain non-executable until explicitly promoted.

## Completion Signals
- `php bin/foundry spec:validate --json` passes.
- `php bin/foundry verify context --feature=cli-experience --json` passes.
- `php bin/foundry verify cli-surface --json` passes.
- Focused CLI tests for new batch commands pass.
- Full PHPUnit suite passes.
- Coverage gate passes at the configured threshold.
- `docs/demos/foundry-blog-live-demo-script.md` uses the new batch commands and is materially shorter without losing workflow clarity.

## Post-Execution Expectations
- Users can run common Foundry workflows with one command instead of repeatedly typing five or six commands.
- Agents can consume structured batch output and still explain individual child-step failures.
- Demo and onboarding docs become shorter because the product has real workflow commands.
- Foundry remains explicit, deterministic, and inspectable even when common workflows are easier to invoke.
