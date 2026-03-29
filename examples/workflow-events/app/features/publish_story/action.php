<?php

declare(strict_types=1);

namespace App\Features\PublishStory;

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

        $services->events()->emit('story.published', [
            'story_id' => $storyId,
        ]);
        $services->jobs()->dispatch('refresh_story_feed', [
            'story_id' => $storyId,
        ]);

        return [
            'feature' => 'publish_story',
            'state' => 'published',
            'story_id' => $storyId,
            'event' => 'story.published',
            'job' => 'refresh_story_feed',
        ];
    }
}
