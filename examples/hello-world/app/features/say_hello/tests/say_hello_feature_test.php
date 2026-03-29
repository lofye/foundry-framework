<?php

declare(strict_types=1);

use App\Features\SayHello\Action;
use Foundry\Auth\AuthContext;
use Foundry\Feature\FeatureServices;
use Foundry\Http\RequestContext;
use PHPUnit\Framework\TestCase;

final class SayHelloFeatureTest extends TestCase
{
    public function test_returns_default_message_without_name(): void
    {
        $action = new Action();
        $services = $this->createStub(FeatureServices::class);

        self::assertSame(
            [
                'message' => 'Hello, world.',
                'feature' => 'say_hello',
            ],
            $action->handle([], new RequestContext('GET', '/hello'), AuthContext::guest(), $services),
        );
    }

    public function test_returns_personalized_message_when_name_is_present(): void
    {
        $action = new Action();
        $services = $this->createStub(FeatureServices::class);

        self::assertSame(
            [
                'message' => 'Hello, Ada.',
                'feature' => 'say_hello',
            ],
            $action->handle(['name' => 'Ada'], new RequestContext('GET', '/hello'), AuthContext::guest(), $services),
        );
    }
}
