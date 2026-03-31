<?php

declare(strict_types=1);

namespace Foundry\Packs;

use Foundry\Generate\Generator;

final readonly class PackGeneratorDefinition
{
    /**
     * @param array<int,string> $capabilities
     */
    public function __construct(
        public string $name,
        public Generator $generator,
        public array $capabilities = [],
        public int $priority = 50,
    ) {}
}
