<?php

declare(strict_types=1);

namespace Foundry\Compiler\GraphSpec;

use Foundry\Support\FoundryError;

final class GraphArtifactMigrator
{
    public function __construct(
        private readonly ?GraphSpecification $spec = null,
    ) {}

    /**
     * @param array<string,mixed> $row
     * @return array<string,mixed>
     */
    public function migrate(array $row): array
    {
        $spec = $this->spec ?? CanonicalGraphSpecification::instance();

        $graphVersion = (int) ($row['graph_version'] ?? 1);

        return match ($graphVersion) {
            $spec->currentGraphVersion() => $this->normalizeV2($row, $spec),
            1 => $this->migrateV1($row, $spec),
            default => throw new FoundryError(
                errorCode: 'GRAPH_VERSION_UNSUPPORTED',
                category: 'graph',
                details: [
                    'graph_version' => $graphVersion,
                    'supported_graph_versions' => $spec->supportedGraphVersions(),
                ],
                message: sprintf('Graph artifact version %d is not supported by this Foundry build.', $graphVersion),
            ),
        };
    }

    /**
     * @param array<string,mixed> $row
     * @return array<string,mixed>
     */
    private function migrateV1(array $row, GraphSpecification $spec): array
    {
        $migrated = $this->normalizeV2($row, $spec);
        $migrated['graph_version'] = $spec->currentGraphVersion();
        $migrated['graph_metadata']['graph_version'] = $spec->currentGraphVersion();
        $migrated['compatibility']['loaded_from_graph_version'] = 1;
        $migrated['compatibility']['migrated_to_graph_version'] = $spec->currentGraphVersion();
        $migrated['compatibility']['migration_strategy'] = 'deterministic_upgrade';

        return $migrated;
    }

    /**
     * @param array<string,mixed> $row
     * @return array<string,mixed>
     */
    private function normalizeV2(array $row, GraphSpecification $spec): array
    {
        $frameworkVersion = (string) ($row['framework_version'] ?? (($row['graph_metadata']['framework_version'] ?? 'unknown')));
        $compiledAt = (string) ($row['compiled_at'] ?? (($row['graph_metadata']['compiled_at'] ?? gmdate(DATE_ATOM))));
        $sourceHash = (string) ($row['source_hash'] ?? (($row['graph_metadata']['source_hash'] ?? '')));
        $graphVersion = (int) ($row['graph_version'] ?? $spec->currentGraphVersion());
        $metadata = is_array($row['metadata'] ?? null) ? $row['metadata'] : [];

        $nodes = [];
        foreach ((array) ($row['nodes'] ?? []) as $nodeRow) {
            if (!is_array($nodeRow)) {
                continue;
            }

            $compatibility = $spec->normalizeCompatibility((array) ($nodeRow['graph_compatibility'] ?? []));
            $nodeRow['graph_compatibility'] = $compatibility;
            $nodeRow['payload'] = is_array($nodeRow['payload'] ?? null) ? $nodeRow['payload'] : [];
            $nodeRow['diagnostic_ids'] = array_values(array_map('strval', (array) ($nodeRow['diagnostic_ids'] ?? [])));
            $nodes[] = $nodeRow;
        }

        $edges = [];
        foreach ((array) ($row['edges'] ?? []) as $edgeRow) {
            if (!is_array($edgeRow)) {
                continue;
            }

            $edgeRow['payload'] = is_array($edgeRow['payload'] ?? null) ? $edgeRow['payload'] : [];
            $edges[] = $edgeRow;
        }

        return [
            'graph_version' => $graphVersion,
            'graph_spec_version' => (int) ($row['graph_spec_version'] ?? $spec->specVersion()),
            'framework_version' => $frameworkVersion,
            'compiled_at' => $compiledAt,
            'source_hash' => $sourceHash,
            'metadata' => $metadata,
            'graph_metadata' => [
                'graph_version' => $graphVersion,
                'framework_version' => $frameworkVersion,
                'compiled_at' => $compiledAt,
                'source_hash' => $sourceHash,
                'metadata' => $metadata,
            ],
            'summary' => is_array($row['summary'] ?? null) ? $row['summary'] : [],
            'nodes' => $nodes,
            'edges' => $edges,
            'integrity' => is_array($row['integrity'] ?? null) ? $row['integrity'] : [
                'status' => 'unverified',
                'fingerprints' => [],
            ],
            'compatibility' => is_array($row['compatibility'] ?? null) ? $row['compatibility'] : [
                'current_graph_version' => $spec->currentGraphVersion(),
                'supported_graph_versions' => $spec->supportedGraphVersions(),
            ],
            'observability' => is_array($row['observability'] ?? null) ? $row['observability'] : [
                'node_correlation_field' => 'id',
                'edge_correlation_field' => 'id',
                'traceable_node_types' => $spec->traceableNodeTypes(),
                'profileable_node_types' => $spec->profileableNodeTypes(),
                'build_association' => [
                    'compiled_at' => $compiledAt,
                    'source_hash' => $sourceHash,
                ],
                'run_association' => [
                    'build_id' => $sourceHash !== '' ? substr(hash('sha256', $sourceHash . ':' . $compiledAt), 0, 16) : null,
                ],
            ],
        ];
    }
}
