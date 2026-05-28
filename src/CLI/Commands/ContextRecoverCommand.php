<?php

declare(strict_types=1);

namespace Foundry\CLI\Commands;

use Foundry\CLI\Command;
use Foundry\CLI\CommandContext;
use Foundry\CLI\Workflow\BatchWorkflowRunner;
use Foundry\Support\FoundryError;

final class ContextRecoverCommand extends Command
{
    #[\Override]
    public function supportedSignatures(): array
    {
        return ['context recover'];
    }

    #[\Override]
    public function matches(array $args): bool
    {
        return ($args[0] ?? null) === 'context' && ($args[1] ?? null) === 'recover';
    }

    #[\Override]
    public function run(array $args, CommandContext $context): array
    {
        $feature = trim((string) ($args[2] ?? ''));
        if ($feature === '') {
            throw new FoundryError(
                'CLI_CONTEXT_RECOVER_FEATURE_REQUIRED',
                'validation',
                [],
                'Context recover requires a feature slug.',
            );
        }

        $jsonContext = new CommandContext($context->paths()->root(), true);
        $batch = (new BatchWorkflowRunner())->run('context recover', [
            [
                'label' => 'context_doctor',
                'command' => 'context doctor --feature=' . $feature,
                'run' => static fn(): array => (new ContextDoctorCommand())->run(['context', 'doctor', '--feature=' . $feature], $jsonContext),
            ],
            [
                'label' => 'context_check_alignment',
                'command' => 'context check-alignment --feature=' . $feature,
                'run' => static fn(): array => (new ContextCheckAlignmentCommand())->run(['context', 'check-alignment', '--feature=' . $feature], $jsonContext),
            ],
            [
                'label' => 'context_repair',
                'command' => 'context repair --feature=' . $feature,
                'run' => static fn(): array => (new ContextRepairCommand())->run(['context', 'repair', '--feature=' . $feature], $jsonContext),
            ],
            [
                'label' => 'verify_context',
                'command' => 'verify context --feature=' . $feature,
                'run' => static fn(): array => (new VerifyContextCommand())->run(['verify', 'context', '--feature=' . $feature], $jsonContext),
            ],
        ]);

        $verify = $this->stepPayload($batch, 'verify_context');
        if (is_array($verify) && (((bool) ($verify['can_proceed'] ?? true)) === false || ((bool) ($verify['requires_repair'] ?? false)) === true)) {
            $batch['ok'] = false;
            $batch['status'] = 1;
            $batch['failed_step'] = $batch['failed_step'] ?? 'verify_context';
        }

        return [
            'status' => $batch['status'],
            'message' => $context->expectsJson() ? null : $this->renderMessage($feature, $batch),
            'payload' => $context->expectsJson() ? array_merge(['feature' => $feature], $batch) : null,
        ];
    }

    /**
     * @param array<string,mixed> $batch
     */
    private function renderMessage(string $feature, array $batch): string
    {
        $lines = [
            'Context recover: ' . $feature,
            'Status: ' . (((bool) ($batch['ok'] ?? false)) ? 'ok' : 'failed'),
            'Summary: ' . (int) (($batch['summary']['passed'] ?? 0)) . '/' . (int) (($batch['summary']['total'] ?? 0)) . ' steps passed',
        ];

        if (($batch['failed_step'] ?? null) !== null) {
            $lines[] = 'Failed step: ' . (string) $batch['failed_step'];
        }

        foreach ((array) ($batch['next_actions'] ?? []) as $action) {
            if (!is_string($action)) {
                continue;
            }
            $lines[] = '- ' . $action;
        }

        return implode(PHP_EOL, $lines);
    }

    /**
     * @param array<string,mixed> $batch
     * @return array<string,mixed>|null
     */
    private function stepPayload(array $batch, string $label): ?array
    {
        foreach ((array) ($batch['steps'] ?? []) as $row) {
            if (!is_array($row) || (string) ($row['label'] ?? '') !== $label) {
                continue;
            }

            return is_array($row['payload'] ?? null) ? $row['payload'] : null;
        }

        return null;
    }
}

