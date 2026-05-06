<?php

declare(strict_types=1);

namespace Foundry\Tests\Unit;

use Foundry\Marketplace\MarketplaceAuthService;
use Foundry\Marketplace\MarketplaceIdentityStore;
use Foundry\Support\Clock;
use Foundry\Support\FoundryError;
use Foundry\Support\Paths;
use Foundry\Tests\Fixtures\TempProject;
use PHPUnit\Framework\TestCase;

final class MarketplaceAuthServiceTest extends TestCase
{
    private TempProject $project;

    protected function setUp(): void
    {
        $this->project = new TempProject();
    }

    protected function tearDown(): void
    {
        $this->project->cleanup();
    }

    public function test_login_whoami_and_logout_are_deterministic(): void
    {
        $service = $this->service();

        $before = $service->whoami();
        $this->assertFalse($before['authenticated']);
        $this->assertNull($before['user']);

        $login = $service->login('demo-user', 'token_demo_1234', 'demo@example.com', null, null);
        $this->assertSame('ok', $login['status']);
        $this->assertTrue($login['authenticated']);
        $this->assertSame('demo-user', $login['user']['id']);
        $this->assertSame('demo@example.com', $login['user']['email']);
        $this->assertNull($login['user']['name']);

        $whoami = $service->whoami();
        $this->assertTrue($whoami['authenticated']);
        $this->assertSame('demo-user', $whoami['user']['id']);
        $this->assertSame('demo@example.com', $whoami['user']['email']);

        $logout = $service->logout();
        $this->assertSame('ok', $logout['status']);
        $this->assertFalse($logout['authenticated']);
        $this->assertNull($logout['user']);
        $this->assertTrue($logout['logout']['had_session']);

        $after = $service->whoami();
        $this->assertFalse($after['authenticated']);
        $this->assertNull($after['user']);
    }

    public function test_login_replaces_previous_credentials(): void
    {
        $service = $this->service();

        $service->login('first-user', 'token_first_1234', 'first@example.com', null, null);
        $service->login('second-user', 'token_second_5678', 'second@example.com', null, null);

        $whoami = $service->whoami();
        $this->assertTrue($whoami['authenticated']);
        $this->assertSame('second-user', $whoami['user']['id']);
        $this->assertSame('second@example.com', $whoami['user']['email']);
    }

    public function test_authenticated_request_contains_required_headers(): void
    {
        $service = $this->service();
        $service->login('demo-user', 'token_demo_1234', 'demo@example.com', null, null);

        $request = $service->authenticatedRequest('get', '/packs', ['limit' => 25]);

        $this->assertSame('ok', $request['status']);
        $this->assertSame('GET', $request['request']['method']);
        $this->assertSame('/packs', $request['request']['path']);
        $this->assertSame('Bearer token_demo_1234', $request['request']['headers']['Authorization']);
        $this->assertSame('demo-user', $request['request']['headers']['X-Foundry-Marketplace-User']);
        $this->assertSame(['limit' => 25], $request['request']['body']);
    }

    public function test_authenticated_request_requires_identity(): void
    {
        $this->expectException(FoundryError::class);
        $this->expectExceptionMessage('Marketplace authentication is required.');

        $this->service()->authenticatedRequest('GET', '/packs');
    }

    public function test_expired_credentials_fail_closed(): void
    {
        $clock = new Clock(new \DateTimeImmutable('2026-01-01T00:00:00+00:00'));
        $service = $this->service($clock);
        $service->login(
            'demo-user',
            'token_demo_1234',
            'demo@example.com',
            null,
            '2025-01-01T00:00:00Z',
        );

        $whoami = $service->whoami();
        $this->assertFalse($whoami['authenticated']);
        $this->assertSame('expired', $whoami['reason']);
        $this->assertSame('MARKETPLACE_AUTH_EXPIRED', $whoami['code']);
        $this->assertSame('demo-user', $whoami['user']['id']);
        $this->assertSame('demo@example.com', $whoami['user']['email']);

        $this->expectException(FoundryError::class);
        $this->expectExceptionMessage('Marketplace credentials are expired.');
        $service->authenticatedRequest('GET', '/packs');
    }

    public function test_malformed_state_fails_closed(): void
    {
        $path = $this->project->root . '/.foundry/marketplace/identity.json';
        if (!is_dir(dirname($path))) {
            mkdir(dirname($path), 0777, true);
        }
        file_put_contents($path, "{\"token_type\":\"bearer\"}\n");

        $whoami = $this->service()->whoami();
        $this->assertSame('error', $whoami['status']);
        $this->assertSame('MARKETPLACE_AUTH_STATE_INVALID', $whoami['code']);
        $this->assertFalse($whoami['authenticated']);
        $this->assertNull($whoami['user']);
    }

    private function service(?Clock $clock = null): MarketplaceAuthService
    {
        $clock ??= new Clock(new \DateTimeImmutable('2026-01-01T00:00:00+00:00'));
        $store = new MarketplaceIdentityStore(new Paths($this->project->root), $clock);

        return new MarketplaceAuthService($store, $clock);
    }
}

