<?php

declare(strict_types=1);

namespace Foundry\Tests\Unit;

use Foundry\Marketplace\MarketplaceEntitlementCache;
use Foundry\Support\Clock;
use Foundry\Support\FoundryError;
use Foundry\Support\Paths;
use Foundry\Tests\Fixtures\TempProject;
use PHPUnit\Framework\TestCase;

final class MarketplaceEntitlementCacheTest extends TestCase
{
    private TempProject $project;
    private Clock $clock;

    protected function setUp(): void
    {
        $this->project = new TempProject();
        $this->clock = new Clock(new \DateTimeImmutable('2026-01-02T00:00:00+00:00'));
    }

    protected function tearDown(): void
    {
        $this->project->cleanup();
    }

    public function test_missing_cache_is_reported_as_not_configured(): void
    {
        $cache = $this->cache();

        $inspect = $cache->inspect();
        $verify = $cache->verify();

        $this->assertFalse($inspect['configured']);
        $this->assertSame('missing', $inspect['status']);
        $this->assertSame([], $inspect['entitlements']);

        $this->assertSame('pass', $verify['status']);
        $this->assertSame([], $verify['errors']);
    }

    public function test_persist_normalizes_order_and_expires_granted_rows(): void
    {
        $cache = $this->cache();

        $persisted = $cache->persist([
            [
                'pack' => 'vendor/z-pack',
                'type' => 'licensed',
                'status' => 'granted',
                'expires_at' => '2025-12-31T00:00:00Z',
                'source' => 'marketplace',
                'granted_at' => '2025-01-01T00:00:00Z',
            ],
            [
                'pack' => 'vendor/a-pack',
                'type' => 'premium',
                'status' => 'granted',
                'expires_at' => null,
                'source' => 'marketplace',
                'granted_at' => '2026-01-01T00:00:00Z',
            ],
        ]);

        $this->assertSame('vendor/a-pack', $persisted[0]['pack']);
        $this->assertSame('vendor/z-pack', $persisted[1]['pack']);
        $this->assertSame('expired', $persisted[1]['status']);

        $inspect = $cache->inspect();
        $this->assertTrue($inspect['configured']);
        $this->assertSame('ok', $inspect['status']);
        $this->assertSame(2, $inspect['count']);
    }

    public function test_invalid_cache_fails_closed(): void
    {
        $path = $this->project->root . '/.foundry/marketplace/entitlements.json';
        if (!is_dir(dirname($path))) {
            mkdir(dirname($path), 0777, true);
        }
        file_put_contents($path, '{invalid-json}' . PHP_EOL);

        $state = $this->cache()->readState();
        $verify = $this->cache()->verify();

        $this->assertSame('invalid', $state['status']);
        $this->assertSame('fail', $verify['status']);
        $this->assertSame('MARKETPLACE_ENTITLEMENT_CACHE_INVALID', $verify['code']);
    }

    public function test_persist_rejects_invalid_entitlement_rows(): void
    {
        $this->expectException(FoundryError::class);
        $this->expectExceptionMessage('Marketplace entitlement cache is invalid.');

        $this->cache()->persist([
            [
                'pack' => 'not a pack name',
                'type' => 'premium',
                'status' => 'granted',
            ],
        ]);
    }

    private function cache(): MarketplaceEntitlementCache
    {
        return new MarketplaceEntitlementCache(new Paths($this->project->root), $this->clock);
    }
}
