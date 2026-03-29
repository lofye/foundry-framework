<?php

declare(strict_types=1);

namespace App\Features\ListPosts;

use Foundry\Auth\AuthContext;
use Foundry\Feature\FeatureAction;
use Foundry\Feature\FeatureServices;
use Foundry\Http\RequestContext;

final class Action implements FeatureAction
{
    private const POSTS = [
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
    ];

    #[\Override]
    public function handle(array $input, RequestContext $request, AuthContext $auth, FeatureServices $services): array
    {
        $limit = (int) ($input['limit'] ?? count(self::POSTS));
        $limit = max(1, min($limit, count(self::POSTS)));
        $posts = array_slice(self::POSTS, 0, $limit);

        return [
            'feature' => 'list_posts',
            'returned' => count($posts),
            'posts' => $posts,
        ];
    }
}
