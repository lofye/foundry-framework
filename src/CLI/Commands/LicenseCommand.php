<?php

declare(strict_types=1);

namespace Foundry\CLI\Commands;

use Foundry\CLI\Command;
use Foundry\CLI\CommandContext;
use Foundry\Monetization\FeatureFlags;
use Foundry\Monetization\MonetizationService;
use Foundry\Support\FoundryError;

final class LicenseCommand extends Command
{
    /**
     * @var list<string>
     */
    private const LICENSE_COMMANDS = [
        'license status',
        'license activate [--key=<license-key>]',
        'license deactivate',
    ];

    #[\Override]
    public function supportedSignatures(): array
    {
        return ['license status', 'license activate', 'license deactivate'];
    }

    #[\Override]
    public function matches(array $args): bool
    {
        return ($args[0] ?? null) === 'license'
            && in_array($args[1] ?? null, ['status', 'activate', 'deactivate'], true);
    }

    #[\Override]
    public function run(array $args, CommandContext $context): array
    {
        $subcommand = (string) ($args[1] ?? '');
        $service = new MonetizationService();

        return match ($subcommand) {
            'status' => $this->result($context, $service->status()),
            'activate' => $this->activate($args, $context, $service),
            'deactivate' => $this->result($context, $service->deactivate()),
            default => throw new FoundryError(
                'CLI_LICENSE_SUBCOMMAND_NOT_FOUND',
                'not_found',
                ['subcommand' => $subcommand],
                'License subcommand not found.',
            ),
        };
    }

    /**
     * @param array<int,string> $args
     * @return array{status:int,payload:array<string,mixed>|null,message:string|null}
     */
    private function activate(array $args, CommandContext $context, MonetizationService $service): array
    {
        $licenseKey = '';

        foreach ($args as $index => $arg) {
            if (str_starts_with($arg, '--key=')) {
                $licenseKey = trim(substr($arg, strlen('--key=')));
                break;
            }

            if ($arg === '--key') {
                $licenseKey = trim((string) ($args[$index + 1] ?? ''));
                break;
            }
        }

        if ($licenseKey === '') {
            $licenseKey = trim((string) ($args[2] ?? ''));
        }

        if ($licenseKey === '') {
            throw new FoundryError(
                'LICENSE_KEY_REQUIRED',
                'validation',
                [],
                'A Foundry license key is required.',
            );
        }

        return $this->result($context, $service->activate($licenseKey));
    }

    /**
     * @param array<string,mixed> $license
     * @return array{status:int,payload:array<string,mixed>|null,message:string|null}
     */
    private function result(CommandContext $context, array $license): array
    {
        $payload = [
            'license' => $license,
            'commands' => self::LICENSE_COMMANDS,
        ];

        return [
            'status' => 0,
            'message' => $context->expectsJson() ? null : $this->renderStatus($license),
            'payload' => $context->expectsJson() ? $payload : null,
        ];
    }

    /**
     * @param array<string,mixed> $license
     */
    private function renderStatus(array $license): string
    {
        $lines = [(string) ($license['message'] ?? 'Foundry license status unavailable.')];
        $lines[] = 'Tier: ' . (string) ($license['tier'] ?? FeatureFlags::TIER_FREE);
        $lines[] = 'Source: ' . (string) ($license['source'] ?? 'none');
        $lines[] = 'License path: ' . (string) ($license['license_path'] ?? '');

        $fingerprint = trim((string) ($license['fingerprint'] ?? ''));
        if ($fingerprint !== '') {
            $lines[] = 'Fingerprint: ' . $fingerprint;
        }

        $features = array_values(array_map('strval', (array) ($license['feature_flags'] ?? $license['features'] ?? [])));
        if ($features !== []) {
            $lines[] = 'Feature flags: ' . implode(', ', $features);
        }

        $tracking = is_array($license['usage_tracking'] ?? null) ? $license['usage_tracking'] : [];
        $lines[] = 'Usage tracking: ' . (((bool) ($tracking['enabled'] ?? false)) ? 'enabled' : 'disabled');
        $lines[] = 'Commands: ' . implode(', ', self::LICENSE_COMMANDS);

        return implode(PHP_EOL, $lines);
    }
}
