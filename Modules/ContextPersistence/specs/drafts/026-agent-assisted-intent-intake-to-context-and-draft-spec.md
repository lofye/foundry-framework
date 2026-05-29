# Execution Spec: 026-agent-assisted-intent-intake-to-context-and-draft-spec

## Feature
- context-persistence

## Purpose
- Let users ask naturally to create a feature, module, pack, or future component without needing to know Foundry's internal artifact names.
- Turn user intent into durable canonical context and one draft execution spec as the default intake workflow.
- Keep V1 honest about current capabilities: Foundry can support an agent-assisted deterministic workflow now, while native CLI AI intake requires a future AI/provider integration.

## Scope
- Define an agent-facing and documentation-backed intake workflow for requests such as:
  - "Create a Blog feature."
  - "Create a Marketplace module."
  - "Create a Stripe billing pack."
  - "Create a DatePicker component." when components become a supported concept.
- If the user provides a meaningful description, use it as the source material for canonical context and draft execution-spec generation.
- If the user provides only a name or vague request, ask targeted clarifying questions before writing canonical artifacts.
- Automatically produce:
  - canonical spec intent
  - canonical state
  - decision-ledger entries
  - one bounded draft execution spec
- Make the draft execution spec non-executable until promoted.
- Update demo and onboarding language so users are not asked to say "feature spec" or "execution spec" unless they are already working at that level.

## Non-Goals
- Do not implement a native LLM provider integration in this spec.
- Do not make the CLI infer product intent from prose without an agent or explicit structured input.
- Do not auto-promote draft execution specs.
- Do not immediately implement generated draft specs.
- Do not remove expert workflows such as `context init`, `plan feature`, `spec:new`, or manual spec authoring.
- Do not introduce components as a fully implemented artifact type unless a separate component-system spec exists.
- Do not make packs or modules follow application-feature placement rules when their owning module contracts say otherwise.

## Constraints
- Foundry must remain deterministic at the framework boundary.
- Agent-assisted prose interpretation must be represented as generated Markdown artifacts that are then validated by existing context and spec validators.
- Missing or ambiguous user intent must result in clarifying questions, not invented requirements.
- Generated execution specs must be drafts by default.
- Canonical context remains authoritative over execution specs.
- Existing feature/module/pack boundaries must be preserved.
- The workflow must be truthful in JSON and docs about whether an AI agent supplied the interpretation.

## Design Position
- V1 is **agent-assisted**, not **framework-native AI**.
- Foundry should provide the artifact contract, prompts/guidance, deterministic validation, and optional structured intake files.
- Codex or another agent can perform the natural-language interpretation step and write the artifacts.
- A future AI integration may reuse the same contract, but this spec must not depend on that integration.

## Requested Changes

### 1. Add An Intent Intake Workflow Contract

Document and support a standard workflow:

```text
User intent -> clarifying questions if needed -> canonical context -> draft execution spec -> verification -> promotion later
```

The user should be able to say:

```text
Create a Blog feature. It should have public posts, an RSS feed, one admin Markdown authoring flow, draft and published states, and default styling.
```

The agent should not require:

```text
Create a Foundry feature spec and execution spec.
```

Instead, the agent should infer that a create-feature request requires durable Foundry artifacts.

### 2. Define Minimum Intake Requirements

For each artifact type, define the minimum information required before writing artifacts.

For an application feature:
- name
- user-facing purpose
- primary actors
- main workflows
- data/storage expectations when relevant
- public/admin/API surfaces when relevant
- explicit exclusions or V1 non-goals
- test/verification expectations

For a framework module:
- module name
- framework capability being changed
- owning subsystem or existing module relationship
- public command/API/docs surfaces affected
- compatibility and migration expectations
- tests and validation gates

For a pack:
- pack name/vendor intent
- reusable capability scope
- app integration points
- entitlement/marketplace expectations when relevant
- generated files or runtime hooks
- tests and documentation expectations

For future components:
- component name
- UI or runtime role
- expected inputs/outputs
- state and accessibility expectations when relevant
- ownership and placement rules from the future component system

If any minimum information is missing, the agent asks concise clarifying questions before creating files.

### 3. Add Agent Guidance For Natural Requests

Update framework and scaffold guidance so agents follow this behavior:
- Treat "create a feature/module/pack/component" as an intent-intake request.
- Ask for missing details only when the request lacks enough substance to create meaningful context.
- Do not force the user to name internal artifacts.
- Write canonical context first.
- Create exactly one draft execution spec for the first bounded slice.
- Run context and spec validation after writing artifacts.
- Tell the user the draft must be promoted before implementation.

### 4. Add Optional Structured Intake Artifact

Introduce an optional, deterministic intake artifact only if useful during implementation:

```text
Features/<Feature>/intake.md
Modules/<Module>/intake.md
Packs/<Vendor>/<Pack>/intake.md
```

