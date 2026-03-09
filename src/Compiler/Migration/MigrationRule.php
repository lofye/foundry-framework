<?php
declare(strict_types=1);

namespace Foundry\Compiler\Migration;

interface MigrationRule
{
    public function id(): string;

    public function description(): string;

    public function sourceType(): string;

    /**
     * @param array<string,mixed> $document
     */
    public function applies(string $path, array $document): bool;

    /**
     * @param array<string,mixed> $document
     * @return array<string,mixed>
     */
    public function migrate(string $path, array $document): array;
}
