<?php

declare(strict_types=1);

namespace Foundry\CLI\Commands;

use Foundry\CLI\Command;
use Foundry\CLI\CommandContext;
use Foundry\FeatureSystem\HistoricalModuleContextGenerator;

final class HistoricalSpecsContextCommand extends Command
{
    #[\Override]
    public function supportedSignatures(): array
    {
        return ['historical-specs:context'];
    }

    #[\Override]
    public function matches(array $args): bool
    {
        return ($args[0] ?? null) === 'historical-specs:context';
    }

    #[\Override]
    public function run(array $args, CommandContext $context): array
    {
        $apply = false;
        $dryRun = false;
        $module = null;

        foreach (array_slice($args, 1) as $arg) {
            if ($arg === '--apply') {
                $apply = true;
                continue;
            }

            if ($arg === '--dry-run') {
                $dryRun = true;
                continue;
            }

            if (str_starts_with($arg, '--module=')) {
                $module = trim(substr($arg, strlen('--module=')));
                continue;
            }
        }

        if (!$apply) {
            $dryRun = true;
        }

        $payload = (new HistoricalModuleContextGenerator($context->paths()))->generate(
            module: $module,
            apply: $apply,
            dryRun: $dryRun,
        );

        return [
            'status' => 0,
            'message' => $payload['summary']['written'] > 0
                ? 'Historical module context generated.'
                : 'Historical module context report generated.',
            'payload' => $payload,
        ];
    }
}