The intake artifact, if created, records the user's raw description and clarifying answers.

Rules:
- It is supporting context, not authoritative over the canonical spec.
- It must not replace the canonical spec/state/decision files.
- It should be omitted if the implementation can preserve raw user intent directly in decisions without extra file churn.

### 5. Add Or Extend CLI Support Without Native AI Claims

Choose one conservative CLI path during implementation:

Option A: documentation/agent-only V1
- No new CLI command.
- Update docs, demo scripts, and skills so agents perform the intake workflow with existing commands.

Option B: deterministic intake packet command
- Add a command such as:

```bash
foundry intent:new feature blog --description="..." --json
```

- The command creates or updates an intake packet and initializes context placeholders.
- It does not synthesize full specs from prose without an agent or future AI provider.
- It returns next actions for the agent to fill context and create a draft spec.

Option C: structured non-AI draft generator
- Add a command that accepts explicit structured fields, not arbitrary prose, and renders canonical context/draft specs deterministically.
- The command refuses missing required fields and does not infer unstated behavior.

The implementation must document which option is chosen and why.

### 6. Update The Blog Demo Script

Update `docs/demos/foundry-blog-live-demo-script.md` so the user prompt says something like:

```text
Please create a Blog feature. It should have many posts, an RSS feed, and one admin who can log in, compose or edit posts in Markdown, and publish them when ready. Include a default stylesheet plus draft and published states.
```

The script should no longer require the user to ask for:

```text
turn the result into a Foundry feature spec and an execution spec
```

Instead, the presenter can explain after the prompt that Foundry/Codex turns that intent into:
- canonical feature context
- one draft execution spec
- validation output

### 7. Update Skills And Onboarding Docs

Update relevant repository-local skills and onboarding docs so the recommended flow is:
- user describes desired thing
- agent asks clarifying questions if needed
- agent writes canonical context
- agent writes one draft execution spec
- agent runs validation
- user reviews and promotes when ready

At minimum, audit:
- `AGENTS.md`
- `APP-AGENTS.md`
- `README.md`
- `APP-README.md`
- `docs/demos/foundry-blog-live-demo-script.md`
- relevant `.skills/` workflows
- context/planning docs that currently require the user to know internal artifact names

### 8. Add Tests Or Documentation Assertions

If this spec changes code, add meaningful tests for:
- missing-description intake blocks with clarifying-question guidance
- description-present intake writes/returns deterministic artifact targets
- draft specs remain in `specs/drafts/`
- no automatic promotion or implementation occurs
- docs/scaffold text no longer requires users to ask for both artifact types explicitly

If implementation chooses documentation/agent-only V1, add tests that assert scaffold docs and demo text contain the new user-facing flow and do not teach the old unnatural prompt as the primary path.

## Expected Behavior
- Users can ask to create a feature/module/pack/component in natural product language.
- Agents translate that request into Foundry's durable artifact workflow without making the user know internal file categories.
- Vague requests trigger clarifying questions before files are written.
- Substantive requests produce canonical context plus exactly one draft execution spec.
- Generated draft specs require review and promotion before implementation.
- Foundry docs remain truthful that native CLI AI interpretation is not part of V1.

## Acceptance Criteria
- The blog demo prompt no longer asks the user to request "a feature spec and an execution spec."
- Onboarding docs explain that natural create requests map to canonical context plus a draft execution spec.
- Generated or documented workflows preserve draft-before-active safety.
- No command or doc claims the non-AI CLI can infer full feature intent from arbitrary prose by itself.
- `php bin/foundry spec:validate --json` passes.
- `php bin/foundry verify context --feature=context-persistence --json` passes.
- Relevant docs/scaffold tests pass.
- Full PHPUnit and coverage gates pass if code or scaffold contracts change.

## Verification Commands

```bash
php bin/foundry spec:validate --json
php bin/foundry verify context --feature=context-persistence --json
php bin/foundry verify features --json
php vendor/bin/phpunit
XDEBUG_MODE=coverage php vendor/bin/phpunit --coverage-clover build/coverage/clover.xml
php bin/foundry verify coverage --min=90 --clover=build/coverage/clover.xml --json
```

## Documentation Notes
- Use "create a Blog feature" in user-facing prompts.
- Explain feature spec and execution spec after the user intent has been captured, not as required vocabulary in the user's initial request.
- Prefer "draft implementation spec" or "draft execution spec" when explaining the non-executable intermediate artifact.
- Keep the safety message simple: the draft is reviewable and cannot be implemented until promoted.

## Completion Signals
- Users no longer need to know Foundry's artifact taxonomy to start a feature.
- Agents still produce the full artifact taxonomy because the repository needs durable context.
- The workflow feels conversational at the front door and deterministic after the intent is captured.
