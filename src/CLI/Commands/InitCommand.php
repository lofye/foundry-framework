<?php

declare(strict_types=1);

namespace Foundry\CLI\Commands;

use Foundry\CLI\Command;
use Foundry\CLI\CommandContext;
use Foundry\UX\FirstRunService;

final class InitCommand extends Command
{
    public function __construct(private readonly ?FirstRunService $firstRun = null) {}

    #[\Override]
    public function supportedSignatures(): array
    {
        return ['init'];
    }

    #[\Override]
    public function matches(array $args): bool
    {
        return ($args[0] ?? null) === 'init' && ($args[1] ?? null) !== 'app';
    }

    #[\Override]
    public function run(array $args, CommandContext $context): array
    {
        return ($this->firstRun ?? new FirstRunService())->run($context, $args);
    }
}
