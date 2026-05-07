<?php

declare(strict_types=1);

namespace Foundry\Tests\Unit;

use Foundry\Marketplace\MarketplaceEntitlementCache;
use Foundry\Marketplace\PackEntitlementResolver;
use Foundry\Support\Clock;
use Foundry\Support\FoundryError;
use Foundry\Support\Paths;
use Foundry\Tests\Fixtures\TempProject;
use PHPUnit\Framework\TestCase;

final class PackEntitlementResolverTest extends TestCase
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

    public function test_free_distribution_resolves_as_not_required(): void
    {
        $resolver = $this->resolver();

        $result = $resolver->resolve('vendor/free-pack', [
            'distribution' => 'free',
            'entitlement_required' => false,
        ]);

        $this->assertFalse($result['required']);
        $this->assertSame('granted', $result['status']);
        $this->assertSame('free', $result['tier']);
    }

    public function test_premium_distribution_requires_granted_entitlement(): void
    {
        $resolver = $this->resolver();

        $missing = $resolver->resolve('vendor/premium-pack', [
            'distribution' => 'premium',
            'entitlement_required' => true,
        ]);
        $this->assertTrue($missing['required']);
        $this->assertSame('missing', $missing['status']);

        $this->cache()->persist([
            [
                'pack' => 'vendor/premium-pack',
                'type' => 'premium',
                'status' => 'granted',
                'expires_at' => null,
                'source' => 'marketplace',
                'granted_at' => '2026-01-01T00:00:00Z',
            ],
        ]);

        $granted = $resolver->resolve('vendor/premium-pack', [
            'distribution' => 'premium',
            'entitlement_required' => true,
        ]);

        $this->assertSame('granted', $granted['status']);
        $this->assertSame('marketplace', $granted['source']);
    }

    public function test_invalid_distribution_metadata_is_rejected(): void
    {
        $this->expectException(FoundryError::class);
        $this->expectExceptionMessage('Marketplace distribution metadata is invalid.');

        $this->resolver()->resolve('vendor/example-pack', []);
    }

    public function test_distribution_metadata_enforces_free_and_premium_entitlement_rules(): void
    {
        try {
            $this->resolver()->validateDistributionMetadata([
                'distribution' => 'free',
                'entitlement_required' => true,
            ], 'vendor/free-pack');
            self::fail('Expected free distribution entitlement rejection.');
        } catch (FoundryError $error) {
            $this->assertSame('MARKETPLACE_DISTRIBUTION_METADATA_INVALID', $error->errorCode);
            $this->assertStringContainsString('Free packs cannot require entitlements.', $error->getMessage());
        }

        try {
            $this->resolver()->validateDistributionMetadata([
                'distribution' => 'premium',
                'entitlement_required' => false,
            ], 'vendor/premium-pack');
            self::fail('Expected premium entitlement requirement rejection.');
        } catch (FoundryError $error) {
            $this->assertSame('MARKETPLACE_DISTRIBUTION_METADATA_INVALID', $error->errorCode);
            $this->assertStringContainsString('Premium packs must require entitlements.', $error->getMessage());
        }
    }

    public function test_distribution_metadata_validates_price_shape(): void
    {
        try {
            $this->resolver()->validateDistributionMetadata([
                'distribution' => 'licensed',
                'entitlement_required' => true,
                'price' => ['currency' => 'cad', 'amount' => '49'],
            ], 'vendor/licensed-pack');
            self::fail('Expected invalid price metadata rejection.');
        } catch (FoundryError $error) {
            $this->assertSame('MARKETPLACE_DISTRIBUTION_METADATA_INVALID', $error->errorCode);
        }

        $metadata = $this->resolver()->validateDistributionMetadata([
            'distribution' => 'licensed',
            'entitlement_required' => true,
            'price' => ['currency' => 'CAD', 'amount' => '49.00'],
        ], 'vendor/licensed-pack');

        $this->assertSame(['currency' => 'CAD', 'amount' => '49.00'], $metadata['price']);
    }

    public function test_resolve_normalizes_unknown_status_and_offline_flag(): void
    {
        $this->cache()->persist([
            [
                'pack' => 'vendor/premium-pack',
                'type' => 'premium',
                'status' => 'unknown',
                'expires_at' => null,
                'source' => '',
                'granted_at' => '2026-01-01T00:00:00Z',
            ],
        ]);

        $result = $this->resolver()->resolve('vendor/premium-pack', [
            'distribution' => 'premium',
            'entitlement_required' => true,
        ], true);

        $this->assertSame('unknown', $result['status']);
        $this->assertTrue($result['offline']);
        $this->assertSame('marketplace', $result['source']);
    }

    public function test_resolve_marks_expired_premium_grant_as_expired(): void
    {
        $this->cache()->persist([
            [
                'pack' => 'vendor/premium-pack',
                'type' => 'premium',
                'status' => 'granted',
                'expires_at' => '2025-12-31T00:00:00Z',
                'source' => 'marketplace',
                'granted_at' => '2026-01-01T00:00:00Z',
            ],
        ]);

        $result = $this->resolver()->resolve('vendor/premium-pack', [
            'distribution' => 'premium',
            'entitlement_required' => true,
        ]);

        $this->assertSame('expired', $result['status']);
        $this->assertSame('2025-12-31T00:00:00Z', $result['expires_at']);
    }

    public function test_invalid_cache_fails_closed(): void
    {
        $path = $this->project->root . '/.foundry/marketplace/entitlements.json';
        if (!is_dir(dirname($path))) {
            mkdir(dirname($path), 0777, true);
        }
        file_put_contents($path, '{bad-json}' . PHP_EOL);

        $this->expectException(FoundryError::class);
        $this->expectExceptionMessage('Marketplace entitlement cache is invalid.');

        $this->resolver()->resolve('vendor/premium-pack', [
            'distribution' => 'premium',
            'entitlement_required' => true,
        ]);
    }

    private function cache(): MarketplaceEntitlementCache
    {
        return new MarketplaceEntitlementCache(new Paths($this->project->root), $this->clock);
    }

    private function resolver(): PackEntitlementResolver
    {
        return new PackEntitlementResolver($this->cache());
    }
}
