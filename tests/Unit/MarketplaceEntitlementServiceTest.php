<?php

declare(strict_types=1);

namespace Foundry\Tests\Unit;

use Foundry\Marketplace\MarketplaceEntitlementCache;
use Foundry\Marketplace\MarketplaceEntitlementService;
use Foundry\Marketplace\MarketplaceIdentityStore;
use Foundry\Marketplace\MarketplaceLicenseClient;
use Foundry\Support\Clock;
use Foundry\Support\FoundryError;
use Foundry\Support\Paths;
use Foundry\Tests\Fixtures\TempProject;
use PHPUnit\Framework\TestCase;

final class MarketplaceEntitlementServiceTest extends TestCase
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

    public function test_activate_license_persists_entitlements_and_redacts_key(): void
    {
        $service = $this->service(new class implements MarketplaceLicenseClient {
            public function activate(string $licenseKey, array $context = []): array
            {
                return [
                    'entitlements' => [[
                        'pack' => 'vendor/premium-pack',
                        'type' => 'premium',
                        'status' => 'granted',
                        'expires_at' => null,
                        'source' => 'marketplace',
                        'granted_at' => '2026-01-01T00:00:00Z',
                    ]],
                ];
            }
        });

        $result = $service->activateLicense('FPRO-ABCD-EFGH-IJKL');

        $this->assertSame('ok', $result['status']);
        $this->assertTrue($result['activated']);
        $this->assertSame('***IJKL', $result['license']['hint']);
        $this->assertCount(1, $result['entitlements']);
        $this->assertSame('vendor/premium-pack', $result['entitlements'][0]['pack']);

        $list = $service->listEntitlements();
        $this->assertCount(1, $list['entitlements']);
    }

    public function test_activate_license_requires_auth_for_account_scoped_keys(): void
    {
        $service = $this->service();

        $this->expectException(FoundryError::class);
        $this->expectExceptionMessage('Marketplace authentication is required.');
        $service->activateLicense('acct_premium_12345');
    }

    public function test_activate_license_rejects_invalid_keys(): void
    {
        $service = $this->service();

        $this->expectException(FoundryError::class);
        $this->expectExceptionMessage('Marketplace license key is invalid.');
        $service->activateLicense('bad key');
    }

    public function test_list_entitlements_fails_closed_when_cache_is_invalid(): void
    {
        $path = $this->project->root . '/.foundry/marketplace/entitlements.json';
        if (!is_dir(dirname($path))) {
            mkdir(dirname($path), 0777, true);
        }
        file_put_contents($path, '{bad-json}' . PHP_EOL);

        $this->expectException(FoundryError::class);
        $this->expectExceptionMessage('Marketplace entitlement cache is invalid.');
        $this->service()->listEntitlements();
    }

    private function service(?MarketplaceLicenseClient $client = null): MarketplaceEntitlementService
    {
        $paths = new Paths($this->project->root);

        return new MarketplaceEntitlementService(
            new MarketplaceEntitlementCache($paths, $this->clock),
            new MarketplaceIdentityStore($paths, $this->clock),
            $client,
        );
    }
}
