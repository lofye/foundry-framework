<?php

declare(strict_types=1);

namespace Foundry\Tests\Unit;

use Foundry\Marketplace\MarketplaceAuthService;
use Foundry\Marketplace\MarketplaceIdentityStore;
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
        $this->assertSame('.foundry/marketplace/identity.json', $before['storage']['path']);
        $this->assertFalse($before['storage']['exists']);

        $login = $service->login('demo-user', 'token_demo_1234');
        $this->assertTrue($login['authenticated']);
        $this->assertSame('demo-user', $login['identity']['user_id']);
        $this->assertSame('toke...1234', $login['identity']['token_hint']);
        $this->assertTrue($login['storage']['exists']);
        $this->assertTrue($login['api']['authenticated_request_available']);

        $whoami = $service->whoami();
        $this->assertSame($login, $whoami);

        $logout = $service->logout();
        $this->assertFalse($logout['authenticated']);
        $this->assertFalse($logout['storage']['exists']);
        $this->assertTrue($logout['logout']['had_session']);

        $after = $service->whoami();
        $this->assertFalse($after['authenticated']);
    }

    public function test_authenticated_request_contains_required_headers(): void
    {
        $service = $this->service();
        $service->login('demo-user', 'token_demo_1234');

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

    public function test_invalid_identity_file_shape_throws_deterministic_error(): void
    {
        $path = $this->project->root . '/.foundry/marketplace/identity.json';
        if (!is_dir(dirname($path))) {
            mkdir(dirname($path), 0777, true);
        }
        file_put_contents($path, "{\"user_id\":\"demo-user\"}\n");

        $this->expectException(FoundryError::class);
        $this->expectExceptionMessage('Marketplace identity file is missing required fields.');

        $this->service()->whoami();
    }

    private function service(): MarketplaceAuthService
    {
        return new MarketplaceAuthService(new MarketplaceIdentityStore(new Paths($this->project->root)));
    }
}

