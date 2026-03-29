<?php

declare(strict_types=1);

namespace App\Features\SubmitStory;

use Foundry\Auth\AuthContext;
use Foundry\Feature\FeatureAction;
use Foundry\Feature\FeatureServices;
use Foundry\Http\RequestContext;

final class Action implements FeatureAction
{
    #[\Override]
    public function handle(array $input, RequestContext $request, AuthContext $auth, FeatureServices $services): array
    {
        $title = (string) ($input['title'] ?? 'Untitled Story');
        $storyId = $this->storyIdFromTitle($title);

        $services->events()->emit('story.submitted', [
            'story_id' => $storyId,
            'title' => $title,
        ]);

        return [
            'feature' => 'submit_story',
            'state' => 'review',
            'story_id' => $storyId,
            'event' => 'story.submitted',
        ];
    }

    private function storyIdFromTitle(string $title): string
    {
        $slug = trim((string) preg_replace('/[^a-z0-9]+/i', '_', strtolower($title)), '_');

        return 'story_' . ($slug !== '' ? $slug : 'draft');
    }
}
