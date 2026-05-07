<?php

declare(strict_types=1);

namespace Foundry\Tests\Unit;

use Foundry\Marketplace\MarketplaceDeterministicPurchaseClient;
use Foundry\Support\FoundryError;
use PHPUnit\Framework\TestCase;

final class MarketplaceDeterministicPurchaseClientTest extends TestCase
{
    public function test_purchase_returns_pending_checkout_by_default(): void
    {
        $result = (new MarketplaceDeterministicPurchaseClient())->purchase('vendor/premium-pack', ['distribution' => 'premium'], ['id' => 'demo-user']);

        $this->assertSame('pending', $result['status']);
        $this->assertStringContainsString('https://marketplace.example/checkout/', (string) $result['checkout_url']);
    }

    public function test_purchase_can_return_success_entitlements(): void
    {
        $result = (new MarketplaceDeterministicPurchaseClient())->purchase('vendor/premium-pack-complete', ['distribution' => 'premium'], ['id' => 'demo-user']);

        $this->assertSame('success', $result['status']);
        $this->assertSame('vendor/premium-pack-complete', $result['entitlements'][0]['pack']);
    }

    public function test_purchase_throws_expected_failure_codes(): void
    {
        try {
            (new MarketplaceDeterministicPurchaseClient())->purchase('vendor/premium-pack-fail', ['distribution' => 'premium'], ['id' => 'demo-user']);
            self::fail('Expected purchase failure.');
        } catch (FoundryError $error) {
            $this->assertSame('MARKETPLACE_PURCHASE_FAILED', $error->errorCode);
        }

        try {
            (new MarketplaceDeterministicPurchaseClient())->purchase('vendor/premium-pack-unavailable', ['distribution' => 'premium'], ['id' => 'demo-user']);
            self::fail('Expected purchase unavailable failure.');
        } catch (FoundryError $error) {
            $this->assertSame('MARKETPLACE_PURCHASE_MARKETPLACE_UNAVAILABLE', $error->errorCode);
        }
    }
}
