<?php

declare(strict_types=1);

use App\Features\ListPosts\Action;
use Foundry\Auth\AuthContext;
use Foundry\Feature\FeatureServices;
use Foundry\Http\RequestContext;
use PHPUnit\Framework\TestCase;

final class ListPostsFeatureTest extends TestCase
{
    public function test_returns_all_posts_by_default(): void
    {
        $action = new Action();
        $services = $this->createStub(FeatureServices::class);

        self::assertSame(
            [
                'feature' => 'list_posts',
                'returned' => 3,
                'posts' => [
                    [
                        'id' => 'post_welcome_to_foundry',
                        'title' => 'Welcome to Foundry',
                        'state' => 'published',
                    ],
                    [
                        'id' => 'post_graph_first_design',
                        'title' => 'Graph-First Design',
                        'state' => 'published',
                    ],
                    [
                        'id' => 'post_safe_edit_loops',
                        'title' => 'Safe Edit Loops',
                        'state' => 'published',
                    ],
                ],
            ],
            $action->handle([], new RequestContext('GET', '/posts'), AuthContext::guest(), $services),
        );
    }

    public function test_honors_limit_input(): void
    {
        $action = new Action();
        $services = $this->createStub(FeatureServices::class);

        self::assertSame(
            [
                'feature' => 'list_posts',
                'returned' => 2,
                'posts' => [
                    [
                        'id' => 'post_welcome_to_foundry',
                        'title' => 'Welcome to Foundry',
                        'state' => 'published',
                    ],
                    [
                        'id' => 'post_graph_first_design',
                        'title' => 'Graph-First Design',
                        'state' => 'published',
                    ],
                ],
            ],
            $action->handle(['limit' => 2], new RequestContext('GET', '/posts'), AuthContext::guest(), $services),
        );
    }
}
