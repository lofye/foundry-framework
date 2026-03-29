<?php

declare(strict_types=1);

use App\Features\PublishPost\Action;
use Foundry\Auth\AuthContext;
use Foundry\Events\EventDispatcher;
use Foundry\Feature\FeatureServices;
use Foundry\Http\RequestContext;
use PHPUnit\Framework\TestCase;

final class PublishPostFeatureTest extends TestCase
{
    public function test_emits_post_published_event_with_deterministic_payload(): void
    {
        $events = $this->createMock(EventDispatcher::class);
        $events->expects(self::once())
            ->method('emit')
            ->with('post.published', [
                'post_id' => 'post_modern_foundry_patterns',
                'author_id' => 'editor_001',
            ]);

        $services = $this->createStub(FeatureServices::class);
        $services->method('events')->willReturn($events);

        $action = new Action();
        $auth = new AuthContext(true, 'editor_001');

        self::assertSame(
            [
                'feature' => 'publish_post',
                'event' => 'post.published',
                'post' => [
                    'id' => 'post_modern_foundry_patterns',
                    'title' => 'Modern Foundry Patterns',
                    'summary' => 'A crisp example write route for the docs catalog.',
                    'state' => 'published',
                    'author_id' => 'editor_001',
                ],
            ],
            $action->handle(
                [
                    'title' => 'Modern Foundry Patterns',
                    'summary' => 'A crisp example write route for the docs catalog.',
                ],
                new RequestContext('POST', '/posts'),
                $auth,
                $services,
            ),
        );
    }
}
