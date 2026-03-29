<?php

declare(strict_types=1);

namespace App\Features\ViewPost;

use Foundry\Auth\AuthContext;
use Foundry\Feature\FeatureAction;
use Foundry\Feature\FeatureServices;
use Foundry\Http\RequestContext;

final class Action implements FeatureAction
{
    private const POSTS = [
        'post_welcome_to_foundry' => [
            'id' => 'post_welcome_to_foundry',
            'title' => 'Welcome to Foundry',
            'summary' => 'A tiny, inspectable post payload for the canonical example API.',
            'state' => 'published',
        ],
        'post_graph_first_design' => [
            'id' => 'post_graph_first_design',
            'title' => 'Graph-First Design',
            'summary' => 'Shows how route-level features stay visible in compile and inspect output.',
            'state' => 'published',
        ],
    ];

    #[\Override]
    public function handle(array $input, RequestContext $request, AuthContext $auth, FeatureServices $services): array
    {
        $postId = (string) ($request->routeParams()['post_id'] ?? $input['post_id'] ?? 'post_welcome_to_foundry');
        $post = self::POSTS[$postId] ?? [
            'id' => $postId,
            'title' => 'Unknown Post',
            'summary' => 'This example keeps route-param behavior deterministic even when a post is missing.',
            'state' => 'draft',
        ];

        return [
            'feature' => 'view_post',
            'post' => $post,
        ];
    }
}
