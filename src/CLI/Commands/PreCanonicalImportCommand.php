<?php

declare(strict_types=1);

namespace Foundry\CLI\Commands;

use Foundry\CLI\Command;
use Foundry\CLI\CommandContext;
use Foundry\FeatureSystem\PreCanonicalArchiveImporter;
use Foundry\Support\FoundryError;

final class PreCanonicalImportCommand extends Command
{
    #[\Override]
    public function supportedSignatures(): array
    {
        return ['precanonical:import'];
    }

    #[\Override]
    public function matches(array $args): bool
    {
        return ($args[0] ?? null) === 'precanonical:import';
    }

    #[\Override]
    public function run(array $args, CommandContext $context): array
    {
        $source = '_import/precanonical/marked-archive.md';
        $targetModule = 'PreCanonical';
        $apply = false;
        $force = false;

        foreach (array_slice($args, 1) as $arg) {
            if ($arg === '--apply') {
                $apply = true;
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

            if (str_starts_with($arg, '--target-module=')) {
                $targetModule = trim(substr($arg, strlen('--target-module=')));
                continue;
            }
        }

        if ($source === '') {
            throw new FoundryError(
                'CLI_PRECANONICAL_IMPORT_SOURCE_REQUIRED',
                'validation',
                [],
                'Source path is required.',
            );
        }

        $payload = (new PreCanonicalArchiveImporter($context->paths()))->import(
            sourcePath: $source,
            targetModule: $targetModule,
            apply: $apply,
            force: $force,
        );

        return [
            'status' => 0,
            'message' => $apply ? 'Pre-canonical archive import completed.' : 'Pre-canonical archive import report generated.',
            'payload' => $payload,
        ];
    }
}
