<?php

declare(strict_types=1);

namespace App\Features\PublishPost;

use Foundry\Auth\AuthContext;
use Foundry\Feature\FeatureAction;
use Foundry\Feature\FeatureServices;
use Foundry\Http\RequestContext;

final class Action implements FeatureAction
{
    #[\Override]
    public function handle(array $input, RequestContext $request, AuthContext $auth, FeatureServices $services): array
    {
        $title = (string) ($input['title'] ?? 'Untitled Post');
        $authorId = $auth->userId() ?? 'editor_001';
        $post = [
            'id' => $this->postIdFromTitle($title),
            'title' => $title,
            'summary' => (string) ($input['summary'] ?? 'Published through the canonical example API.'),
            'state' => 'published',
            'author_id' => $authorId,
        ];

        $services->events()->emit('post.published', [
            'post_id' => $post['id'],
            'author_id' => $authorId,
        ]);

        return [
            'feature' => 'publish_post',
            'event' => 'post.published',
            'post' => $post,
        ];
    }

    private function postIdFromTitle(string $title): string
    {
        $slug = trim((string) preg_replace('/[^a-z0-9]+/i', '_', strtolower($title)), '_');

        return 'post_' . ($slug !== '' ? $slug : 'draft');
    }
}
