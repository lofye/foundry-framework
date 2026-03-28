<?php

declare(strict_types=1);

namespace Foundry\Tests\Unit;

use Foundry\Compiler\ApplicationGraph;
use Foundry\Compiler\GraphCompiler;
use Foundry\Compiler\GraphSpec\CanonicalGraphSpecification;
use PHPUnit\Framework\TestCase;

final class GraphSpecRegistryTest extends TestCase
{
    public function test_canonical_graph_spec_exposes_required_node_and_edge_types(): void
    {
        $spec = CanonicalGraphSpecification::instance();

        $this->assertSame(GraphCompiler::GRAPH_VERSION, $spec->currentGraphVersion());
        $this->assertNotNull($spec->nodeType('feature'));
        $this->assertNotNull($spec->nodeType('execution_plan'));
        $this->assertNotNull($spec->edgeType('feature_to_route'));
        $this->assertNotNull($spec->edgeType('event_publisher_to_subscriber'));
        $this->assertArrayHasKey('required', $spec->artifactSchema());
    }

    public function test_application_graph_from_array_migrates_v1_graph_artifacts(): void
    {
        $graph = ApplicationGraph::fromArray([
            'graph_version' => 1,
            'framework_version' => 'test',
            'compiled_at' => '2026-03-27T00:00:00+00:00',
            'source_hash' => 'abc123',
            'nodes' => [
                [
                    'id' => 'feature:publish_post',
                    'type' => 'feature',
                    'source_path' => 'app/features/publish_post/feature.yaml',
                    'payload' => [
                        'feature' => 'publish_post',
                        'kind' => 'http',
                    ],
                    'graph_compatibility' => [1],
                ],
            ],
            'edges' => [],
        ]);

        $this->assertSame(GraphCompiler::GRAPH_VERSION, $graph->graphVersion());
        $this->assertTrue($graph->hasNode('feature:publish_post'));
        $this->assertContains(GraphCompiler::GRAPH_VERSION, $graph->node('feature:publish_post')?->graphCompatibility() ?? []);
    }
}
