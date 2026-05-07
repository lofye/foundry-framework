<?php

declare(strict_types=1);

namespace Foundry\MCP\Handlers;

use Foundry\MCP\CliReadBridge;
use Foundry\MCP\ToolHandler;
use Foundry\Support\FoundryError;

final class GenerateApplyHandler implements ToolHandler
{
    public function __construct(private readonly CliReadBridge $bridge) {}

    public function handle(array $input): array
    {
        $planId = trim((string) ($input['plan_id'] ?? ''));
        if ($planId === '') {
            throw new FoundryError('MCP_INPUT_INVALID', 'validation', ['tool' => 'generate_apply'], 'Input `plan_id` is required.');
        }

        $strict = ($input['strict'] ?? true) === true;
        $args = ['plan:replay', $planId, '--dry-run'];
        if ($strict) {
            $args[] = '--strict';
        }

        try {
            $preflight = $this->bridge->run($args);
        } catch (FoundryError $error) {
            $blocking = $this->blockingPayload($planId, $error);
            if ($blocking !== null) {
                return $blocking;
            }

            throw $error;
        }

        $applyArgs = ['plan:replay', $planId];
        if ($strict) {
            $applyArgs[] = '--strict';
        }

        try {
            $applied = $this->bridge->run($applyArgs);
        } catch (FoundryError $error) {
            $blocking = $this->blockingPayload($planId, $error);
            if ($blocking !== null) {
                return $blocking;
            }

            throw $error;
        }

        return [
            'status' => 'applied',
            'plan_id' => $planId,
            'preflight' => [
                'execution_state' => (string) ($preflight['execution_state'] ?? 'executable'),
                'entitlements' => is_array($preflight['entitlements'] ?? null) ? $preflight['entitlements'] : [],
            ],
            'result' => $applied,
        ];
    }

    /**
     * @return array<string,mixed>|null
     */
    private function blockingPayload(string $planId, FoundryError $error): ?array
    {
        $code = (string) $error->errorCode;
        if (!in_array($code, [
            'MISSING_ENTITLEMENT',
            'EXPIRED_ENTITLEMENT',
            'UNKNOWN_ENTITLEMENT',
            'ENTITLEMENT_STATE_CHANGED',
            'ENTITLEMENT_VALIDATION_FAILED',
            'MARKETPLACE_PACK_NOT_AVAILABLE',
        ], true)) {
            return null;
        }

        $details = is_array($error->details ?? null) ? $error->details : [];
        $cliPayload = is_array($details['payload'] ?? null) ? $details['payload'] : [];
        $cliError = is_array($cliPayload['error'] ?? null) ? $cliPayload['error'] : [];
        $errorDetails = is_array($cliError['details'] ?? null) ? $cliError['details'] : [];
        $pack = null;
        if (is_string($errorDetails['pack'] ?? null) && trim((string) $errorDetails['pack']) !== '') {
            $pack = trim((string) $errorDetails['pack']);
        } elseif (is_string($details['pack'] ?? null) && trim((string) $details['pack']) !== '') {
            $pack = trim((string) $details['pack']);
        }

        return [
            'status' => 'blocked',
            'code' => $code,
            'plan_id' => $planId,
            'pack' => $pack,
            'message' => $error->getMessage() !== '' ? $error->getMessage() : 'Generate apply is blocked by entitlement validation.',
            'details' => $errorDetails !== [] ? $errorDetails : $details,
        ];
    }
}
