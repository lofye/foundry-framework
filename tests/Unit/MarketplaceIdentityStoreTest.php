<?php

declare(strict_types=1);

namespace Foundry\Tests\Unit;

use Foundry\Marketplace\MarketplaceIdentityStore;
use Foundry\Support\Clock;
use Foundry\Support\FoundryError;
use Foundry\Support\Paths;
use Foundry\Tests\Fixtures\TempProject;
use PHPUnit\Framework\TestCase;

final class MarketplaceIdentityStoreTest extends TestCase
{
    private TempProject $project;
    private Clock $clock;

    protected function setUp(): void
    {
        $this->project = new TempProject();
        $this->clock = new Clock(new \DateTimeImmutable('2026-01-01T00:00:00+00:00'));
    }

    protected function tearDown(): void
    {
        $this->project->cleanup();
    }

    public function test_missing_file_is_unauthenticated_and_verifies_pass(): void
    {
        $store = $this->store();
        $this->assertFalse($store->exists());
        $this->assertNull($store->read());

        $inspect = $store->inspect();
        $verify = $store->verify();

        $this->assertFalse($inspect['configured']);
        $this->assertFalse($inspect['authenticated']);
        $this->assertSame('unauthenticated', $inspect['status']);
        $this->assertFalse($inspect['token']['present']);

        $this->assertSame('pass', $verify['status']);
        $this->assertFalse($verify['configured']);
        $this->assertSame('MARKETPLACE_AUTH_NOT_AUTHENTICATED', $verify['code']);
        $this->assertSame([], $verify['errors']);
    }

    public function test_write_and_read_round_trip_uses_canonical_shape(): void
    {
        $store = $this->store();
        $store->write($this->credential(expiresAt: null));

        $this->assertTrue($store->exists());
        $credential = $store->read();
        $this->assertIsArray($credential);
        $this->assertSame('bearer', $credential['token_type']);
        $this->assertSame('token_demo_1234', $credential['access_token']);
        $this->assertNull($credential['expires_at']);
        $this->assertSame('demo-user', $credential['user']['id']);
        $this->assertSame('demo@example.com', $credential['user']['email']);

        $inspect = $store->inspect();
        $this->assertTrue($inspect['configured']);
        $this->assertTrue($inspect['authenticated']);
        $this->assertTrue($inspect['token']['present']);
        $this->assertSame('bearer', $inspect['token']['type']);
        $this->assertArrayNotHasKey('access_token', $inspect);

        $verify = $store->verify();
        $this->assertSame('pass', $verify['status']);
        $this->assertTrue($verify['authenticated']);
    }

    public function test_malformed_json_fails_closed(): void
    {
        $path = $this->project->root . '/.foundry/marketplace/identity.json';
        if (!is_dir(dirname($path))) {
            mkdir(dirname($path), 0777, true);
        }
        file_put_contents($path, "{broken-json}\n");

        $store = $this->store();
        $this->expectException(FoundryError::class);
        $this->expectExceptionMessage('Marketplace auth state is invalid.');
        $store->read();
    }

    public function test_missing_required_fields_fail_closed(): void
    {
        $path = $this->project->root . '/.foundry/marketplace/identity.json';
        if (!is_dir(dirname($path))) {
            mkdir(dirname($path), 0777, true);
        }
        file_put_contents($path, json_encode(['token_type' => 'bearer'], JSON_THROW_ON_ERROR) . "\n");

        $verify = $this->store()->verify();
        $this->assertSame('fail', $verify['status']);
        $this->assertSame('MARKETPLACE_AUTH_STATE_INVALID', $verify['code']);
    }

    public function test_expired_credentials_are_non_authenticated_and_fail_verify(): void
    {
        $store = $this->store();
        $store->write($this->credential(expiresAt: '2025-01-01T00:00:00Z'));

        $inspect = $store->inspect();
        $verify = $store->verify();

        $this->assertTrue($inspect['configured']);
        $this->assertFalse($inspect['authenticated']);
        $this->assertSame('expired', $inspect['status']);
        $this->assertSame('MARKETPLACE_AUTH_EXPIRED', $inspect['code']);
        $this->assertSame('demo-user', $inspect['user']['id']);
        $this->assertSame('demo@example.com', $inspect['user']['email']);

        $this->assertSame('fail', $verify['status']);
        $this->assertSame('MARKETPLACE_AUTH_EXPIRED', $verify['code']);
    }

    public function test_logout_clear_is_idempotent(): void
    {
        $store = $this->store();
        $store->write($this->credential(expiresAt: null));
        $this->assertTrue($store->clear());
        $this->assertFalse($store->clear());
        $this->assertFalse($store->exists());
    }

    private function store(): MarketplaceIdentityStore
    {
        return new MarketplaceIdentityStore(new Paths($this->project->root), $this->clock);
    }

    /**
     * @return array<string,mixed>
     */
    private function credential(?string $expiresAt): array
    {
        return [
            'token_type' => 'bearer',
            'access_token' => 'token_demo_1234',
            'expires_at' => $expiresAt,
            'user' => [
                'id' => 'demo-user',
                'email' => 'demo@example.com',
                'name' => null,
                'created_at' => '2026-01-01T00:00:00Z',
            ],
        ];
    }
}
