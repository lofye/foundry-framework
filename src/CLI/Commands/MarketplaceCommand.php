<?php

declare(strict_types=1);

namespace Foundry\CLI\Commands;

use Foundry\CLI\Command;
use Foundry\CLI\CommandContext;
use Foundry\Marketplace\MarketplaceRepository;
use Foundry\Marketplace\MarketplaceVerifier;
use Foundry\Support\FoundryError;

final class MarketplaceCommand extends Command
{
    #[\Override]
    public function supportedSignatures(): array
    {
        return ['inspect marketplace', 'verify marketplace'];
    }

    #[\Override]
    public function matches(array $args): bool
    {
        return (($args[0] ?? null) === 'inspect' && ($args[1] ?? null) === 'marketplace')
            || (($args[0] ?? null) === 'verify' && ($args[1] ?? null) === 'marketplace');
    }

    #[\Override]
    public function run(array $args, CommandContext $context): array
    {
        $command = (string) ($args[0] ?? '');
        $repository = new MarketplaceRepository($context->paths());

        return match ($command) {
            'inspect' => [
                'status' => 0,
                'message' => 'Marketplace inspection completed.',
                'payload' => $repository->inspect(),
            ],
            'verify' => $this->verify($repository),
            default => throw new FoundryError('MARKETPLACE_COMMAND_INVALID', 'validation', ['args' => $args], 'Unsupported marketplace command.'),
        };
    }

    /**
     * @return array{status:int,message:string,payload:array<string,mixed>}
     */
    private function verify(MarketplaceRepository $repository): array
    {
        $payload = (new MarketplaceVerifier($repository))->verify();
        $status = ((string) ($payload['status'] ?? 'fail')) === 'pass' ? 0 : 1;

        return [
            'status' => $status,
            'message' => $status === 0 ? 'Marketplace verification passed.' : 'Marketplace verification failed.',
            'payload' => $payload,
        ];
    }
}
