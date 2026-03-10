# Contributor Vocabulary

Foundry was built through internal iteration milestones, but those labels are not part of the public framework model.

Use public names that describe **features and architecture**, not historical implementation batches.

Preferred naming style:
- `GenerateScaffoldCommand`
- `InspectPlatformCommand`
- `VerifyIntegrationCommand`
- `PlatformDefinitionPass`

Avoid public names based on internal milestone labels:
- timeline-coded command names
- timeline-coded pass names
- timeline-coded test names
- docs or examples named with implementation-history labels

Rules for contributors:
1. Public command/class/doc names should answer “what is this?”.
2. Internal compatibility aliases are allowed when needed, but should be clearly deprecated and not the primary surfaced name.
3. New docs and examples should use architecture labels such as compiler, graph, pipeline, extensions, integrations, platform capabilities.
