<?php

declare(strict_types=1);

namespace Foundry\CLI\Commands;

use Foundry\CLI\Command;
use Foundry\CLI\CommandContext;

final class InspectStateStoreCommand extends Command
{
    #[\Override]
    public function supportedSignatures(): array
    {
        return ['inspect state-store'];
    }

    #[\Override]
    public function matches(array $args): bool
    {
        return ($args[0] ?? null) === 'inspect' && ($args[1] ?? null) === 'state-store';
    }

    #[\Override]
    public function run(array $args, CommandContext $context): array
    {
        $payload = $context->sqliteStateStore()->inspectMetadata();
        $status = ((string) ($payload['status'] ?? 'ok')) === 'schema_invalid' ? 1 : 0;

        return [
            'status' => $status,
            'message' => $status === 0 ? 'State-store inspection completed.' : 'State-store inspection found schema issues.',
            'payload' => $payload,
        ];
    }
}
