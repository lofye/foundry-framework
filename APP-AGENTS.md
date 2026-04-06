# Foundry App Agent Guide

Use this file when working inside a Foundry application repository.

## Command Rule

- In Foundry app repos, prefer `foundry ...`
- If your shell does not resolve current-directory executables, use `./foundry ...`
- Prefer `--json` for inspect, verify, doctor, prompt, export, and generation commands when an agent is consuming the output

## Source Of Truth

- Treat `app/features/*` as source-of-truth application behavior
- Treat `app/definitions/*` as source-of-truth definitions when that folder exists
- Treat `app/.foundry/build/*` as canonical compiled output
- Treat `.foundry/packs/installed.json` as explicit local pack activation state when packs are in use
- Treat `.foundry/cache/registry.json` as cached hosted-registry metadata when remote pack discovery is used
- Treat `.foundry/packs/*/*/*/foundry.json` as installed pack metadata, not editable app source
- Treat `app/generated/*` as generated compatibility projections
- Treat `docs/generated/*` and `docs/inspect-ui/*` as generated documentation output
- Treat feature context documents under `docs/features/*` as the source of truth for feature intent, state, and reasoning context
- Treat code and tests as the source of truth for actual implementation and runtime behavior
- Do not hand-edit `app/generated/*`; regenerate instead
- Do not hand-edit installed pack files under `.foundry/packs/*`; reinstall or replace them from source instead

## Safe Edit Loop

1. Read the relevant feature spec, feature context document, and decision ledger before editing.
2. Inspect current feature and graph reality before changing code.
3. Edit the smallest source-of-truth files that satisfy the task.
4. Compile graph and inspect diagnostics.
5. Inspect impact, pipeline, and route surfaces when the change touches auth, routes, docs, or execution order.
6. Verify graph, context, and contract surfaces.
7. Refresh generated docs if source-of-truth changed.
8. Run PHPUnit.

## Guard Rails

- When a bug is encountered, create a test that fails because of that bug, then modify the non-test code so that the test passes while maintaining the intent of the original code.
- Never take a shortcut (such as forcing a test falsely return true) to get a test to pass.
- Keep test coverage above 90% for all new features and existing code.

## Recommended Command Loop

In a scaffolded app repo, bare `foundry` runs the first-run orientation for the current project. `foundry explain --json` without a target explains the first feature or route deterministically.
Explain, explain diff, and generate JSON payloads include deterministic confidence scores, bands, and evidence factors; prefer them when an agent is deciding whether to proceed or ask for clarification.
When Git is available, `foundry explain <target> --git --json` adds repository context for the explained target, and `foundry generate` returns Git safety metadata for the run.

```bash
foundry
foundry explain --json
foundry explain <target> --git --json
foundry explain --diff --json
foundry inspect graph --json
foundry inspect pipeline --json
foundry pack search <query> --json
foundry inspect feature <feature> --json
foundry inspect context <feature> --json
foundry inspect packs --json
foundry compile graph --json
foundry inspect impact --file=app/features/<feature>/feature.yaml --json
foundry doctor --feature=<feature> --json
foundry context doctor --feature=<feature> --json
foundry context check-alignment --feature=<feature> --json
foundry verify context --feature=<feature> --json
foundry history --kind=generate --json
foundry generate docs --format=markdown --json
foundry generate inspect-ui --json
foundry verify graph --json
foundry verify pipeline --json
foundry verify contracts --json
php vendor/bin/phpunit -c phpunit.xml.dist