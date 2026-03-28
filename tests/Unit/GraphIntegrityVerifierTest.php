<?php

declare(strict_types=1);

namespace Foundry\Tests\Unit;

use Foundry\Compiler\BuildLayout;
use Foundry\Compiler\CompileOptions;
use Foundry\Compiler\Diagnostics\DiagnosticBag;
use Foundry\Compiler\GraphCompiler;
use Foundry\Compiler\GraphSpec\EdgeTypeDefinition;
use Foundry\Compiler\GraphSpec\GraphIntegrityReport;
use Foundry\Compiler\GraphSpec\GraphIntegrityVerifier;
use Foundry\Compiler\GraphSpec\GraphSpecification;
use Foundry\Compiler\GraphSpec\NodeTypeDefinition;
use Foundry\Compiler\IR\ContextManifestNode;
use Foundry\Compiler\IR\FeatureNode;
use Foundry\Support\Paths;
use Foundry\Tests\Fixtures\TempProject;
use PHPUnit\Framework\TestCase;

final class GraphIntegrityVerifierTest extends TestCase
{
    private TempProject $project;

    private Paths $paths;

    private BuildLayout $layout;

    /**
     * @var array<string,mixed>
     */
    private array $graph;

    protected function setUp(): void
    {
        $this->project = new TempProject();
        $this->seedFeature();

        $this->paths = Paths::fromCwd($this->project->root);
        $compiler = new GraphCompiler($this->paths);
        $compileResult = $compiler->compile(new CompileOptions());

        $this->layout = $compiler->buildLayout();
        $this->graph = $compileResult->graph->toArray(new DiagnosticBag());
    }

    protected function tearDown(): void
    {
        $this->project->cleanup();
    }

    public function test_verify_reports_missing_invalid_and_valid_graph_artifacts(): void
    {
        $path = $this->layout->graphJsonPath();
        @unlink($path);

        $verifier = new GraphIntegrityVerifier($this->paths, $this->layout);

        $missing = $verifier->verify();
        $this->assertFalse($missing->ok);
        $this->assertNull($missing->graphVersion);
        $this->assertNull($missing->graphSpecVersion);
        $this->assertSame(['FDY9120_GRAPH_ARTIFACT_MISSING'], $this->codes($missing));
        $this->assertSame('app/.foundry/build/graph/app_graph.json', $missing->issues[0]['details']['path']);

        file_put_contents($path, "{\n");

        $invalid = $verifier->verify();
        $this->assertFalse($invalid->ok);
        $this->assertSame(['FDY9122_GRAPH_ARTIFACT_INVALID_JSON'], $this->codes($invalid));

        file_put_contents($path, json_encode($this->graph, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));

        $valid = $verifier->verify();
        $this->assertTrue($valid->ok);
        $this->assertSame(2, $valid->graphVersion);
        $this->assertSame(1, $valid->graphSpecVersion);
        $this->assertSame([], $valid->issues);
    }

