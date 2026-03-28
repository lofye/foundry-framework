<?php

declare(strict_types=1);

namespace Foundry\Compiler\GraphSpec;

use Foundry\Compiler\BuildLayout;
use Foundry\Support\Json;
use Foundry\Support\Paths;

final class GraphIntegrityVerifier
{
    public function __construct(
        private readonly Paths $paths,
        private readonly BuildLayout $layout,
        private readonly ?GraphSpecification $spec = null,
    ) {}

    public function verify(): GraphIntegrityReport
    {
        $spec = $this->spec ?? CanonicalGraphSpecification::instance();

        $path = $this->layout->graphJsonPath();
        if (!is_file($path)) {
            return new GraphIntegrityReport(false, null, null, [[
                'severity' => 'error',
                'code' => 'FDY9120_GRAPH_ARTIFACT_MISSING',
                'section' => 'artifact',
                'message' => 'Canonical graph JSON artifact is missing.',
                'details' => ['path' => $this->relativePath($path)],
            ]]);
        }

        $json = file_get_contents($path);
        if ($json === false) {
            return new GraphIntegrityReport(false, null, null, [[
                'severity' => 'error',
                'code' => 'FDY9121_GRAPH_ARTIFACT_UNREADABLE',
                'section' => 'artifact',
                'message' => 'Canonical graph JSON artifact could not be read.',
                'details' => ['path' => $this->relativePath($path)],
            ]]);
        }

        try {
            /** @var array<string,mixed> $decoded */
            $decoded = Json::decodeAssoc($json);
        } catch (\Throwable $error) {
            return new GraphIntegrityReport(false, null, null, [[
                'severity' => 'error',
                'code' => 'FDY9122_GRAPH_ARTIFACT_INVALID_JSON',
                'section' => 'artifact',
                'message' => 'Canonical graph JSON artifact contains invalid JSON.',
                'details' => [
                    'path' => $this->relativePath($path),
                    'exception' => $error::class,
                ],
            ]]);
        }

        $migrator = new GraphArtifactMigrator($spec);
        $graph = $migrator->migrate($decoded);

        return $this->verifyGraphArray($graph, $spec);
    }

