<?php

declare(strict_types=1);

namespace Foundry\CLI\Commands;

use Foundry\CLI\Command;
use Foundry\CLI\CommandContext;
use Foundry\CLI\Commands\Concerns\InteractsWithLicensing;
use Foundry\Compiler\CompileOptions;
use Foundry\Explain\Diff\ExplainDiffService;
use Foundry\Explain\ExplainOptions;
use Foundry\Explain\ExplainSupport;
use Foundry\Explain\ExplainTarget;
use Foundry\Explain\Snapshot\ExplainSnapshotService;
use Foundry\Monetization\FeatureFlags;
use Foundry\Pro\ArchitectureExplainer;
use Foundry\Support\FoundryError;

final class ExplainCommand extends Command
{
    use InteractsWithLicensing;

    #[\Override]
    public function supportedSignatures(): array
    {
        return ['explain'];
    }

    #[\Override]
    public function matches(array $args): bool
    {
        return ($args[0] ?? null) === 'explain';
    }

    #[\Override]
    public function run(array $args, CommandContext $context): array
    {
        $this->monetizationContext('explain', [FeatureFlags::PRO_EXPLAIN_PLUS]);
        [$target, $targetKind, $options, $diff] = $this->parseExplainArgs($args);

        if ($diff) {
            $this->assertDiffModeOptions($target, $targetKind, $options);
            $diffService = new ExplainDiffService(
                $context->paths(),
                new ExplainSnapshotService($context->paths(), $context->apiSurfaceRegistry()),
            );
            $payload = $diffService->loadLast();

            return [
                'status' => 0,
                'message' => $context->expectsJson() ? null : $diffService->render($payload),
                'payload' => $context->expectsJson() ? $payload : null,
            ];
        }

        $compiler = $context->graphCompiler();
        $graph = $compiler->loadGraph() ?? $compiler->compile(new CompileOptions())->graph;
        if ($target === '') {
            $target = ExplainSupport::defaultTarget($graph);
        }

        $response = (new ArchitectureExplainer(
            paths: $context->paths(),
            impactAnalyzer: $compiler->impactAnalyzer(),
            apiSurfaceRegistry: $context->apiSurfaceRegistry(),
            extensionRows: $context->extensionRegistry()->inspectRows(),
        ))->explain($graph, ExplainTarget::parse($target, $targetKind), $options);

        return [
            'status' => 0,
            'message' => $context->expectsJson() ? null : $response->rendered,
            'payload' => $context->expectsJson() ? $response->toArray() : null,
        ];
    }

    /**
     * @param array<int,string> $args
     * @return array{0:string,1:?string,2:ExplainOptions,3:bool}
     */
    private function parseExplainArgs(array $args): array
    {
        $targetParts = [];
        $targetKind = null;
        $format = 'text';
        $deep = false;
        $includeDiagnostics = true;
        $includeNeighbors = true;
        $includeExecutionFlow = true;
        $diff = false;

        for ($index = 1; $index < count($args); $index++) {
            $arg = (string) $args[$index];

            if ($arg === '--diff') {
                $diff = true;
                continue;
            }

            if ($arg === '--markdown') {
                $format = 'markdown';
                continue;
            }

            if ($arg === '--deep') {
                $deep = true;
                continue;
            }

            if ($arg === '--no-diagnostics') {
                $includeDiagnostics = false;
                continue;
            }

            if ($arg === '--no-neighbors') {
                $includeNeighbors = false;
                continue;
            }

            if ($arg === '--neighbors') {
                $includeNeighbors = true;
                continue;
            }

            if ($arg === '--no-flow') {
                $includeExecutionFlow = false;
                continue;
            }

            if (str_starts_with($arg, '--type=')) {
                $targetKind = trim(substr($arg, strlen('--type=')));
                continue;
            }

            if ($arg === '--type') {
                $targetKind = trim((string) ($args[$index + 1] ?? ''));
                $index++;
                continue;
            }

            $targetParts[] = $arg;
        }

        return [
            trim(implode(' ', $targetParts)),
            $targetKind !== '' ? $targetKind : null,
            new ExplainOptions(
                format: $format,
                deep: $deep,
                includeDiagnostics: $includeDiagnostics,
                includeNeighbors: $includeNeighbors,
                includeExecutionFlow: $includeExecutionFlow,
                type: $targetKind !== '' ? $targetKind : null,
            ),
            $diff,
        ];
    }

    private function assertDiffModeOptions(string $target, ?string $targetKind, ExplainOptions $options): void
    {
        if (
            trim($target) !== ''
            || $targetKind !== null
            || $options->format !== 'text'
            || $options->deep
            || $options->includeDiagnostics !== true
            || $options->includeNeighbors !== true
            || $options->includeExecutionFlow !== true
        ) {
            throw new FoundryError(
                'EXPLAIN_DIFF_ARGUMENTS_INVALID',
                'validation',
                [],
                'Explain diff supports only `foundry explain --diff` and `foundry explain --diff --json`.',
            );
        }
    }
}