    public function test_verify_graph_array_reports_version_node_edge_and_multiplicity_issues(): void
    {
        $graph = $this->graph;
        $graph['graph_version'] = 99;
        $graph['graph_spec_version'] = 999;

        $featureId = $this->firstNodeIdByType($graph, 'feature');
        $routeId = $this->firstNodeIdByType($graph, 'route');
        $schemaId = $this->firstNodeIdByType($graph, 'schema');
        $authId = $this->firstNodeIdByType($graph, 'auth');
        $routeIndex = $this->firstNodeIndexByType($graph, 'route');
        $featureIndex = $this->firstNodeIndexByType($graph, 'feature');
        $authIndex = $this->firstNodeIndexByType($graph, 'auth');

        unset($graph['nodes'][$routeIndex]['payload']['method']);
        $graph['nodes'][$routeIndex]['payload']['features'] = 'invalid';

        $graph['nodes'][] = [
            'type' => 'feature',
            'source_path' => 'app/features/missing-id/feature.yaml',
            'payload' => ['feature' => 'missing_id', 'kind' => 'http'],
            'graph_compatibility' => [2],
        ];
        $graph['nodes'][] = $graph['nodes'][$featureIndex];
        $graph['nodes'][] = [
            'id' => 'unknown:publish_post',
            'type' => 'unknown_type',
            'source_path' => 'app/features/publish_post/feature.yaml',
            'payload' => ['feature' => 'publish_post'],
            'graph_compatibility' => [2],
        ];

        $graph['nodes'][] = [
            ...$graph['nodes'][$featureIndex],
            'id' => 'feature:secondary_publish',
            'payload' => ['feature' => 'secondary_publish', 'kind' => 'http'],
        ];
        $graph['nodes'][] = [
            ...$graph['nodes'][$authIndex],
            'id' => 'auth:secondary_publish',
            'payload' => ['feature' => 'secondary_publish'],
        ];

        $firstEdge = $graph['edges'][0];
        $graph['edges'][] = [
            'type' => 'feature_to_route',
            'from' => $featureId,
            'to' => $routeId,
            'payload' => [],
        ];
        $graph['edges'][] = $firstEdge;
        $graph['edges'][] = [
            'id' => 'edge:unknown',
            'type' => 'unknown_edge',
            'from' => $featureId,
            'to' => $routeId,
            'payload' => [],
        ];
        $graph['edges'][] = [
            'id' => 'edge:missing-source',
            'type' => 'feature_to_route',
            'from' => 'feature:missing',
            'to' => $routeId,
            'payload' => [],
        ];
        $graph['edges'][] = [
            'id' => 'edge:missing-target',
            'type' => 'feature_to_route',
            'from' => $featureId,
            'to' => 'route:missing',
            'payload' => [],
        ];
        $graph['edges'][] = [
            'id' => 'edge:illegal-types',
            'type' => 'feature_to_route',
            'from' => $routeId,
            'to' => $authId,
            'payload' => [],
        ];
        $graph['edges'][] = [
            'id' => 'edge:unexpected-payload',
            'type' => 'feature_to_route',
            'from' => $featureId,
            'to' => $routeId,
            'payload' => ['unexpected' => true],
        ];
        $graph['edges'][] = [
            'id' => 'edge:target-multiplicity',
            'type' => 'feature_to_input_schema',
            'from' => 'feature:secondary_publish',
            'to' => $schemaId,
            'payload' => [],
        ];
        $graph['edges'][] = [
            'id' => 'edge:source-multiplicity',
            'type' => 'feature_to_auth_config',
            'from' => $featureId,
            'to' => 'auth:secondary_publish',
            'payload' => [],
        ];

        $report = (new GraphIntegrityVerifier($this->paths, $this->layout))->verifyGraphArray($graph);
        $codes = $this->codes($report);

        $this->assertFalse($report->ok);
        $this->assertSame(99, $report->graphVersion);
        $this->assertSame(999, $report->graphSpecVersion);
        $this->assertContains('FDY9123_GRAPH_VERSION_UNSUPPORTED', $codes);
        $this->assertContains('FDY9124_GRAPH_SPEC_VERSION_MISMATCH', $codes);
        $this->assertContains('FDY9125_NODE_ID_MISSING', $codes);
        $this->assertContains('FDY9126_NODE_ID_DUPLICATE', $codes);
        $this->assertContains('FDY9127_NODE_TYPE_UNKNOWN', $codes);
        $this->assertContains('FDY9128_NODE_PAYLOAD_KEY_MISSING', $codes);
        $this->assertContains('FDY9129_NODE_PAYLOAD_TYPE_INVALID', $codes);
        $this->assertContains('FDY9130_NODE_GRAPH_COMPATIBILITY_INVALID', $codes);
        $this->assertContains('FDY9131_EDGE_ID_MISSING', $codes);
        $this->assertContains('FDY9132_EDGE_ID_DUPLICATE', $codes);
        $this->assertContains('FDY9133_EDGE_TYPE_UNKNOWN', $codes);
        $this->assertContains('FDY9134_EDGE_SOURCE_MISSING', $codes);
        $this->assertContains('FDY9135_EDGE_TARGET_MISSING', $codes);
        $this->assertContains('FDY9136_EDGE_SOURCE_TYPE_ILLEGAL', $codes);
        $this->assertContains('FDY9137_EDGE_TARGET_TYPE_ILLEGAL', $codes);
        $this->assertContains('FDY9138_EDGE_PAYLOAD_UNEXPECTED', $codes);
        $this->assertContains('FDY9142_EDGE_MULTIPLICITY_TARGET_VIOLATION', $codes);
        $this->assertContains('FDY9143_EDGE_MULTIPLICITY_SOURCE_VIOLATION', $codes);
    }