    /**
     * @param array<string,mixed> $graph
     */
    public function verifyGraphArray(array $graph, ?GraphSpecification $spec = null): GraphIntegrityReport
    {
        $spec ??= $this->spec ?? CanonicalGraphSpecification::instance();

        $issues = [];

        $graphVersion = (int) ($graph['graph_version'] ?? 0);
        if (!$spec->supportsGraphVersion($graphVersion)) {
            $issues[] = $this->issue('error', 'FDY9123_GRAPH_VERSION_UNSUPPORTED', 'compatibility', 'Graph version is not supported by the canonical graph specification.', [
                'graph_version' => $graphVersion,
                'supported_graph_versions' => $spec->supportedGraphVersions(),
            ]);
        }

        if ((int) ($graph['graph_spec_version'] ?? 0) !== $spec->specVersion()) {
            $issues[] = $this->issue('error', 'FDY9124_GRAPH_SPEC_VERSION_MISMATCH', 'compatibility', 'Graph spec version does not match the canonical graph specification.', [
                'graph_spec_version' => (int) ($graph['graph_spec_version'] ?? 0),
                'expected_graph_spec_version' => $spec->specVersion(),
            ]);
        }

        $nodeRows = array_values(array_filter((array) ($graph['nodes'] ?? []), 'is_array'));
        $edgeRows = array_values(array_filter((array) ($graph['edges'] ?? []), 'is_array'));

        $nodeIds = [];
        $nodeTypes = [];
        foreach ($nodeRows as $row) {
            $nodeId = (string) ($row['id'] ?? '');
            $nodeType = (string) ($row['type'] ?? '');
            $payload = is_array($row['payload'] ?? null) ? $row['payload'] : [];

            if ($nodeId === '') {
                $issues[] = $this->issue('error', 'FDY9125_NODE_ID_MISSING', 'node_integrity', 'Graph node is missing an id.', ['row' => $row]);
                continue;
            }

            if (isset($nodeIds[$nodeId])) {
                $issues[] = $this->issue('error', 'FDY9126_NODE_ID_DUPLICATE', 'node_integrity', 'Graph node id is duplicated.', ['node_id' => $nodeId]);
            }
            $nodeIds[$nodeId] = true;

            $definition = $spec->nodeType($nodeType);
            if ($definition === null) {
                $issues[] = $this->issue('error', 'FDY9127_NODE_TYPE_UNKNOWN', 'node_integrity', 'Graph node type is not recognized.', [
                    'node_id' => $nodeId,
                    'node_type' => $nodeType,
                ]);
                continue;
            }

            $nodeTypes[$nodeId] = $nodeType;

            foreach ($definition->requiredPayloadKeys as $key) {
                if (!array_key_exists($key, $payload)) {
                    $issues[] = $this->issue('error', 'FDY9128_NODE_PAYLOAD_KEY_MISSING', 'node_integrity', 'Graph node payload is missing a required key.', [
                        'node_id' => $nodeId,
                        'node_type' => $nodeType,
                        'required_key' => $key,
                    ]);
                }
            }

            foreach ($definition->payloadTypes as $key => $expectedType) {
                if (!array_key_exists($key, $payload)) {
                    continue;
                }

                if (!$this->valueMatchesType($payload[$key], $expectedType)) {
                    $issues[] = $this->issue('error', 'FDY9129_NODE_PAYLOAD_TYPE_INVALID', 'node_integrity', 'Graph node payload violates its declared type rule.', [
                        'node_id' => $nodeId,
                        'node_type' => $nodeType,
                        'payload_key' => $key,
                        'expected_type' => $expectedType,
                    ]);
                }
            }

            $compatibility = $spec->normalizeCompatibility((array) ($row['graph_compatibility'] ?? []));
            if (!in_array($graphVersion, $compatibility, true)) {
                $issues[] = $this->issue('error', 'FDY9130_NODE_GRAPH_COMPATIBILITY_INVALID', 'compatibility', 'Graph node compatibility does not include the active graph version.', [
                    'node_id' => $nodeId,
                    'node_type' => $nodeType,
                    'graph_version' => $graphVersion,
                    'graph_compatibility' => $compatibility,
                ]);
            }
        }

        $edgeIds = [];
        $edgeSourceCounts = [];
        $edgeTargetCounts = [];
        $incomingByType = [];
        $outgoingByType = [];

        foreach ($edgeRows as $row) {
            $edgeId = (string) ($row['id'] ?? '');
            $edgeType = (string) ($row['type'] ?? '');
            $from = (string) ($row['from'] ?? '');
            $to = (string) ($row['to'] ?? '');
            $payload = is_array($row['payload'] ?? null) ? $row['payload'] : [];

            if ($edgeId === '') {
                $issues[] = $this->issue('error', 'FDY9131_EDGE_ID_MISSING', 'edge_integrity', 'Graph edge is missing an id.', ['row' => $row]);
                continue;
            }

            if (isset($edgeIds[$edgeId])) {
                $issues[] = $this->issue('error', 'FDY9132_EDGE_ID_DUPLICATE', 'edge_integrity', 'Graph edge id is duplicated.', ['edge_id' => $edgeId]);
            }
            $edgeIds[$edgeId] = true;

            $definition = $spec->edgeType($edgeType);
            if ($definition === null) {
                $issues[] = $this->issue('error', 'FDY9133_EDGE_TYPE_UNKNOWN', 'edge_integrity', 'Graph edge type is not recognized.', [
                    'edge_id' => $edgeId,
                    'edge_type' => $edgeType,
                ]);
                continue;
            }

            if ($from === '' || !isset($nodeTypes[$from])) {
                $issues[] = $this->issue('error', 'FDY9134_EDGE_SOURCE_MISSING', 'edge_integrity', 'Graph edge source node does not exist.', [
                    'edge_id' => $edgeId,
                    'edge_type' => $edgeType,
                    'from' => $from,
                ]);
            }

            if ($to === '' || !isset($nodeTypes[$to])) {
                $issues[] = $this->issue('error', 'FDY9135_EDGE_TARGET_MISSING', 'edge_integrity', 'Graph edge target node does not exist.', [
                    'edge_id' => $edgeId,
                    'edge_type' => $edgeType,
                    'to' => $to,
                ]);
            }

            if (isset($nodeTypes[$from]) && !$definition->allowsSourceType($nodeTypes[$from])) {
                $issues[] = $this->issue('error', 'FDY9136_EDGE_SOURCE_TYPE_ILLEGAL', 'edge_integrity', 'Graph edge source type is not legal for this edge type.', [
                    'edge_id' => $edgeId,
                    'edge_type' => $edgeType,
                    'source_type' => $nodeTypes[$from],
                ]);
            }

            if (isset($nodeTypes[$to]) && !$definition->allowsTargetType($nodeTypes[$to])) {
                $issues[] = $this->issue('error', 'FDY9137_EDGE_TARGET_TYPE_ILLEGAL', 'edge_integrity', 'Graph edge target type is not legal for this edge type.', [
                    'edge_id' => $edgeId,
                    'edge_type' => $edgeType,
                    'target_type' => $nodeTypes[$to],
                ]);
            }

            if (!$definition->payloadAllowed && $payload !== []) {
                $issues[] = $this->issue('warning', 'FDY9138_EDGE_PAYLOAD_UNEXPECTED', 'edge_integrity', 'Graph edge carries payload even though the canonical edge type does not declare one.', [
                    'edge_id' => $edgeId,
                    'edge_type' => $edgeType,
                ]);
            }

            foreach ($definition->requiredPayloadKeys as $key) {
                if (!array_key_exists($key, $payload)) {
                    $issues[] = $this->issue('error', 'FDY9139_EDGE_PAYLOAD_KEY_MISSING', 'edge_integrity', 'Graph edge payload is missing a required key.', [
                        'edge_id' => $edgeId,
                        'edge_type' => $edgeType,
                        'required_key' => $key,
                    ]);
                }
            }

            foreach ($definition->payloadTypes as $key => $expectedType) {
                if (!array_key_exists($key, $payload)) {
                    continue;
                }

                if (!$this->valueMatchesType($payload[$key], $expectedType)) {
                    $issues[] = $this->issue('error', 'FDY9140_EDGE_PAYLOAD_TYPE_INVALID', 'edge_integrity', 'Graph edge payload violates its declared type rule.', [
                        'edge_id' => $edgeId,
                        'edge_type' => $edgeType,
                        'payload_key' => $key,
                        'expected_type' => $expectedType,
                    ]);
                }
            }

            $edgeSourceCounts[$edgeType][$from] = ($edgeSourceCounts[$edgeType][$from] ?? 0) + 1;
            $edgeTargetCounts[$edgeType][$to] = ($edgeTargetCounts[$edgeType][$to] ?? 0) + 1;
            $incomingByType[$to][$edgeType][] = $row;
            $outgoingByType[$from][$edgeType][] = $row;

            if ($definition->hasRole('execution')) {
                $targetDefinition = $spec->nodeType($nodeTypes[$to] ?? '');
                if ($targetDefinition !== null && $targetDefinition->semanticCategory === 'observational') {
                    $issues[] = $this->issue('error', 'FDY9141_EXECUTION_TARGET_IMPOSSIBLE', 'structural_integrity', 'Execution edge points into an observational node category.', [
                        'edge_id' => $edgeId,
                        'edge_type' => $edgeType,
                        'target_node_id' => $to,
                        'target_node_type' => $nodeTypes[$to] ?? null,
                    ]);
                }
            }
        }

        foreach ($spec->edgeTypes() as $edgeType => $definition) {
            $sourceCounts = $edgeSourceCounts[$edgeType] ?? [];
            $targetCounts = $edgeTargetCounts[$edgeType] ?? [];

            if ($definition->multiplicity === 'one_to_one' || $definition->multiplicity === 'one_to_many') {
                foreach ($targetCounts as $targetId => $count) {
                    if ($count > 1) {
                        $issues[] = $this->issue('error', 'FDY9142_EDGE_MULTIPLICITY_TARGET_VIOLATION', 'edge_integrity', 'Graph edge multiplicity is violated at the target side.', [
                            'edge_type' => $edgeType,
                            'target_node_id' => $targetId,
                            'count' => $count,
                            'multiplicity' => $definition->multiplicity,
                        ]);
                    }
                }
            }

            if ($definition->multiplicity === 'one_to_one') {
                foreach ($sourceCounts as $sourceId => $count) {
                    if ($count > 1) {
                        $issues[] = $this->issue('error', 'FDY9143_EDGE_MULTIPLICITY_SOURCE_VIOLATION', 'edge_integrity', 'Graph edge multiplicity is violated at the source side.', [
                            'edge_type' => $edgeType,
                            'source_node_id' => $sourceId,
                            'count' => $count,
                            'multiplicity' => $definition->multiplicity,
                        ]);
                    }
                }
            }
        }

        foreach ($nodeTypes as $nodeId => $nodeType) {
            if ($nodeType === 'execution_plan') {
                $incoming = array_merge(
                    $incomingByType[$nodeId]['feature_to_execution_plan'] ?? [],
                    $incomingByType[$nodeId]['route_to_execution_plan'] ?? [],
                );
                if ($incoming === []) {
                    $issues[] = $this->issue('error', 'FDY9144_EXECUTION_PLAN_ORPHAN', 'structural_integrity', 'Execution plan node is orphaned from feature and route ownership.', [
                        'node_id' => $nodeId,
                    ]);
                }
            }

            if ($nodeType === 'guard') {
                $incoming = array_merge(
                    $incomingByType[$nodeId]['feature_to_guard'] ?? [],
                    $incomingByType[$nodeId]['execution_plan_to_guard'] ?? [],
                );
                $outgoing = $outgoingByType[$nodeId]['guard_to_pipeline_stage'] ?? [];
                if ($incoming === [] || $outgoing === []) {
                    $issues[] = $this->issue('error', 'FDY9145_GUARD_ORPHAN', 'structural_integrity', 'Guard node is not fully connected into execution topology.', [
                        'node_id' => $nodeId,
                    ]);
                }
            }

            if ($nodeType === 'interceptor') {
                $incoming = $incomingByType[$nodeId]['execution_plan_to_interceptor'] ?? [];
                $outgoing = $outgoingByType[$nodeId]['interceptor_to_pipeline_stage'] ?? [];
                if ($outgoing === []) {
                    $issues[] = $this->issue('error', 'FDY9146_INTERCEPTOR_ORPHAN', 'structural_integrity', 'Interceptor node is missing its pipeline-stage attachment.', [
                        'node_id' => $nodeId,
                    ]);
                } elseif ($incoming === []) {
                    $issues[] = $this->issue('warning', 'FDY9147_INTERCEPTOR_UNUSED', 'structural_integrity', 'Interceptor node is attached to a stage but no execution plan currently references it.', [
                        'node_id' => $nodeId,
                    ]);
                }
            }

            if ($nodeType === 'route') {
                $owners = $incomingByType[$nodeId]['feature_to_route'] ?? [];
                if ($owners === []) {
                    $issues[] = $this->issue('error', 'FDY9148_ROUTE_OWNER_MISSING', 'structural_integrity', 'Route node has no owning feature edge.', [
                        'node_id' => $nodeId,
                    ]);
                }

                if (count($owners) > 1) {
                    $diagnosticIds = [];
                    foreach ($nodeRows as $row) {
                        if ((string) ($row['id'] ?? '') !== $nodeId) {
                            continue;
                        }
                        $diagnosticIds = array_values(array_map('strval', (array) ($row['diagnostic_ids'] ?? [])));
                        break;
                    }

                    if ($diagnosticIds === []) {
                        $issues[] = $this->issue('error', 'FDY9149_ROUTE_OWNERSHIP_CONFLICT_UNDIAGNOSED', 'structural_integrity', 'Route ownership conflict exists without any attached diagnostic ids.', [
                            'node_id' => $nodeId,
                            'owner_count' => count($owners),
                        ]);
                    }
                }
            }
        }

        $errors = array_values(array_filter($issues, static fn(array $issue): bool => (string) ($issue['severity'] ?? 'error') === 'error'));

        return new GraphIntegrityReport(
            ok: $errors === [],
            graphVersion: $graphVersion,
            graphSpecVersion: (int) ($graph['graph_spec_version'] ?? 0),
            issues: $issues,
        );
    }

    /**
     * @param array<string,mixed> $details
     * @return array<string,mixed>
     */
    private function issue(string $severity, string $code, string $section, string $message, array $details = []): array
    {
        return [
            'severity' => $severity,
            'code' => $code,
            'section' => $section,
            'message' => $message,
            'details' => $details,
        ];
    }

    private function valueMatchesType(mixed $value, string $expectedType): bool
    {
        return match ($expectedType) {
            'string' => is_string($value),
            'int' => is_int($value),
            'bool' => is_bool($value),
            'array' => is_array($value),
            'map' => is_array($value),
            default => true,
        };
    }

    private function relativePath(string $absolutePath): string
    {
        $root = rtrim($this->paths->root(), '/') . '/';

        return str_starts_with($absolutePath, $root)
            ? substr($absolutePath, strlen($root))
            : $absolutePath;
    }
}
