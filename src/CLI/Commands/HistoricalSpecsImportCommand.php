<?php

declare(strict_types=1);

namespace Foundry\CLI\Commands;

use Foundry\CLI\Command;
use Foundry\CLI\CommandContext;
use Foundry\FeatureSystem\HistoricalSpecArchiveImporter;
use Foundry\Support\FoundryError;

final class HistoricalSpecsImportCommand extends Command
{
    #[\Override]
    public function supportedSignatures(): array
    {
        return ['historical-specs:import'];
    }

    #[\Override]
    public function matches(array $args): bool
    {
        return ($args[0] ?? null) === 'historical-specs:import';
    }

    #[\Override]
    public function run(array $args, CommandContext $context): array
    {
        $source = 'historical-specs';
        $apply = false;
        $dryRun = false;
        $force = false;

        foreach (array_slice($args, 1) as $arg) {
            if ($arg === '--apply') {
                $apply = true;
                continue;
            }

            if ($arg === '--dry-run') {
                $dryRun = true;
                continue;
            }

            if ($arg === '--force') {
                $force = true;
                continue;
            }

            if (str_starts_with($arg, '--source=')) {
                $source = trim(substr($arg, strlen('--source=')));
                continue;
            }
        }

        if ($source === '') {
            throw new FoundryError(
                'CLI_HISTORICAL_SPECS_IMPORT_SOURCE_REQUIRED',
                'validation',
                [],
                'Source path is required.',
            );
        }

        if (!$apply) {
            $dryRun = true;
        }

        $payload = (new HistoricalSpecArchiveImporter($context->paths()))->import(
            sourcePath: $source,
            apply: $apply,
            dryRun: $dryRun,
            force: $force,
        );

        return [
            'status' => 0,
            'message' => $payload['summary']['written'] > 0
                ? 'Historical spec import completed.'
                : 'Historical spec import report generated.',
            'payload' => $payload,
        ];
    }
}
