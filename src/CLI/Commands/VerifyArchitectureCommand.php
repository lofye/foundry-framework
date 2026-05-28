<?php

declare(strict_types=1);

namespace Foundry\CLI\Commands;

use Foundry\CLI\Command;
use Foundry\CLI\CommandContext;
use Foundry\CLI\Workflow\BatchWorkflowRunner;

final class VerifyArchitectureCommand extends Command
{
    #[\Override]
    public function supportedSignatures(): array
    {
        return ['verify architecture'];
    }

    #[\Override]
    public function matches(array $args): bool
    {
        return ($args[0] ?? null) === 'verify' && ($args[1] ?? null) === 'architecture';
    }

    #[\Override]
    public function run(array $args, CommandContext $context): array
    {
        $jsonContext = new CommandContext($context->paths()->root(), true);
        $batch = (new BatchWorkflowRunner())->run('verify architecture', [
            [
                'label' => 'compile_graph',
                'command' => 'compile graph',
                'run' => static fn(): array => (new CompileGraphCommand())->run(['compile', 'graph'], $jsonContext),
            ],
            [
                'label' => 'inspect_graph',
                'command' => 'inspect graph',
                'run' => static fn(): array => (new InspectGraphCommand())->run(['inspect', 'graph'], $jsonContext),
            ],
            [
                'label' => 'inspect_pipeline',
                'command' => 'inspect pipeline',
                'run' => static fn(): array => (new InspectGraphCommand())->run(['inspect', 'pipeline'], $jsonContext),
            ],
            [
                'label' => 'verify_graph',
                'command' => 'verify graph',
                'run' => static fn(): array => (new VerifyGraphCommand())->run(['verify', 'graph'], $jsonContext),
            ],
            [
                'label' => 'verify_pipeline',
                'command' => 'verify pipeline',
                'run' => static fn(): array => (new VerifyPipelineCommand())->run(['verify', 'pipeline'], $jsonContext),
            ],
            [
                'label' => 'verify_contracts',
                'command' => 'verify contracts',
                'run' => static fn(): array => (new VerifyContractsCommand())->run(['verify', 'contracts'], $jsonContext),
            ],
        ]);

        $payload = [
            'workflow' => $batch['workflow'],
            'ok' => $batch['ok'],
            'status' => $batch['status'],
            'summary' => $batch['summary'],
            'failed_step' => $batch['failed_step'],
            'next_actions' => $batch['next_actions'],
            'graph_summary' => $this->stepPayload($batch, 'inspect_graph'),
            'pipeline_summary' => $this->stepPayload($batch, 'inspect_pipeline'),
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
            'Verify architecture',
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
