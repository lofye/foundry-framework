<?php

declare(strict_types=1);

namespace Foundry\Tests\Unit;

use Foundry\Marketplace\MarketplaceDeterministicLicenseClient;
use Foundry\Support\Clock;
use Foundry\Support\FoundryError;
use PHPUnit\Framework\TestCase;

final class MarketplaceDeterministicLicenseClientTest extends TestCase
{
    public function test_activate_returns_deterministic_entitlement_payload(): void
    {
        $client = new MarketplaceDeterministicLicenseClient(new Clock(new \DateTimeImmutable('2026-01-01T00:00:00+00:00')));
        $payload = $client->activate('fpro-abcd-efgh-ijkl');

        $this->assertSame('vendor/premium-pack', $payload['entitlements'][0]['pack']);
        $this->assertSame('premium', $payload['entitlements'][0]['type']);
        $this->assertSame('granted', $payload['entitlements'][0]['status']);
        $this->assertSame('2026-01-01T00:00:00+00:00', $payload['entitlements'][0]['granted_at']);
    }

    public function test_activate_rejects_blank_or_whitespace_keys(): void
    {
        $client = new MarketplaceDeterministicLicenseClient();

        $this->expectException(FoundryError::class);
        $this->expectExceptionMessage('Marketplace license key is invalid.');
        $client->activate("  \n");
    }

    public function test_activate_rejects_structurally_invalid_keys(): void
    {
        $client = new MarketplaceDeterministicLicenseClient();

        $this->expectException(FoundryError::class);
        $this->expectExceptionMessage('Marketplace license key is invalid.');
        $client->activate('short');
    }

    public function test_activate_rejects_invalid_marker_keys(): void
    {
        $client = new MarketplaceDeterministicLicenseClient();

        $this->expectException(FoundryError::class);
        $this->expectExceptionMessage('Marketplace license key is invalid.');
        $client->activate('FPRO-INVALID-1234-5678');
    }

    public function test_activate_reports_activation_failure_marker(): void
    {
        $client = new MarketplaceDeterministicLicenseClient();

        $this->expectException(FoundryError::class);
        $this->expectExceptionMessage('Marketplace license activation failed.');
        $client->activate('FPRO-FAIL-1234-5678');
    }
}
