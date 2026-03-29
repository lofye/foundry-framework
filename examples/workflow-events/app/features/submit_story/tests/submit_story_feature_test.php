<?php

declare(strict_types=1);

use App\Features\SubmitStory\Action;
use Foundry\Auth\AuthContext;
use Foundry\Events\EventDispatcher;
use Foundry\Feature\FeatureServices;
use Foundry\Http\RequestContext;
use PHPUnit\Framework\TestCase;

final class SubmitStoryFeatureTest extends TestCase
{
    public function test_creates_a_story_id_and_emits_the_submission_event(): void
    {
        $events = $this->createMock(EventDispatcher::class);
        $events->expects(self::once())
            ->method('emit')
            ->with('story.submitted', [
                'story_id' => 'story_launch_checklist',
                'title' => 'Launch Checklist',
            ]);

        $services = $this->createStub(FeatureServices::class);
        $services->method('events')->willReturn($events);

        $action = new Action();

        self::assertSame(
            [
                'feature' => 'submit_story',
                'state' => 'review',
                'story_id' => 'story_launch_checklist',
                'event' => 'story.submitted',
            ],
            $action->handle(
                ['title' => 'Launch Checklist'],
                new RequestContext('POST', '/editorial/stories'),
                new AuthContext(true, 'editor_001'),
                $services,
            ),
        );
    }
}
