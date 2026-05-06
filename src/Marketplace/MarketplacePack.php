<?php

declare(strict_types=1);

namespace Foundry\Marketplace;

final readonly class MarketplacePack
{
    /**
     * @param list<MarketplacePackVersion> $versions
     * @param array<int,string> $tags
     */
    public function __construct(
        public string $name,
        public string $displayName,
        public string $description,
        public string $vendor,
        public string $latestVersion,
        public array $versions,
        public ?string $homepage,
        public ?string $license,
        public array $tags,
    ) {}

    /**
     * @return array<string,mixed>
     */
    public function summary(): array
    {
        return [
            'name' => $this->name,
            'display_name' => $this->displayName,
            'description' => $this->description,
            'vendor' => $this->vendor,
            'latest_version' => $this->latestVersion,
            'versions' => array_values(array_map(
                static fn(MarketplacePackVersion $version): string => $version->version,
                $this->versions,
            )),
            'metadata' => [
                'homepage' => $this->homepage,
                'license' => $this->license,
                'tags' => $this->tags,
            ],
        ];
    }

    /**
     * @return array<string,mixed>
     */
    public function detail(): array
    {
        return [
            'name' => $this->name,
            'display_name' => $this->displayName,
            'description' => $this->description,
            'vendor' => $this->vendor,
            'latest_version' => $this->latestVersion,
            'versions' => array_values(array_map(
                fn(MarketplacePackVersion $version): array => $version->detail($this->name),
                $this->versions,
            )),
            'metadata' => [
                'homepage' => $this->homepage,
                'license' => $this->license,
                'tags' => $this->tags,
            ],
        ];
    }
}

