<?php

declare(strict_types=1);

namespace Foundry\MCP\Handlers;

use Foundry\MCP\CliReadBridge;
use Foundry\MCP\ToolHandler;
use Foundry\Support\FoundryError;

final class ExplainPlanHandler implements ToolHandler
{
    public function __construct(private readonly CliReadBridge $bridge) {}

    public function handle(array $input): array
    {
        $planId = trim((string) ($input['plan_id'] ?? ''));
        if ($planId === '') {
            throw new FoundryError(
                'MCP_INPUT_INVALID',
                'validation',
                ['tool' => 'explain_plan', 'field' => 'plan_id'],
                'Input `plan_id` is required.',
            );
        }

        return $this->bridge->run(['explain', 'plan', $planId]);
    }
}
