<?php

declare(strict_types=1);

namespace Foundry\CLI\Commands;

use Foundry\CLI\Command;
use Foundry\CLI\CommandContext;
use Foundry\FeatureSystem\HistoricalSpecEvidenceMapper;
use Foundry\Support\FoundryError;

final class HistoricalSpecsEvidenceCommand extends Command
{
    #[\Override]
    public function supportedSignatures(): array
    {
        return ['historical-specs:evidence'];
    }

    #[\Override]
    public function matches(array $args): bool
    {
        return ($args[0] ?? null) === 'historical-specs:evidence';
    }

    #[\Override]
    public function run(array $args, CommandContext $context): array
    {
        $source = '_import/historical-specs';
        $anchors = '_import/historical-specs/import-anchors.json';
        $withGitEvidence = false;
        $write = false;
        $dryRun = false;

        foreach (array_slice($args, 1) as $arg) {
            if ($arg === '--with-git') {
                $withGitEvidence = true;
                continue;
            }

            if ($arg === '--write') {
                $write = true;
                continue;
            }

            if ($arg === '--dry-run') {
                $dryRun = true;
                continue;
            }

            if (str_starts_with($arg, '--source=')) {
                $source = trim(substr($arg, strlen('--source=')));
                continue;
            }

            if (str_starts_with($arg, '--anchors=')) {
                $anchors = trim(substr($arg, strlen('--anchors=')));
                continue;
            }
        }

        if ($source === '') {
            throw new FoundryError(
                'CLI_HISTORICAL_SPECS_EVIDENCE_SOURCE_REQUIRED',
                'validation',
                [],
                'Source path is required.',
            );
        }

        if (!$write) {
            $dryRun = true;
        }

        $payload = (new HistoricalSpecEvidenceMapper($context->paths()))->build(
            sourcePath: $source,
            anchorsPath: $anchors,
            withGitEvidence: $withGitEvidence,
            write: $write,
            dryRun: $dryRun,
        );

        return [
            'status' => 0,
            'message' => $payload['outputs']['written']
                ? 'Historical spec evidence map generated.'
                : 'Historical spec evidence map preview generated.',
            'payload' => $payload,
        ];
    }
}
