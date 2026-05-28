<?php

declare(strict_types=1);

namespace Foundry\CLI\Commands;

use Foundry\CLI\Command;
use Foundry\CLI\CommandContext;
use Foundry\CLI\Workflow\BatchWorkflowRunner;
use Foundry\Support\FoundryError;

final class VerifyFeatureWorkCommand extends Command
{
    #[\Override]
    public function supportedSignatures(): array
    {
        return ['verify feature-work'];
    }

    #[\Override]
    public function matches(array $args): bool
    {
        return ($args[0] ?? null) === 'verify' && ($args[1] ?? null) === 'feature-work';
    }

    #[\Override]
    public function run(array $args, CommandContext $context): array
    {
        $feature = trim((string) ($args[2] ?? ''));
        if ($feature === '') {
            throw new FoundryError(
                'CLI_VERIFY_FEATURE_WORK_FEATURE_REQUIRED',
                'validation',
                [],
                'Verify feature-work requires a feature slug.',
            );
        }

        $jsonContext = new CommandContext($context->paths()->root(), true);
        $batch = (new BatchWorkflowRunner())->run('verify feature-work', [
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
                'label' => 'verify_context',
                'command' => 'verify context --feature=' . $feature,
                'run' => static fn(): array => (new VerifyContextCommand())->run(['verify', 'context', '--feature=' . $feature], $jsonContext),
            ],
            [
                'label' => 'verify_features',
                'command' => 'verify features',
                'run' => static fn(): array => (new VerifyFeaturesCommand())->run(['verify', 'features'], $jsonContext),
            ],
            [
                'label' => 'feature_map',
                'command' => 'feature:map',
                'run' => static fn(): array => (new FeatureSystemCommand())->run(['feature:map'], $jsonContext),
            ],
        ]);

        $doctor = $this->stepPayload($batch, 'context_doctor');
        $alignment = $this->stepPayload($batch, 'context_check_alignment');
        $contextPayload = $this->stepPayload($batch, 'verify_context');
        if (
            (is_array($doctor) && in_array((string) ($doctor['status'] ?? ''), ['repairable', 'non_compliant'], true))
            || (is_array($alignment) && (string) ($alignment['status'] ?? '') === 'mismatch')
            || (is_array($contextPayload) && (((bool) ($contextPayload['can_proceed'] ?? true)) === false || ((bool) ($contextPayload['requires_repair'] ?? false)) === true))
        ) {
            $batch['ok'] = false;
            $batch['status'] = 1;
            $batch['failed_step'] = $batch['failed_step'] ?? 'verify_context';
        }

        $payload = [
            'feature' => $feature,
            'workflow' => $batch['workflow'],
            'ok' => $batch['ok'],
            'status' => $batch['status'],
            'summary' => $batch['summary'],
            'failed_step' => $batch['failed_step'],
            'next_actions' => $batch['next_actions'],
            'steps' => $batch['steps'],
        ];

        return [
            'status' => (int) $batch['status'],
            'message' => $context->expectsJson() ? null : $this->renderMessage($payload),
            'payload' => $context->expectsJson() ? $payload : null,
        ];
    }

    /**
     * @param array<string,mixed> $payload
     */
    private function renderMessage(array $payload): string
    {
        $lines = [
            'Verify feature-work: ' . (string) $payload['feature'],
            'Status: ' . (((bool) ($payload['ok'] ?? false)) ? 'ok' : 'failed'),
            'Summary: ' . (int) (($payload['summary']['passed'] ?? 0)) . '/' . (int) (($payload['summary']['total'] ?? 0)) . ' steps passed',
        ];

        if (($payload['failed_step'] ?? null) !== null) {
            $lines[] = 'Failed step: ' . (string) $payload['failed_step'];
        }

        foreach ((array) ($payload['next_actions'] ?? []) as $action) {
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
