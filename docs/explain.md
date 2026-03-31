# Explain

`foundry explain` is the canonical architecture introspection surface for Foundry.

Core flows:

```bash
foundry explain
foundry explain publish_post --json
foundry explain pack:foundry/blog --json
foundry explain --diff
foundry explain --diff --json
```

Notes:

- when no target is provided, Foundry explains the first feature or route deterministically
- `--json` returns the canonical explain contract used by other tooling
- `--diff` reads the latest explain-derived architectural diff from `.foundry/diffs/last.json`
- the diff compares `.foundry/snapshots/pre-generate.json` and `.foundry/snapshots/post-generate.json`
- the diff is architectural, not file-based

Iteration loop:

```bash
foundry generate "add feature" --mode=new
foundry explain --diff
foundry explain
```
