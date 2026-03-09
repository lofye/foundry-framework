<?php
declare(strict_types=1);

namespace Foundry\Tests\Unit;

use Foundry\Compiler\ApplicationGraph;
use Foundry\Compiler\GraphEdge;
use Foundry\Compiler\IR\CacheNode;
use Foundry\Compiler\IR\EventNode;
use Foundry\Compiler\IR\FeatureNode;
use Foundry\Compiler\IR\RouteNode;
use Foundry\Compiler\Visualization\GraphVisualizer;
use PHPUnit\Framework\TestCase;

final class GraphVisualizerTest extends TestCase
{
    public function test_builds_views_and_renders_all_formats(): void
    {
        $graph = new ApplicationGraph(1, '1.0.0', gmdate(DATE_ATOM), 'hash');

        $graph->addNode(new FeatureNode('feature:publish_post', 'app/features/publish_post/feature.yaml', ['feature' => 'publish_post']));
        $graph->addNode(new FeatureNode('feature:update_feed', 'app/features/update_feed/feature.yaml', ['feature' => 'update_feed']));
        $graph->addNode(new EventNode('event:post.created', 'app/features/publish_post/events.yaml', ['name' => 'post.created']));
        $graph->addNode(new RouteNode('route:POST:/posts', 'app/features/publish_post/feature.yaml', ['signature' => 'POST /posts']));
        $graph->addNode(new CacheNode('cache:posts:list', 'app/features/publish_post/cache.yaml', ['key' => 'posts:list']));

        $graph->addEdge(GraphEdge::make('event_publisher_to_subscriber', 'feature:publish_post', 'feature:update_feed', ['event' => 'post.created']));
        $graph->addEdge(GraphEdge::make('feature_to_event_emit', 'feature:publish_post', 'event:post.created'));
        $graph->addEdge(GraphEdge::make('feature_to_event_subscribe', 'feature:update_feed', 'event:post.created'));
        $graph->addEdge(GraphEdge::make('feature_to_route', 'feature:publish_post', 'route:POST:/posts'));
        $graph->addEdge(GraphEdge::make('feature_to_cache_invalidation', 'feature:publish_post', 'cache:posts:list'));

        $visualizer = new GraphVisualizer();

        $dependencies = $visualizer->build($graph, 'dependencies');
        $this->assertSame('dependencies', $dependencies['view']);
        $this->assertCount(2, $dependencies['nodes']);
        $this->assertCount(1, $dependencies['edges']);

        $events = $visualizer->build($graph, 'events');
        $this->assertCount(3, $events['nodes']);
        $this->assertCount(2, $events['edges']);

        $routes = $visualizer->build($graph, 'routes');
        $this->assertNotEmpty($routes['nodes']);
        $this->assertNotEmpty($routes['edges']);

        $caches = $visualizer->build($graph, 'caches', 'publish_post');
        $this->assertSame('publish_post', $caches['feature_filter']);
        $this->assertNotEmpty($caches['nodes']);
        $this->assertNotEmpty($caches['edges']);

        $mermaid = $visualizer->render($dependencies, 'mermaid');
        $this->assertStringContainsString('graph TD', $mermaid);
        $this->assertStringContainsString('post.created', $mermaid);

        $dot = $visualizer->render($events, 'dot');
        $this->assertStringContainsString('digraph foundry', $dot);
        $this->assertStringContainsString('feature:publish_post', $dot);

        $svg = $visualizer->render($caches, 'svg');
        $this->assertStringContainsString('<svg', $svg);
        $this->assertStringContainsString('posts:list', $svg);

        $json = $visualizer->render($routes, 'json');
        $this->assertStringContainsString('"view"', $json);
        $this->assertStringContainsString('"routes"', $json);
    }

    public function test_unknown_view_and_format_fall_back_deterministically(): void
    {
        $graph = new ApplicationGraph(1, '1.0.0', gmdate(DATE_ATOM), 'hash');
        $graph->addNode(new FeatureNode('feature:alpha', 'app/features/alpha/feature.yaml', ['feature' => 'alpha']));

        $visualizer = new GraphVisualizer();
        $data = $visualizer->build($graph, 'unknown-view');
        $this->assertSame('dependencies', $data['view']);

        $rendered = $visualizer->render($data, 'unknown-format');
        $this->assertStringContainsString('graph TD', $rendered);
    }
}

