# Framework Examples

Foundry examples are split into three groups:

- canonical app examples you can study or copy into `app/*`
- reference packs that help you plan a richer app or extension
- framework surface examples for compiler, CLI, and tooling behavior

The canonical app examples are intentionally small and keep only authored source files. They are not standalone Composer projects, and they do not commit `app/generated/*`.

## Canonical App Examples

- [Hello World](hello-world/README.md): the smallest readable Foundry app, showing one feature folder, schemas, context manifests, and the inspect/doctor/verify loop.
- [Blog API](blog-api/README.md): the canonical HTTP slice with one collection read, one item read, and one protected write.
- [Workflow And Events](workflow-events/README.md): a compact editorial flow showing workflows, event edges, route params, and job dispatch.

## Reference Packs

- [Extensions And Migrations](extensions-migrations/README.md): extension registration, pack metadata, migrations, and codemod examples.
- [Reference Blog](reference-blog/README.md): a richer build kit with exact commands, an LLM prompt, and starter content for a blog with admin login and RSS.

## Framework Surface Examples

- [Compiler Core](compiler-core/README.md)
- [Architecture Tools](architecture-tools/README.md)
- [Execution Pipeline](execution-pipeline/README.md)
- [App Scaffolding](app-scaffolding/README.md)
- [Integration Tooling](integration-tooling/README.md)

## Thresholds

Thresholds is the richer real-app reference. The examples in this directory stay smaller and more explicit so they teach one architecture idea at a time without hiding the underlying Foundry model.
