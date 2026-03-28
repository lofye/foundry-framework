<?php

declare(strict_types=1);

namespace Foundry\CLI\Commands;

use Foundry\CLI\Command;
use Foundry\CLI\CommandContext;

final class VerifyGraphCommand extends Command
{
    #[\Override]
    public function supportedSignatures(): array
    {
        return ['verify graph', 'verify graph-integrity'];
    }

    #[\Override]
    public function matches(array $args): bool
    {
        return ($args[0] ?? null) === 'verify'
            && in_array((string) ($args[1] ?? ''), ['graph', 'graph-integrity'], true);
    }

    #[\Override]
    public function run(array $args, CommandContext $context): array
    {
        $target = (string) ($args[1] ?? 'graph');
        if ($target === 'graph-integrity') {
            $report = $context->graphVerifier()->verifyGraphIntegrity();

            return [
                'status' => $report->ok ? 0 : 1,
                'message' => $report->ok ? 'Graph integrity verification passed.' : 'Graph integrity verification failed.',
                'payload' => $report->toArray(),
            ];
        }

        $result = $context->graphVerifier()->verify();
        $artifactVerification = $context->graphVerifier()->verifyArtifacts();
        $graphIntegrity = $context->graphVerifier()->verifyGraphIntegrity();

        return [
            'status' => $result->ok ? 0 : 1,
            'message' => $result->ok ? 'Graph verification passed.' : 'Graph verification failed.',
            'payload' => $result->toArray() + [
                'artifact_verification' => $artifactVerification->toArray(),
                'graph_integrity' => $graphIntegrity->toArray(),
            ],
        ];
    }
}
