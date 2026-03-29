<?php

declare(strict_types=1);

use App\Features\PublishStory\Action;
use Foundry\Auth\AuthContext;
use Foundry\Events\EventDispatcher;
use Foundry\Feature\FeatureServices;
use Foundry\Http\RequestContext;
use Foundry\Queue\JobDispatcher;
use PHPUnit\Framework\TestCase;

final class PublishStoryFeatureTest extends TestCase
{
    public function test_emits_the_publication_event_and_dispatches_the_feed_refresh_job(): void
    {
        $events = $this->createMock(EventDispatcher::class);
        $events->expects(self::once())
            ->method('emit')
            ->with('story.published', [
                'story_id' => 'story_launch_checklist',
            ]);

        $jobs = $this->createMock(JobDispatcher::class);
        $jobs->expects(self::once())
            ->method('dispatch')
            ->with('refresh_story_feed', [
                'story_id' => 'story_launch_checklist',
            ]);

        $services = $this->createStub(FeatureServices::class);
        $services->method('events')->willReturn($events);
        $services->method('jobs')->willReturn($jobs);

        $request = new RequestContext(
            'POST',
            '/editorial/stories/story_launch_checklist/publish',
            [],
            [],
            [],
            ['story_id' => 'story_launch_checklist'],
        );

        $action = new Action();

        self::assertSame(
            [
                'feature' => 'publish_story',
                'state' => 'published',
                'story_id' => 'story_launch_checklist',
                'event' => 'story.published',
                'job' => 'refresh_story_feed',
            ],
            $action->handle([], $request, new AuthContext(true, 'editor_007'), $services),
        );
    }
}
