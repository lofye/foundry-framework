<?php

declare(strict_types=1);

namespace Foundry\Tests\Unit;

use Foundry\Compiler\ApplicationGraph;
use Foundry\Compiler\BuildLayout;
use Foundry\Compiler\CompileOptions;
use Foundry\Compiler\Diagnostics\DiagnosticBag;
use Foundry\Compiler\GraphCompiler;
use Foundry\Compiler\GraphEdge;
use Foundry\Compiler\GraphSpec\CanonicalGraphSpecification;
use Foundry\Compiler\GraphSpec\IllegalGraphEdge;
use Foundry\Compiler\GraphSpec\UnknownGraphEdgeType;
use Foundry\Support\Paths;
use Foundry\Tests\Fixtures\TempProject;
use PHPUnit\Framework\TestCase;

final class ApplicationGraphContractTest extends TestCase
{
    private TempProject $project;

    protected function setUp(): void
    {
        $this->project = new TempProject();
    }

    protected function tearDown(): void
    {
        $this->project->cleanup();
    }

    public function test_add_verified_edge_enforces_legality_and_indexes_edges(): void
    {
        $spec = CanonicalGraphSpecification::instance();
        $graph = new ApplicationGraph(2, 'dev-main', '2026-03-28T00:00:00+00:00', 'hash');

        $graph->addNode($spec->instantiateNode(
            type: 'feature',
            id: 'feature:publish_post',
            sourcePath: 'app/features/publish_post/feature.yaml',
            payload: ['feature' => 'publish_post', 'kind' => 'http'],
            sourceRegion: null,
            graphCompatibility: [2],
        ));
        $graph->addNode($spec->instantiateNode(
            type: 'route',
            id: 'route:POST /posts',
            sourcePath: 'app/features/publish_post/feature.yaml',
            payload: [
                'method' => 'POST',
                'path' => '/posts',
                'signature' => 'POST /posts',
                'features' => ['publish_post'],
            ],
            sourceRegion: null,
            graphCompatibility: [2],
        ));
        $graph->addNode($spec->instantiateNode(
            type: 'auth',
            id: 'auth:publish_post',
            sourcePath: 'app/features/publish_post/feature.yaml',
            payload: ['feature' => 'publish_post'],
            sourceRegion: null,
            graphCompatibility: [2],
        ));

        $graph->addVerifiedEdge(new GraphEdge(
            id: 'edge:feature-route',
            type: 'feature_to_route',
            from: 'feature:publish_post',
            to: 'route:POST /posts',
            payload: [],
        ));
        $graph->addVerifiedEdge(new GraphEdge(
            id: 'edge:feature-auth',
            type: 'feature_to_auth_config',
            from: 'feature:publish_post',
            to: 'auth:publish_post',
            payload: [],
        ));

        $this->assertCount(1, $graph->edgesByType('feature_to_route'));
        $this->assertCount(2, $graph->dependencies('feature:publish_post'));
        $this->assertCount(1, $graph->dependents('route:POST /posts'));
        $this->assertCount(1, $graph->nodesByCategory('structural'));
        $this->assertCount(1, $graph->nodesByCategory('policy'));

        $this->expectException(UnknownGraphEdgeType::class);
        $graph->addVerifiedEdge(new GraphEdge(
            id: 'edge:unknown',
            type: 'unknown_edge',
            from: 'feature:publish_post',
            to: 'route:POST /posts',
            payload: [],
        ));
    }

