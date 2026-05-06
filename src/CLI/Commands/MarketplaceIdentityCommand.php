<?php

declare(strict_types=1);

namespace Foundry\CLI\Commands;

use Foundry\CLI\Command;
use Foundry\CLI\CommandContext;
use Foundry\Marketplace\MarketplaceAuthService;
use Foundry\Marketplace\MarketplaceIdentityStore;

final class MarketplaceIdentityCommand extends Command
{
    #[\Override]
    public function supportedSignatures(): array
    {
        return ['login', 'logout', 'whoami'];
    }

    #[\Override]
    public function matches(array $args): bool
    {
        return in_array((string) ($args[0] ?? ''), ['login', 'logout', 'whoami'], true);
    }

    #[\Override]
    public function run(array $args, CommandContext $context): array
    {
        $command = (string) ($args[0] ?? '');
        $service = new MarketplaceAuthService(new MarketplaceIdentityStore($context->paths()));

        return match ($command) {
            'login' => $this->login($args, $context, $service),
            'logout' => $this->result($context, $service->logout(), 'Marketplace identity cleared.'),
            'whoami' => $this->result($context, $service->whoami(), 'Marketplace identity inspected.'),
            default => [
                'status' => 1,
                'message' => 'Marketplace identity command not found.',
                'payload' => null,
            ],
        };
    }

    /**
     * @param array<int,string> $args
     * @return array{status:int,payload:array<string,mixed>|null,message:string|null}
     */
    private function login(array $args, CommandContext $context, MarketplaceAuthService $service): array
    {
        $userId = $this->optionValue($args, '--user');
        $token = $this->optionValue($args, '--token');
        $payload = $service->login($userId, $token);

        return $this->result($context, $payload, 'Marketplace login completed.');
    }

    /**
     * @param array<string,mixed> $payload
     * @return array{status:int,payload:array<string,mixed>|null,message:string|null}
     */
    private function result(CommandContext $context, array $payload, string $message): array
    {
        if ($context->expectsJson()) {
            return [
                'status' => 0,
                'message' => null,
                'payload' => $payload,
            ];
        }

        return [
            'status' => 0,
            'message' => $this->renderMessage($message, $payload),
            'payload' => null,
        ];
    }

    /**
     * @param array<int,string> $args
     */
    private function optionValue(array $args, string $name): string
    {
        foreach ($args as $index => $arg) {
            if (str_starts_with($arg, $name . '=')) {
                return trim(substr($arg, strlen($name . '=')));
            }

            if ($arg === $name) {
                return trim((string) ($args[$index + 1] ?? ''));
            }
        }

        return '';
    }

    /**
     * @param array<string,mixed> $payload
     */
    private function renderMessage(string $headline, array $payload): string
    {
        $authenticated = ((bool) ($payload['authenticated'] ?? false)) ? 'yes' : 'no';
        $user = $payload['identity']['user_id'] ?? null;
        $tokenHint = $payload['identity']['token_hint'] ?? null;
        $path = (string) ($payload['storage']['path'] ?? '.foundry/marketplace/identity.json');

        $lines = [
            $headline,
            'Authenticated: ' . $authenticated,
            'User: ' . ($user === null ? 'none' : (string) $user),
            'Token: ' . ($tokenHint === null ? 'none' : (string) $tokenHint),
            'Identity file: ' . $path,
        ];

        return implode(PHP_EOL, $lines);
    }
}

