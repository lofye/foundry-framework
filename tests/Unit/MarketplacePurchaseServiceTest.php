<?php

declare(strict_types=1);

namespace Foundry\Tests\Unit;

use Foundry\Marketplace\MarketplaceEntitlementCache;
use Foundry\Marketplace\MarketplaceEntitlementService;
use Foundry\Marketplace\MarketplaceIdentityStore;
use Foundry\Marketplace\MarketplacePurchaseClient;
use Foundry\Marketplace\MarketplacePurchaseService;
use Foundry\Packs\HostedPackRegistry;
use Foundry\Support\Clock;
use Foundry\Support\FoundryError;
use Foundry\Support\Paths;
use Foundry\Tests\Fixtures\TempProject;
use PHPUnit\Framework\TestCase;

final class MarketplacePurchaseServiceTest extends TestCase
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

    public function test_purchase_returns_not_purchasable_for_free_pack(): void
    {
        $service = $this->serviceWithRegistry([
            $this->registryRow('vendor/free-pack', 'free', false),
        ]);

        $result = $service->purchase('vendor/free-pack');

        $this->assertSame('not_purchasable', $result['status']);
        $this->assertSame('MARKETPLACE_PURCHASE_PACK_NOT_PURCHASABLE', $result['code']);
    }

    public function test_purchase_rejects_invalid_pack_names(): void
    {
        $service = $this->serviceWithRegistry([]);

        try {
            $service->purchase('invalid pack');
            self::fail('Expected invalid pack purchase to fail.');
        } catch (FoundryError $error) {
            $this->assertSame('MARKETPLACE_PURCHASE_PACK_NOT_FOUND', $error->errorCode);
        }
    }

    public function test_purchase_requires_auth_for_paid_pack(): void
    {
        $service = $this->serviceWithRegistry([
            $this->registryRow('vendor/premium-pack', 'premium', true),
        ]);

        $this->expectException(FoundryError::class);
        $this->expectExceptionMessage('Marketplace authentication is required for purchases.');
        $service->purchase('vendor/premium-pack');
    }

    public function test_purchase_returns_already_entitled_when_cache_has_grant(): void
    {
        $service = $this->serviceWithRegistry([
            $this->registryRow('vendor/premium-pack', 'premium', true),
        ]);
        $this->login();
        $this->cache()->persist([[
            'pack' => 'vendor/premium-pack',
            'type' => 'premium',
            'status' => 'granted',
            'expires_at' => null,
            'source' => 'marketplace',
            'granted_at' => '2026-01-01T00:00:00Z',
        ]]);

        $result = $service->purchase('vendor/premium-pack');

        $this->assertSame('already_entitled', $result['status']);
        $this->assertSame('MARKETPLACE_PURCHASE_ALREADY_ENTITLED', $result['code']);
    }

    public function test_purchase_pending_returns_checkout_handoff(): void
    {
        $service = $this->serviceWithRegistry(
            [$this->registryRow('vendor/premium-pack', 'premium', true)],
            new class implements MarketplacePurchaseClient {
                public function purchase(string $pack, array $metadata, ?array $identity): array
                {
                    return [
                        'status' => 'pending',
                        'checkout_url' => 'https://marketplace.example/checkout/session_123',
                    ];
                }
            },
        );
        $this->login();

        $result = $service->purchase('vendor/premium-pack');

        $this->assertSame('pending', $result['status']);
        $this->assertSame('https://marketplace.example/checkout/session_123', $result['checkout_url']);
        $this->assertFalse($result['entitlement_refreshed']);
    }

    public function test_purchase_success_persists_entitlements(): void
    {
        $service = $this->serviceWithRegistry(
            [$this->registryRow('vendor/premium-pack-complete', 'premium', true)],
            new class implements MarketplacePurchaseClient {
                public function purchase(string $pack, array $metadata, ?array $identity): array
                {
                    return [
                        'status' => 'success',
                        'entitlements' => [[
                            'pack' => $pack,
                            'type' => 'premium',
                            'status' => 'granted',
                            'expires_at' => null,
                            'source' => 'marketplace',
                            'granted_at' => '2026-01-01T00:00:00Z',
                        ]],
                    ];
                }
            },
        );
        $this->login();

        $result = $service->purchase('vendor/premium-pack-complete');

        $this->assertSame('success', $result['status']);
        $this->assertTrue($result['entitlement_refreshed']);
        $this->assertSame('vendor/premium-pack-complete', $result['entitlements'][0]['pack']);
        $this->assertCount(1, $this->cache()->entitlements());
    }

    public function test_purchase_returns_partial_when_entitlement_refresh_fails(): void
    {
        $service = $this->serviceWithRegistry(
            [$this->registryRow('vendor/premium-pack-complete', 'premium', true)],
            new class implements MarketplacePurchaseClient {
                public function purchase(string $pack, array $metadata, ?array $identity): array
                {
                    return [
                        'status' => 'success',
                        'entitlements' => [['pack' => 'invalid pack', 'type' => 'premium', 'status' => 'granted']],
                    ];
                }
            },
        );
        $this->login();

        $result = $service->purchase('vendor/premium-pack-complete');

        $this->assertSame('partial', $result['status']);
        $this->assertSame('MARKETPLACE_PURCHASE_ENTITLEMENT_REFRESH_FAILED', $result['code']);
    }

    public function test_purchase_pending_without_checkout_url_fails(): void
    {
        $service = $this->serviceWithRegistry(
            [$this->registryRow('vendor/premium-pack', 'premium', true)],
            new class implements MarketplacePurchaseClient {
                public function purchase(string $pack, array $metadata, ?array $identity): array
                {
                    return ['status' => 'pending'];
                }
            },
        );
        $this->login();

        try {
            $service->purchase('vendor/premium-pack');
            self::fail('Expected missing checkout URL to fail.');
        } catch (FoundryError $error) {
            $this->assertSame('MARKETPLACE_PURCHASE_FAILED', $error->errorCode);
        }
    }

    public function test_purchase_success_requires_entitlements_array(): void
    {
        $service = $this->serviceWithRegistry(
            [$this->registryRow('vendor/premium-pack', 'premium', true)],
            new class implements MarketplacePurchaseClient {
                public function purchase(string $pack, array $metadata, ?array $identity): array
                {
                    return [
                        'status' => 'success',
                        'entitlements' => 'invalid',
                    ];
                }
            },
        );
        $this->login();

        try {
            $service->purchase('vendor/premium-pack');
            self::fail('Expected invalid entitlements payload to fail.');
        } catch (FoundryError $error) {
            $this->assertSame('MARKETPLACE_PURCHASE_FAILED', $error->errorCode);
        }
    }

    public function test_purchase_unknown_status_fails(): void
    {
        $service = $this->serviceWithRegistry(
            [$this->registryRow('vendor/premium-pack', 'premium', true)],
            new class implements MarketplacePurchaseClient {
                public function purchase(string $pack, array $metadata, ?array $identity): array
                {
                    return ['status' => 'unknown'];
                }
            },
        );
        $this->login();

        try {
            $service->purchase('vendor/premium-pack');
            self::fail('Expected unknown status purchase to fail.');
        } catch (FoundryError $error) {
            $this->assertSame('MARKETPLACE_PURCHASE_FAILED', $error->errorCode);
        }
    }

    public function test_purchase_maps_pack_not_found_and_unavailable_errors(): void
    {
        $missing = $this->serviceWithRegistry([]);
        $this->login();

        try {
            $missing->purchase('vendor/missing-pack');
            self::fail('Expected missing pack error.');
        } catch (FoundryError $error) {
            $this->assertSame('MARKETPLACE_PURCHASE_PACK_NOT_FOUND', $error->errorCode);
        }

        $unavailableRegistry = new HostedPackRegistry(
            new Paths($this->project->root),
            static fn(string $url): string => throw new \RuntimeException('offline'),
            'https://registry.example/packs',
        );
        $service = new MarketplacePurchaseService(
            new Paths($this->project->root),
            $unavailableRegistry,
            $this->identityStore(),
            $this->entitlementService(),
            null,
        );

        try {
            $service->purchase('vendor/premium-pack');
            self::fail('Expected unavailable purchase error.');
        } catch (FoundryError $error) {
            $this->assertSame('MARKETPLACE_PURCHASE_MARKETPLACE_UNAVAILABLE', $error->errorCode);
        }
    }

    public function test_purchase_maps_unexpected_registry_failures_to_generic_failure(): void
    {
        $registry = new HostedPackRegistry(
            new Paths($this->project->root),
            static fn(string $url): string => '{"invalid":"shape"}',
            'https://registry.example/packs',
        );
        $service = new MarketplacePurchaseService(
            new Paths($this->project->root),
            $registry,
            $this->identityStore(),
            $this->entitlementService(),
            null,
        );

        try {
            $service->purchase('vendor/premium-pack');
            self::fail('Expected invalid registry payload to fail.');
        } catch (FoundryError $error) {
            $this->assertSame('MARKETPLACE_PURCHASE_FAILED', $error->errorCode);
        }
    }

    public function test_capability_inspect_and_verify_contracts_are_deterministic(): void
    {
        $service = $this->serviceWithRegistry([$this->registryRow('vendor/free-pack', 'free', false)]);

        $inspect = $service->inspectCapability();
        $verify = $service->verifyCapability();

        $this->assertSame(
            [
                'enabled' => true,
                'client' => 'deterministic',
                'requires_auth_for_paid' => true,
                'supports_browser_handoff' => true,
                'live_mode_required' => false,
            ],
            $inspect,
        );
        $this->assertSame('pass', $verify['status']);
        $this->assertSame([], $verify['errors']);
    }

    private function serviceWithRegistry(array $rows, ?MarketplacePurchaseClient $client = null): MarketplacePurchaseService
    {
        $registry = new HostedPackRegistry(
            new Paths($this->project->root),
            static fn(string $url): string => json_encode($rows, JSON_THROW_ON_ERROR),
            'https://registry.example/packs',
        );

        return new MarketplacePurchaseService(
            new Paths($this->project->root),
            $registry,
            $this->identityStore(),
            $this->entitlementService(),
            $client,
        );
    }

    /**
     * @return array<string,mixed>
     */
    private function registryRow(string $name, string $distribution, bool $required): array
    {
        return [
            'name' => $name,
            'version' => '1.0.0',
            'description' => 'Marketplace pack',
            'download_url' => 'https://downloads.example/' . str_replace('/', '-', $name) . '.zip',
            'checksum' => str_repeat('a', 64),
            'signature' => null,
            'verified' => true,
            'distribution' => $distribution,
            'entitlement_required' => $required,
            'price' => $distribution === 'free' ? null : ['currency' => 'CAD', 'amount' => '49.00'],
        ];
    }

    private function login(): void
    {
        $this->identityStore()->write([
            'token_type' => 'bearer',
            'access_token' => 'token_demo_1234',
            'expires_at' => null,
            'user' => [
                'id' => 'demo-user',
                'email' => 'demo@example.com',
                'name' => null,
                'created_at' => '2026-01-01T00:00:00Z',
            ],
        ]);
    }

    private function cache(): MarketplaceEntitlementCache
    {
        return new MarketplaceEntitlementCache(new Paths($this->project->root), new Clock(new \DateTimeImmutable('2026-01-01T00:00:00+00:00')));
    }

    private function identityStore(): MarketplaceIdentityStore
    {
        return new MarketplaceIdentityStore(new Paths($this->project->root), new Clock(new \DateTimeImmutable('2026-01-01T00:00:00+00:00')));
    }

    private function entitlementService(): MarketplaceEntitlementService
    {
        return new MarketplaceEntitlementService($this->cache(), $this->identityStore());
    }
}