    public function test_add_verified_edge_rejects_missing_illegal_and_duplicate_edges(): void
    {
        $spec = CanonicalGraphSpecification::instance();
        $graph = new ApplicationGraph(2, 'dev-main', '2026-03-28T00:00:00+00:00', 'hash');

        $graph->addNode($spec->instantiateNode(
            type: 'feature',
            id: 'feature:publish_post',
            sourcePath: 'app/features/publish_post/feature.yaml',
            payload: ['feature' => 'publish_post', 'kind' => 'http'],
            sourceRegion: null,
            graphCompatibility: [2],
        ));
        $graph->addNode($spec->instantiateNode(
            type: 'route',
            id: 'route:POST /posts',
            sourcePath: 'app/features/publish_post/feature.yaml',
            payload: [
                'method' => 'POST',
                'path' => '/posts',
                'signature' => 'POST /posts',
                'features' => ['publish_post'],
            ],
            sourceRegion: null,
            graphCompatibility: [2],
        ));

        $graph->addVerifiedEdge(new GraphEdge(
            id: 'edge:feature-route',
            type: 'feature_to_route',
            from: 'feature:publish_post',
            to: 'route:POST /posts',
            payload: [],
        ));

        try {
            $graph->addVerifiedEdge(new GraphEdge(
                id: 'edge:missing-source',
                type: 'feature_to_route',
                from: 'feature:missing',
                to: 'route:POST /posts',
                payload: [],
            ));
            self::fail('Expected a missing-source edge to be rejected.');
        } catch (IllegalGraphEdge $error) {
            $this->assertStringContainsString('missing source node', $error->getMessage());
        }

        try {
            $graph->addVerifiedEdge(new GraphEdge(
                id: 'edge:missing-target',
                type: 'feature_to_route',
                from: 'feature:publish_post',
                to: 'route:missing',
                payload: [],
            ));
            self::fail('Expected a missing-target edge to be rejected.');
        } catch (IllegalGraphEdge $error) {
            $this->assertStringContainsString('missing target node', $error->getMessage());
        }

        try {
            $graph->addVerifiedEdge(new GraphEdge(
                id: 'edge:illegal',
                type: 'feature_to_route',
                from: 'route:POST /posts',
                to: 'feature:publish_post',
                payload: [],
            ));
            self::fail('Expected an illegal edge pairing to be rejected.');
        } catch (IllegalGraphEdge $error) {
            $this->assertStringContainsString('not legal between', $error->getMessage());
        }

        try {
            $graph->addVerifiedEdge(new GraphEdge(
                id: 'edge:feature-route',
                type: 'feature_to_route',
                from: 'feature:publish_post',
                to: 'route:POST /posts',
                payload: [],
            ));
            self::fail('Expected a duplicate edge id to be rejected.');
        } catch (IllegalGraphEdge $error) {
            $this->assertStringContainsString('Duplicate graph edge id', $error->getMessage());
        }
    }

    public function test_subgraph_extractors_and_fingerprints_remain_stable_for_compiled_graphs(): void
    {
        $this->seedFeature();

        $paths = Paths::fromCwd($this->project->root);
        $compiler = new GraphCompiler($paths);
        $compileResult = $compiler->compile(new CompileOptions());
        $graph = $compileResult->graph;

        $featureGraph = $graph->featureSubgraph('publish_post');
        $this->assertSame(['publish_post'], $featureGraph->features());
        $this->assertNotEmpty($featureGraph->edgesByType('feature_to_route'));
        $this->assertSame([], $graph->featureSubgraph('missing_feature')->nodes());

        $execution = $graph->executionSubgraph('publish_post');
        $ownership = $graph->ownershipSubgraph('publish_post');
        $observability = $graph->observabilitySubgraph('publish_post');

        $this->assertArrayHasKey('execution_plan_to_stage', $execution->edgeCountsByType());
        $this->assertArrayHasKey('feature_to_execution_plan', $ownership->edgeCountsByType());
        $this->assertArrayHasKey('context_manifest', $observability->nodeCountsByType());

        $fingerprint = $featureGraph->fingerprint();
        $topology = $featureGraph->topologyFingerprint();
        $payloadStructure = $featureGraph->payloadStructureFingerprint();

        $this->assertSame($fingerprint, $featureGraph->fingerprint());
        $this->assertSame($topology, $featureGraph->topologyFingerprint());
        $this->assertSame($payloadStructure, $featureGraph->payloadStructureFingerprint());

        $serialized = $graph->toArray(new DiagnosticBag());
        $restored = ApplicationGraph::fromArray($serialized);
        $this->assertSame($graph->fingerprint(), $restored->fingerprint());
        $this->assertSame($graph->topologyFingerprint(), $restored->topologyFingerprint());

        $routeId = array_key_first($graph->nodesByType('route'));
        $this->assertIsString($routeId);

        $before = $graph->topologyFingerprint();
        $graph->removeNode($routeId);
        $this->assertSame([], $graph->edgesByType('feature_to_route'));
        $this->assertNotSame($before, $graph->topologyFingerprint());

        $featuresOnly = ApplicationGraph::fromArray($serialized);
        $featuresOnly->retainOnlyFeatureNodes();
        $this->assertSame(['feature' => 1], $featuresOnly->nodeCountsByType());
        $this->assertSame([], $featuresOnly->edges());

        $withoutFeature = ApplicationGraph::fromArray($serialized);
        $withoutFeature->removeFeature('publish_post');
        $this->assertSame([], $withoutFeature->features());
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
