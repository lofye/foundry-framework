<?php

declare(strict_types=1);

namespace Foundry\CLI\Commands;

use Foundry\CLI\Command;
use Foundry\CLI\CommandContext;
use Foundry\FeatureSystem\HistoricalSpecArchiveExtractor;
use Foundry\Support\FoundryError;

final class HistoricalSpecsExtractCommand extends Command
{
    #[\Override]
    public function supportedSignatures(): array
    {
        return ['historical-specs:extract'];
    }

    #[\Override]
    public function matches(array $args): bool
    {
        return ($args[0] ?? null) === 'historical-specs:extract';
    }

    #[\Override]
    public function run(array $args, CommandContext $context): array
    {
        $source = '_import/raw-historical-specs';
        $target = '_import/historical-specs';
        $dryRun = false;

        foreach (array_slice($args, 1) as $arg) {
            if ($arg === '--dry-run') {
                $dryRun = true;
                continue;
            }

            if (str_starts_with($arg, '--source=')) {
                $source = trim(substr($arg, strlen('--source=')));
                continue;
            }

            if (str_starts_with($arg, '--target=')) {
                $target = trim(substr($arg, strlen('--target=')));
                continue;
            }
        }

        if ($source === '') {
            throw new FoundryError(
                'CLI_HISTORICAL_SPECS_SOURCE_REQUIRED',
                'validation',
                [],
                'Source path is required.',
            );
        }

        if ($target === '') {
            throw new FoundryError(
                'CLI_HISTORICAL_SPECS_TARGET_REQUIRED',
                'validation',
                [],
                'Target path is required.',
            );
        }

        $payload = (new HistoricalSpecArchiveExtractor($context->paths()))->extract($source, $target, $dryRun);

        return [
            'status' => 0,
            'message' => $dryRun
                ? 'Historical spec extraction dry-run completed.'
                : 'Historical spec extraction completed.',
            'payload' => $payload,
        ];
    }
}
