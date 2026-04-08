<?php

declare(strict_types=1);

namespace Foundry\CLI\Commands;

use Foundry\CLI\Command;
use Foundry\CLI\CommandContext;
use Foundry\Context\ContextInitService;
use Foundry\Support\FoundryError;

final class ContextInitCommand extends Command
{
    #[\Override]
    public function supportedSignatures(): array
    {
        return ['context init'];
    }

    #[\Override]
    public function matches(array $args): bool
    {
        return ($args[0] ?? null) === 'context' && ($args[1] ?? null) === 'init';
    }

    #[\Override]
    public function run(array $args, CommandContext $context): array
    {
        $featureName = (string) ($args[2] ?? '');
        if ($featureName === '') {
            throw new FoundryError(
                'CLI_CONTEXT_FEATURE_REQUIRED',
                'validation',
                [],
                'Context feature name required.',
            );
        }

        $payload = (new ContextInitService($context->paths()))->init($featureName);

        return [
            'status' => $payload['success'] ? 0 : 1,
            'message' => $context->expectsJson() ? null : $this->renderMessage($payload),
            'payload' => $context->expectsJson() ? $payload : null,
        ];
    }

    /**
     * @param array{success:bool,feature:string,feature_valid:bool,created:list<string>,existing:list<string>,issues:list<array<string,mixed>>} $payload
     */
    private function renderMessage(array $payload): string
    {
        if (!$payload['success']) {
            $lines = [
                'Context init failed: ' . $payload['feature'],
                'Feature name valid: no',
                'Issues:',
            ];

            foreach ($payload['issues'] as $issue) {
                $lines[] = '- ' . (string) ($issue['code'] ?? '') . ': ' . (string) ($issue['message'] ?? '');
            }

            return implode(PHP_EOL, $lines);
        }

        return implode(PHP_EOL, [
            'Context initialized: ' . $payload['feature'],
            'Created:',
            ...$this->listLines($payload['created']),
            'Already existed:',
            ...$this->listLines($payload['existing']),
        ]);
    }

    /**
     * @param list<string> $values
     * @return list<string>
     */
    private function listLines(array $values): array
    {
        if ($values === []) {
            return ['- none'];
        }

        return array_values(array_map(
            static fn(string $value): string => '- ' . $value,
            $values,
        ));
    }
}
