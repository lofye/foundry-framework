<?php

declare(strict_types=1);

namespace Foundry\MCP;

use Foundry\MCP\Handlers\DoctorHandler;
use Foundry\MCP\Handlers\EventInspectHandler;
use Foundry\MCP\Handlers\EventListHandler;
use Foundry\MCP\Handlers\ExplainPackHandler;
use Foundry\MCP\Handlers\ExplainTargetHandler;
use Foundry\MCP\Handlers\GenerateApplyHandler;
use Foundry\MCP\Handlers\GeneratePlanHandler;
use Foundry\MCP\Handlers\InspectGraphHandler;
use Foundry\MCP\Handlers\ListExamplesHandler;
use Foundry\MCP\Handlers\ListPacksHandler;
use Foundry\MCP\Handlers\ValidatePlanHandler;
use Foundry\Support\FoundryError;

final class MCPServer
{
    public function __construct(private readonly ToolRegistry $registry) {}

    public static function boot(?CliReadBridge $bridge = null): self
    {
        $bridge ??= new CliReadBridge();
        $registry = new ToolRegistry();
        $apply = new GenerateApplyHandler($bridge);

        $registry->register('doctor', new DoctorHandler($bridge));
        $registry->register('event.inspect', new EventInspectHandler($bridge));
        $registry->register('event.list', new EventListHandler($bridge));
        $registry->register('explain_pack', new ExplainPackHandler($bridge));
        $registry->register('explain_target', new ExplainTargetHandler($bridge));
        $registry->register('apply_plan', $apply);
        $registry->register('generate_apply', $apply);
        $registry->register('generate_plan', new GeneratePlanHandler($bridge));
        $registry->register('inspect_graph', new InspectGraphHandler($bridge));
        $registry->register('list_examples', new ListExamplesHandler($bridge));
        $registry->register('list_packs', new ListPacksHandler($bridge));
        $registry->register('validate_plan', new ValidatePlanHandler($bridge));

        return new self($registry);
    }

    /**
     * @return array{name:string,tools:list<string>}
     */
    public function manifest(): array
    {
        return [
            'name' => 'foundry-mcp',
            'tools' => $this->registry->names(),
        ];
    }

    /**
     * @param array<string,mixed> $input
     * @return array{tool:string,data:array<string,mixed>}
     */
    public function invoke(string $tool, array $input = []): array
    {
        if ($tool === '') {
            throw new FoundryError('MCP_TOOL_NAME_INVALID', 'validation', ['tool' => $tool], 'MCP tool name is required.');
        }

        return [
            'tool' => $tool,
            'data' => $this->registry->invoke($tool, $input),
        ];
    }
}
