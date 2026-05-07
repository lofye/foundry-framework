<?php

declare(strict_types=1);

namespace Foundry\CLI\Commands;

use Foundry\CLI\Command;
use Foundry\CLI\CommandContext;
use Foundry\Marketplace\MarketplaceAuthService;
use Foundry\Marketplace\MarketplaceEntitlementCache;
use Foundry\Marketplace\MarketplaceEntitlementService;
use Foundry\Marketplace\MarketplaceIdentityStore;

final class MarketplaceIdentityCommand extends Command
{
    #[\Override]
    public function supportedSignatures(): array
    {
        return ['login', 'logout', 'whoami', 'entitlements'];
    }

    #[\Override]
    public function matches(array $args): bool
    {
        return in_array((string) ($args[0] ?? ''), ['login', 'logout', 'whoami', 'entitlements'], true);
    }

    #[\Override]
    public function run(array $args, CommandContext $context): array
    {
        $command = (string) ($args[0] ?? '');
        $store = new MarketplaceIdentityStore($context->paths());
        $service = new MarketplaceAuthService($store);
        $entitlements = new MarketplaceEntitlementService(
            new MarketplaceEntitlementCache($context->paths()),
            $store,
        );

        return match ($command) {
            'login' => $this->login($args, $context, $service),
            'logout' => $this->result($context, $service->logout(), 'Marketplace identity cleared.'),
            'whoami' => $this->result($context, $service->whoami(), 'Marketplace identity inspected.'),
            'entitlements' => $this->entitlements($context, $entitlements),
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
        $email = $this->optionValue($args, '--email');
        $name = $this->optionValue($args, '--name');
        $expiresAt = $this->optionValue($args, '--expires-at');
        $payload = $service->login(
            $userId,
            $token,
            $email === '' ? null : $email,
            $name === '' ? null : $name,
            $expiresAt === '' ? null : $expiresAt,
        );

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
     * @return array{status:int,payload:array<string,mixed>|null,message:string|null}
     */
    private function entitlements(CommandContext $context, MarketplaceEntitlementService $service): array
    {
        $payload = $service->listEntitlements();
        if ($context->expectsJson()) {
            return [
                'status' => 0,
                'message' => null,
                'payload' => $payload,
            ];
        }

        $lines = ['Marketplace entitlements:'];
        foreach ((array) ($payload['entitlements'] ?? []) as $entitlement) {
            if (!is_array($entitlement)) {
                continue;
            }

            $lines[] = '- ' . (string) ($entitlement['pack'] ?? '')
                . ' [' . (string) ($entitlement['type'] ?? '') . ']'
                . ' status=' . (string) ($entitlement['status'] ?? '')
                . ' expires=' . ((string) (($entitlement['expires_at'] ?? null) ?? 'never'));
        }

        if (count($lines) === 1) {
            $lines[] = '- none';
        }

        return [
            'status' => 0,
            'message' => implode(PHP_EOL, $lines),
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
        $user = $payload['user']['id'] ?? null;
        $email = $payload['user']['email'] ?? null;
        $reason = isset($payload['reason']) ? (string) $payload['reason'] : null;

        $lines = [
            $headline,
            'Authenticated: ' . $authenticated,
            'User: ' . ($user === null ? 'none' : (string) $user),
            'Email: ' . ($email === null ? 'none' : (string) $email),
        ];
        if ($reason !== null && $reason !== '') {
            $lines[] = 'Reason: ' . $reason;
        }

        return implode(PHP_EOL, $lines);
    }
}
