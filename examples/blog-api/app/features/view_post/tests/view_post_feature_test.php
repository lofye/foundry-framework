<?php

declare(strict_types=1);

use App\Features\ViewPost\Action;
use Foundry\Auth\AuthContext;
use Foundry\Feature\FeatureServices;
use Foundry\Http\RequestContext;
use PHPUnit\Framework\TestCase;

final class ViewPostFeatureTest extends TestCase
{
    public function test_reads_the_requested_post_from_route_params(): void
    {
        $action = new Action();
        $services = $this->createStub(FeatureServices::class);
        $request = new RequestContext(
            'GET',
            '/posts/post_graph_first_design',
            [],
            [],
            [],
            ['post_id' => 'post_graph_first_design'],
        );

        self::assertSame(
            [
                'feature' => 'view_post',
                'post' => [
                    'id' => 'post_graph_first_design',
                    'title' => 'Graph-First Design',
                    'summary' => 'Shows how route-level features stay visible in compile and inspect output.',
                    'state' => 'published',
                ],
            ],
            $action->handle([], $request, AuthContext::guest(), $services),
        );
    }
}
