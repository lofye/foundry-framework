<?php

declare(strict_types=1);

namespace Foundry\Monetization;

final readonly class FeatureGate
{
    public function __construct(private LicenseStore $licenses) {}

    /**
     * @param array<int,string> $requiredFeatures
     * @return array<string,mixed>
     */
    public function require(string $command, array $requiredFeatures = []): array
    {
        return (new MonetizationService($this->licenses))->require($command, $requiredFeatures);
    }
}
