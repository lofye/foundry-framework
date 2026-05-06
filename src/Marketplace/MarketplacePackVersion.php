<?php

declare(strict_types=1);

namespace Foundry\Marketplace;

final readonly class MarketplacePackVersion
{
    /**
     * @param array<int,string> $tags
     */
    public function __construct(
        public string $version,
        public string $requiresFoundry,
        public string $artifact,
        public string $sha256,
        public string $publishedAt,
        public ?string $homepage,
        public ?string $license,
        public array $tags,
    ) {}

    /**
     * @return array<string,mixed>
     */
    public function detail(string $packName): array
    {
        return [
            'version' => $this->version,
            'requires_foundry' => $this->requiresFoundry,
            'sha256' => $this->sha256,
            'published_at' => $this->publishedAt,
            'download_url' => '/packs/' . $packName . '/' . $this->version . '/download',
            'metadata' => [
                'homepage' => $this->homepage,
                'license' => $this->license,
                'tags' => $this->tags,
            ],
        ];
    }
}

