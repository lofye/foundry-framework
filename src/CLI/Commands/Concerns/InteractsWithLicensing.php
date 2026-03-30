<?php

declare(strict_types=1);

namespace Foundry\CLI\Commands\Concerns;

use Foundry\Monetization\FeatureGate;
use Foundry\Monetization\LicenseStore;
use Foundry\Monetization\MonetizationService;

trait InteractsWithLicensing
{
    /**
     * @param array<int,string> $requiredFeatures
     * @return array<string,mixed>
     */
    protected function requireLicensedFeatures(string $command, array $requiredFeatures = []): array
    {
        return (new FeatureGate($this->licenseStore()))->require($command, $requiredFeatures);
    }

    /**
     * @return array<string,mixed>
     */
    protected function licenseStatus(): array
    {
        return $this->monetizationService()->status();
    }

    protected function licenseStore(): LicenseStore
    {
        return new LicenseStore();
    }

    protected function monetizationService(): MonetizationService
    {
        return new MonetizationService($this->licenseStore());
    }
}
