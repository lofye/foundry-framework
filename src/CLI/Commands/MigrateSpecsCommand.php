<?php
declare(strict_types=1);

namespace Foundry\CLI\Commands;

use Foundry\CLI\Command;
use Foundry\CLI\CommandContext;

final class MigrateSpecsCommand extends Command
{
    #[\Override]
    public function matches(array $args): bool
    {
        return ($args[0] ?? null) === 'migrate' && ($args[1] ?? null) === 'specs';
    }

    #[\Override]
    public function run(array $args, CommandContext $context): array
    {
        $write = in_array('--write', $args, true);
        $dryRun = in_array('--dry-run', $args, true) || !$write;

        $result = $context->specMigrator()->migrate($write);

        return [
            'status' => 0,
            'message' => $write ? 'Spec migration complete.' : 'Spec migration dry run complete.',
            'payload' => [
                'mode' => $write ? 'write' : 'dry-run',
                'dry_run' => $dryRun,
                'result' => $result->toArray(),
            ],
        ];
    }
}
