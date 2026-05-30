# Deep Tests Example

After copying the example definitions into a Foundry app, generate deep tests for a feature or resource:

```bash
foundry generate tests api-create-post --mode=deep --json
foundry generate tests posts --mode=resource --json
foundry generate tests --all-missing --mode=deep --json
```

Deep mode uses graph context (auth, schemas, events, jobs, route shape) to select scenario templates.