    public function test_verify_graph_array_reports_structural_issues_for_execution_and_route_topology(): void
    {
        $graph = $this->graph;

        $executionPlanId = $this->firstNodeIdByType($graph, 'execution_plan');
        $routeId = $this->firstNodeIdByType($graph, 'route');
        $featureIndex = $this->firstNodeIndexByType($graph, 'feature');
        $routeIndex = $this->firstNodeIndexByType($graph, 'route');
        $guardId = $this->allNodeIdsByType($graph, 'guard')[0];
        $interceptorIds = $this->allNodeIdsByType($graph, 'interceptor');

        $graph['nodes'][$routeIndex]['diagnostic_ids'] = [];
        $graph['nodes'][] = [
            ...$graph['nodes'][$featureIndex],
            'id' => 'feature:secondary_publish',
            'payload' => ['feature' => 'secondary_publish', 'kind' => 'http'],
        ];
        $graph['nodes'][] = [
            ...$graph['nodes'][$routeIndex],
            'id' => 'route:GET /orphan',
            'payload' => [
                'method' => 'GET',
                'path' => '/orphan',
                'signature' => 'GET /orphan',
                'features' => ['orphan'],
            ],
            'diagnostic_ids' => [],
        ];

        $graph['edges'] = array_values(array_filter(
            $graph['edges'],
            static function (array $edge) use ($executionPlanId, $guardId, $interceptorIds, $routeId): bool {
                return !(
                    (in_array((string) ($edge['type'] ?? ''), ['feature_to_execution_plan', 'route_to_execution_plan'], true)
                        && (string) ($edge['to'] ?? '') === $executionPlanId)
                    || ((string) ($edge['type'] ?? '') === 'guard_to_pipeline_stage'
                        && (string) ($edge['from'] ?? '') === $guardId)
                    || ((string) ($edge['type'] ?? '') === 'interceptor_to_pipeline_stage'
                        && (string) ($edge['from'] ?? '') === $interceptorIds[0])
                    || ((string) ($edge['type'] ?? '') === 'execution_plan_to_interceptor'
                        && (string) ($edge['to'] ?? '') === $interceptorIds[1])
                );
            },
        ));

        $graph['edges'][] = [
            'id' => 'edge:secondary-route-owner',
            'type' => 'feature_to_route',
            'from' => 'feature:secondary_publish',
            'to' => $routeId,
            'payload' => [],
        ];

        $report = (new GraphIntegrityVerifier($this->paths, $this->layout))->verifyGraphArray($graph);
        $codes = $this->codes($report);

        $this->assertFalse($report->ok);
        $this->assertContains('FDY9144_EXECUTION_PLAN_ORPHAN', $codes);
        $this->assertContains('FDY9145_GUARD_ORPHAN', $codes);
        $this->assertContains('FDY9146_INTERCEPTOR_ORPHAN', $codes);
        $this->assertContains('FDY9147_INTERCEPTOR_UNUSED', $codes);
        $this->assertContains('FDY9148_ROUTE_OWNER_MISSING', $codes);
        $this->assertContains('FDY9149_ROUTE_OWNERSHIP_CONFLICT_UNDIAGNOSED', $codes);
    }

    public function test_verify_graph_array_honors_custom_edge_payload_rules_and_execution_targets(): void
    {
        $spec = new GraphSpecification(
            specVersion: 1,
            currentGraphVersion: 2,
            supportedGraphVersions: [2],
            nodeTypes: [
                'feature' => new NodeTypeDefinition(
                    type: 'feature',
                    className: FeatureNode::class,
                    semanticCategory: 'structural',
                    runtimeScope: 'both',
                    requiredPayloadKeys: ['feature', 'kind'],
                    payloadTypes: ['feature' => 'string', 'kind' => 'string'],
                    participatesInExecutionTopology: true,
                    participatesInOwnershipTopology: true,
                    graphCompatibility: [2],
                    traceable: true,
                    profileable: true,
                ),
                'context_manifest' => new NodeTypeDefinition(
                    type: 'context_manifest',
                    className: ContextManifestNode::class,
                    semanticCategory: 'observational',
                    runtimeScope: 'both',
                    requiredPayloadKeys: ['feature'],
                    payloadTypes: ['feature' => 'string'],
                    participatesInOwnershipTopology: true,
                    graphCompatibility: [2],
                    traceable: true,
                ),
            ],
            edgeTypes: [
                'feature_to_observation' => new EdgeTypeDefinition(
                    type: 'feature_to_observation',
                    semanticClass: 'execution',
                    allowedSourceTypes: ['feature'],
                    allowedTargetTypes: ['context_manifest'],
                    multiplicity: 'many_to_many',
                    payloadAllowed: true,
                    requiredPayloadKeys: ['mode'],
                    payloadTypes: ['mode' => 'string'],
                    roles: ['execution'],
                ),
            ],
            invariants: [],
            migrationRules: [],
        );

        $graph = [
            'graph_version' => 2,
            'graph_spec_version' => 1,
            'nodes' => [
                [
                    'id' => 'feature:publish_post',
                    'type' => 'feature',
                    'source_path' => 'app/features/publish_post/feature.yaml',
                    'payload' => ['feature' => 'publish_post', 'kind' => 'http'],
                    'graph_compatibility' => [2],
                ],
                [
                    'id' => 'context:publish_post',
                    'type' => 'context_manifest',
                    'source_path' => 'app/features/publish_post/context.manifest.json',
                    'payload' => ['feature' => 'publish_post'],
                    'graph_compatibility' => [2],
                ],
            ],
            'edges' => [
                [
                    'id' => 'edge:missing-mode',
                    'type' => 'feature_to_observation',
                    'from' => 'feature:publish_post',
                    'to' => 'context:publish_post',
                    'payload' => [],
                ],
                [
                    'id' => 'edge:bad-mode',
                    'type' => 'feature_to_observation',
                    'from' => 'feature:publish_post',
                    'to' => 'context:publish_post',
                    'payload' => ['mode' => ['invalid']],
                ],
            ],
        ];

        $report = (new GraphIntegrityVerifier($this->paths, $this->layout, $spec))->verifyGraphArray($graph, $spec);
        $codes = $this->codes($report);

        $this->assertFalse($report->ok);
        $this->assertContains('FDY9139_EDGE_PAYLOAD_KEY_MISSING', $codes);
        $this->assertContains('FDY9140_EDGE_PAYLOAD_TYPE_INVALID', $codes);
        $this->assertContains('FDY9141_EXECUTION_TARGET_IMPOSSIBLE', $codes);
    }

