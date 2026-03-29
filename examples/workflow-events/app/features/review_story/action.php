<?php

declare(strict_types=1);

namespace App\Features\ReviewStory;

use Foundry\Auth\AuthContext;
use Foundry\Feature\FeatureAction;
use Foundry\Feature\FeatureServices;
use Foundry\Http\RequestContext;

final class Action implements FeatureAction
{
    #[\Override]
    public function handle(array $input, RequestContext $request, AuthContext $auth, FeatureServices $services): array
    {
        $storyId = (string) ($request->routeParams()['story_id'] ?? $input['story_id'] ?? '');
        $reviewerId = $auth->userId() ?? 'editor_001';

        $services->events()->emit('story.review_requested', [
            'story_id' => $storyId,
            'reviewer_id' => $reviewerId,
        ]);

        return [
            'feature' => 'review_story',
            'state' => 'review',
            'story_id' => $storyId,
            'event' => 'story.review_requested',
        ];
    }
}
