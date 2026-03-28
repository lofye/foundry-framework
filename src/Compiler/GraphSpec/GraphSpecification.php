<?php

declare(strict_types=1);

namespace Foundry\Compiler\GraphSpec;

use Foundry\Compiler\IR\GraphNode;

final class GraphSpecification
{
    /**
     * @param array<string,NodeTypeDefinition> $nodeTypes
     * @param array<string,EdgeTypeDefinition> $edgeTypes
     * @param array<int,string> $invariants
     * @param array<int,int> $supportedGraphVersions
     * @param array<int,array<string,mixed>> $migrationRules
     */
    public function __construct(
        private readonly int $specVersion,
        private readonly int $currentGraphVersion,
        private readonly array $supportedGraphVersions,
        private readonly array $nodeTypes,
        private readonly array $edgeTypes,
        private readonly array $invariants,
        private readonly array $migrationRules,
    ) {}

    public function specVersion(): int
    {
        return $this->specVersion;
    }

    public function currentGraphVersion(): int
    {
        return $this->currentGraphVersion;
    }

    /**
     * @return array<int,int>
     */
    public function supportedGraphVersions(): array
    {
        return $this->supportedGraphVersions;
    }

    public function supportsGraphVersion(int $graphVersion): bool
    {
        return in_array($graphVersion, $this->supportedGraphVersions, true);
    }

    /**
     * @return array<string,NodeTypeDefinition>
     */
    public function nodeTypes(): array
    {
        return $this->nodeTypes;
    }

    public function nodeType(string $type): ?NodeTypeDefinition
    {
        return $this->nodeTypes[$type] ?? null;
    }

    /**
     * @return array<string,EdgeTypeDefinition>
     */
    public function edgeTypes(): array
    {
        return $this->edgeTypes;
    }

    public function edgeType(string $type): ?EdgeTypeDefinition
    {
        return $this->edgeTypes[$type] ?? null;
    }

    /**
     * @return array<int,string>
     */
    public function invariants(): array
    {
        return $this->invariants;
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function migrationRules(): array
    {
        return $this->migrationRules;
    }

    /**
     * @param array<string,mixed> $payload
     * @param array{line_start:int|null,line_end:int|null}|null $sourceRegion
     * @param array<int,int> $graphCompatibility
     */
    public function instantiateNode(
        string $type,
        string $id,
        string $sourcePath,
        array $payload,
        ?array $sourceRegion,
        array $graphCompatibility,
    ): GraphNode {
        $definition = $this->nodeType($type);
        if ($definition === null) {
            throw new UnknownGraphNodeType($type, $id);
        }

        $compatibility = GraphCompatibility::normalizeVersions($graphCompatibility, $this->currentGraphVersion);
        $className = $definition->className;

        return new $className($id, $sourcePath, $payload, $sourceRegion, $compatibility);
    }

    /**
     * @param array<int,int> $versions
     * @return array<int,int>
     */
    public function normalizeCompatibility(array $versions): array
    {
        return GraphCompatibility::normalizeVersions($versions, $this->currentGraphVersion);
    }

    /**
     * @return array<int,string>
     */
    public function traceableNodeTypes(): array
    {
        $types = [];
        foreach ($this->nodeTypes as $type => $definition) {
            if ($definition->traceable) {
                $types[] = $type;
            }
        }

        sort($types);

        return $types;
    }

    /**
     * @return array<int,string>
     */
    public function profileableNodeTypes(): array
    {
        $types = [];
        foreach ($this->nodeTypes as $type => $definition) {
            if ($definition->profileable) {
                $types[] = $type;
            }
        }

        sort($types);

        return $types;
    }

    /**
     * @return array<string,mixed>
     */
    public function artifactSchema(): array
    {
        return [
            '$schema' => 'https://json-schema.org/draft/2020-12/schema',
            'title' => 'FoundryCanonicalGraphArtifact',
            'type' => 'object',
            'required' => [
                'graph_version',
                'graph_spec_version',
                'graph_metadata',
                'summary',
                'nodes',
                'edges',
            ],
            'properties' => [
                'graph_version' => ['type' => 'integer', 'minimum' => 1],
                'graph_spec_version' => ['type' => 'integer', 'minimum' => 1],
                'framework_version' => ['type' => 'string'],
                'compiled_at' => ['type' => 'string'],
                'source_hash' => ['type' => 'string'],
                'metadata' => ['type' => 'object'],
                'graph_metadata' => [
                    'type' => 'object',
                    'required' => ['graph_version', 'framework_version', 'compiled_at', 'source_hash'],
                ],
                'summary' => ['type' => 'object'],
                'integrity' => ['type' => 'object'],
                'compatibility' => ['type' => 'object'],
                'observability' => ['type' => 'object'],
                'nodes' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'required' => ['id', 'type', 'source_path', 'payload', 'graph_compatibility'],
                    ],
                ],
                'edges' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'required' => ['id', 'type', 'from', 'to', 'payload'],
                    ],
                ],
            ],
        ];
    }

    /**
     * @return array<string,mixed>
     */
    public function toArray(): array
    {
        $nodeRows = [];
        foreach ($this->nodeTypes as $type => $definition) {
            $nodeRows[$type] = $definition->toArray();
        }
        ksort($nodeRows);

        $edgeRows = [];
        foreach ($this->edgeTypes as $type => $definition) {
            $edgeRows[$type] = $definition->toArray();
        }
        ksort($edgeRows);

        return [
            'spec_version' => $this->specVersion,
            'graph_version' => $this->currentGraphVersion,
            'supported_graph_versions' => $this->supportedGraphVersions,
            'invariants' => $this->invariants,
            'migration_rules' => $this->migrationRules,
            'node_types' => array_values($nodeRows),
            'edge_types' => array_values($edgeRows),
            'artifact_schema' => $this->artifactSchema(),
            'observability' => [
                'node_correlation_field' => 'id',
                'edge_correlation_field' => 'id',
                'traceable_node_types' => $this->traceableNodeTypes(),
                'profileable_node_types' => $this->profileableNodeTypes(),
            ],
        ];
    }
}
