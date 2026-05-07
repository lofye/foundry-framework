<?php

declare(strict_types=1);

namespace Foundry\MCP\Handlers;

use Foundry\MCP\CliReadBridge;
use Foundry\MCP\ToolHandler;
use Foundry\Support\FoundryError;

final class GeneratePlanHandler implements ToolHandler
{
    public function __construct(private readonly CliReadBridge $bridge) {}

    public function handle(array $input): array
    {
        $intent = trim((string) ($input['intent'] ?? ''));
        if ($intent === '') {
            throw new FoundryError('MCP_INPUT_INVALID', 'validation', ['tool' => 'generate_plan'], 'Input `intent` is required.');
        }

        $mode = trim((string) ($input['mode'] ?? 'new'));
        if (!in_array($mode, ['new', 'modify', 'repair'], true)) {
            throw new FoundryError('MCP_INPUT_INVALID', 'validation', ['tool' => 'generate_plan', 'mode' => $mode], 'Input `mode` must be new, modify, or repair.');
        }

        $args = ['generate', $intent, '--mode=' . $mode, '--dry-run'];
        $target = trim((string) ($input['target'] ?? ''));
        if ($target !== '') {
            $args[] = '--target=' . $target;
        }

        $packs = $this->packsFromInput($input['packs'] ?? null);
        if ($packs !== []) {
            $args[] = '--packs=' . implode(',', $packs);
        }

        try {
            $payload = $this->bridge->run($args);
        } catch (FoundryError $error) {
            $code = (string) $error->errorCode;
            if (in_array($code, [
                'GENERATE_PACK_INSTALL_REQUIRED',
                'MISSING_ENTITLEMENT',
                'EXPIRED_ENTITLEMENT',
                'UNKNOWN_ENTITLEMENT',
                'ENTITLEMENT_STATE_CHANGED',
                'ENTITLEMENT_VALIDATION_FAILED',
                'MARKETPLACE_PACK_NOT_AVAILABLE',
            ], true)) {
                $details = is_array($error->details ?? null) ? $error->details : [];
                $cliPayload = is_array($details['payload'] ?? null) ? $details['payload'] : [];
                $cliError = is_array($cliPayload['error'] ?? null) ? $cliPayload['error'] : [];
                $errorDetails = is_array($cliError['details'] ?? null) ? $cliError['details'] : [];

                return [
                    'status' => 'blocked',
                    'plan_id' => null,
                    'execution_state' => (string) ($errorDetails['execution_state'] ?? $details['execution_state'] ?? 'invalid'),
                    'entitlements' => is_array($errorDetails['entitlements'] ?? null)
                        ? $errorDetails['entitlements']
                        : (is_array($details['entitlements'] ?? null) ? $details['entitlements'] : []),
                    'pack_requirements' => array_values(array_filter(
                        (array) ($errorDetails['pack_requirements'] ?? $details['pack_requirements'] ?? []),
                        'is_array',
                    )),
                    'plan' => [],
                    'error' => [
                        'code' => $code,
                        'message' => $error->getMessage(),
                        'details' => $errorDetails !== [] ? $errorDetails : $details,
                    ],
                ];
            }

            throw $error;
        }

        return [
            'status' => ((bool) ($payload['ok'] ?? false) || !isset($payload['error'])) ? 'planned' : 'blocked',
            'plan_id' => $payload['plan_record']['plan_id'] ?? null,
            'execution_state' => (string) ($payload['execution_state'] ?? 'executable'),
            'entitlements' => is_array($payload['entitlements'] ?? null) ? $payload['entitlements'] : [],
            'pack_requirements' => array_values(array_filter((array) ($payload['pack_requirements'] ?? []), 'is_array')),
            'plan' => is_array($payload['plan'] ?? null) ? $payload['plan'] : [],
            'error' => is_array($payload['error'] ?? null) ? $payload['error'] : null,
        ];
    }

    /**
     * @return array<int,string>
     */
    private function packsFromInput(mixed $value): array
    {
        if (is_string($value)) {
            $rows = array_map('trim', explode(',', $value));
        } elseif (is_array($value)) {
            $rows = array_map(static fn(mixed $pack): string => trim((string) $pack), $value);
        } else {
            return [];
        }

        $rows = array_values(array_filter($rows, static fn(string $row): bool => $row !== ''));
        $rows = array_values(array_unique($rows));
        sort($rows);

        return $rows;
    }
}
