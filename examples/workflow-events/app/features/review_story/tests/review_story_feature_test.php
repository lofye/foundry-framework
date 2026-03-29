<?php

declare(strict_types=1);

use App\Features\ReviewStory\Action;
use Foundry\Auth\AuthContext;
use Foundry\Events\EventDispatcher;
use Foundry\Feature\FeatureServices;
use Foundry\Http\RequestContext;
use PHPUnit\Framework\TestCase;

final class ReviewStoryFeatureTest extends TestCase
{
    public function test_uses_route_params_and_emits_the_review_request_event(): void
    {
        $events = $this->createMock(EventDispatcher::class);
        $events->expects(self::once())
            ->method('emit')
            ->with('story.review_requested', [
                'story_id' => 'story_launch_checklist',
                'reviewer_id' => 'editor_007',
            ]);

        $services = $this->createStub(FeatureServices::class);
        $services->method('events')->willReturn($events);

        $request = new RequestContext(
            'POST',
            '/editorial/stories/story_launch_checklist/review',
            [],
            [],
            [],
            ['story_id' => 'story_launch_checklist'],
        );

        $action = new Action();

        self::assertSame(
            [
                'feature' => 'review_story',
                'state' => 'review',
                'story_id' => 'story_launch_checklist',
                'event' => 'story.review_requested',
            ],
            $action->handle([], $request, new AuthContext(true, 'editor_007'), $services),
        );
    }
}