    /**
     * @return list<string>
     */
    private function codes(GraphIntegrityReport $report): array
    {
        return array_values(array_map(
            static fn(array $issue): string => (string) ($issue['code'] ?? ''),
            $report->issues,
        ));
    }

    /**
     * @param array<string,mixed> $graph
     */
    private function firstNodeIdByType(array $graph, string $type): string
    {
        foreach ((array) ($graph['nodes'] ?? []) as $row) {
            if ((string) ($row['type'] ?? '') === $type) {
                return (string) ($row['id'] ?? '');
            }
        }

        self::fail(sprintf('Expected a node of type %s.', $type));
    }

    /**
     * @param array<string,mixed> $graph
     * @return list<string>
     */
    private function allNodeIdsByType(array $graph, string $type): array
    {
        $ids = [];
        foreach ((array) ($graph['nodes'] ?? []) as $row) {
            if ((string) ($row['type'] ?? '') === $type) {
                $ids[] = (string) ($row['id'] ?? '');
            }
        }

        return $ids;
    }

    /**
     * @param array<string,mixed> $graph
     */
    private function firstNodeIndexByType(array $graph, string $type): int
    {
        foreach ((array) ($graph['nodes'] ?? []) as $index => $row) {
            if ((string) ($row['type'] ?? '') === $type) {
                return (int) $index;
            }
        }

        self::fail(sprintf('Expected an indexed node of type %s.', $type));
    }

    private function seedFeature(): void
    {
        $base = $this->project->root . '/app/features/publish_post';
        mkdir($base . '/tests', 0777, true);

        file_put_contents($base . '/feature.yaml', <<<'YAML'
version: 2
feature: publish_post
kind: http
description: publish
route:
  method: POST
  path: /posts
input:
  schema: app/features/publish_post/input.schema.json
output:
  schema: app/features/publish_post/output.schema.json
auth:
  required: true
  strategies: [bearer]
  permissions: [posts.create]
database:
  reads: []
  writes: []
  transactions: required
  queries: []
cache:
  reads: []
  writes: []
  invalidate: []
events:
  emit: []
  subscribe: []
jobs:
  dispatch: []
rate_limit: {}
tests:
  required: [feature]
llm:
  editable: true
  risk: low
YAML);

        file_put_contents($base . '/input.schema.json', '{"type":"object","properties":{"title":{"type":"string"}}}');
        file_put_contents($base . '/output.schema.json', '{"type":"object","properties":{"id":{"type":"string"}}}');
        file_put_contents($base . '/events.yaml', "version: 1\nemit: []\nsubscribe: []\n");
        file_put_contents($base . '/jobs.yaml', "version: 1\ndispatch: []\n");
        file_put_contents($base . '/cache.yaml', "version: 1\nentries: []\n");
        file_put_contents($base . '/permissions.yaml', "version: 1\npermissions: [posts.create]\nrules:\n  admin: [posts.create]\n");
        file_put_contents($base . '/context.manifest.json', '{"version":1,"feature":"publish_post","kind":"http"}');
        file_put_contents($base . '/tests/publish_post_feature_test.php', '<?php declare(strict_types=1);');
    }
}
