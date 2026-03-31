<?php

declare(strict_types=1);

namespace Foundry\Generate;

final readonly class RegisteredGenerator
{
    /**
     * @param array<int,string> $capabilities
     */
    public function __construct(
        public string $id,
        public string $origin,
        public ?string $extension,
        public Generator $generator,
        public array $capabilities = [],
        public int $priority = 0,
    ) {}

    /**
     * @return array<string,mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'origin' => $this->origin,
            'extension' => $this->extension,
            'capabilities' => $this->capabilities,
            'priority' => $this->priority,
        ];
    }
}
