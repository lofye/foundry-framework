<?php
declare(strict_types=1);

namespace Foundry\Compiler\Migration;

final readonly class SpecMigrationResult
{
    /**
     * @param array<int,array<string,mixed>> $changes
     * @param array<int,array<string,mixed>> $diagnostics
     */
    public function __construct(
        public bool $written,
        public array $changes,
        public array $diagnostics,
    ) {
    }

    /**
     * @return array<string,mixed>
     */
    public function toArray(): array
    {
        return [
            'written' => $this->written,
            'changes' => $this->changes,
            'diagnostics' => $this->diagnostics,
        ];
    }
}
