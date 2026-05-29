# Feature: cli-experience

## Purpose
- Improve the usability, discoverability, and ergonomics of the Foundry CLI.

## Decision Summary

Refreshed Through Spec: `004-framework-root-foundry-launcher`

- CLI ergonomics are treated as a first-class framework module while preserving stable command contracts.
- Completion support is deterministic, registry-backed, and currently focused on bash, zsh, and active execution-spec workflows.
- Draft execution specs remain excluded from default implementation completion so shell assistance does not blur promotion boundaries.
- Common multi-step CLI execution loops are available as first-class deterministic batch workflows rather than ad hoc shell macros.

## Current State
- Foundry provides a reliable command surface for human and agent workflows.
- Bash and zsh consume generated completion scripts for command discovery.
- Static completion covers top-level commands and known subcommands from the registered CLI surface.
- Dynamic completion exposes feature names and active execution-spec ids where appropriate, excluding drafts by default.
- CLI help, registry metadata, and surface verification remain aligned.
- CLI surface verification remains green after recent usability changes.
- Unsupported or invalid completion requests fail clearly.
- Common grouped workflows are available through deterministic batch commands and options for readiness, context bootstrap/recovery, architecture verification, feature-work verification, completion verification, and feature-focused test orchestration.
- Batch workflow outputs include structured aggregate status with per-step results and explicit failure location.
- Batch workflow commands are discoverable, registry-backed, and covered by command-surface tests and verification probes.
- Documentation explains how to generate and use bash and zsh completion and that active execution specs are completed by default.
- Framework-repository command guidance now standardizes on a project-local `./foundry ...` launcher, while generated apps continue to use `foundry ...`.
- Framework command-prefix output surfaces `./foundry` when a repository-root launcher exists and preserves deterministic fallback behavior when it does not.

## Open Questions
- Should completion support remain shell-script based only, or should a more general completion abstraction exist later?
- When should additional shells beyond bash and zsh be supported?
- Which command families benefit most from dynamic completion beyond `implement spec`?
- Which additional batch workflows should be promoted to stable versus kept experimental as command surface grows?

## Next Steps
- Collect usage feedback on the new batch workflows and identify any missing high-frequency command groups.
- Evaluate whether additional command families need dynamic completion beyond `implement spec`.
- Decide when additional shells beyond bash and zsh should be supported.
