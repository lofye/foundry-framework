<?php

declare(strict_types=1);

namespace Foundry\CLI\Commands;

use Foundry\CLI\Command;
use Foundry\CLI\CommandContext;
use Foundry\Context\ExecutionSpecCatalog;
use Foundry\Context\ExecutionSpecFilename;
use Foundry\Support\FeatureNaming;
use Foundry\Support\FoundryError;

final class SpecPromoteCommand extends Command
{
    #[\Override]
    public function supportedSignatures(): array
    {
        return ['spec:promote'];
    }

    #[\Override]
    public function matches(array $args): bool
    {
        return ($args[0] ?? null) === 'spec:promote';
    }

    #[\Override]
    public function run(array $args, CommandContext $context): array
    {
        $positionals = array_values(array_slice($args, 1));
        if ($positionals === []) {
            throw new FoundryError(
                'CLI_SPEC_PROMOTE_TARGET_REQUIRED',
                'validation',
                [],
                'Spec promote target required.',
            );
        }

        [$feature, $draftEntry] = $this->resolveDraftTarget($positionals, $context);
        $activePath = $this->activePathFromDraftPath((string) $draftEntry['path']);
        if ($activePath === null) {
            throw new FoundryError('CLI_SPEC_PROMOTE_PATH_INVALID', 'validation', ['path' => $draftEntry['path']], 'Draft spec path could not be promoted.');
        }

        if (is_file($context->paths()->join($activePath))) {
            throw new FoundryError(
                'CLI_SPEC_PROMOTE_ACTIVE_EXISTS',
                'validation',
                ['draft_path' => $draftEntry['path'], 'active_path' => $activePath],
                'Active execution spec already exists.',
            );
        }

        $contents = file_get_contents($context->paths()->join((string) $draftEntry['path']));
        if ($contents === false) {
            throw new FoundryError('CLI_SPEC_PROMOTE_READ_FAILED', 'filesystem', ['path' => $draftEntry['path']], 'Could not read draft execution spec.');
        }

        $expectedHeading = ExecutionSpecFilename::heading((string) $draftEntry['name']);
        $firstLine = strtok(str_replace("\r\n", "\n", $contents), "\n");
        if (trim((string) $firstLine) !== $expectedHeading) {
            throw new FoundryError(
                'CLI_SPEC_PROMOTE_HEADING_INVALID',
                'validation',
                ['path' => $draftEntry['path'], 'expected_heading' => $expectedHeading, 'actual_heading' => trim((string) $firstLine)],
                'Execution spec heading must match its filename before promotion.',
            );
        }

        $directory = dirname($context->paths()->join($activePath));
        if (!is_dir($directory) && !mkdir($directory, 0777, true) && !is_dir($directory)) {
            throw new FoundryError('CLI_SPEC_PROMOTE_DIRECTORY_CREATE_FAILED', 'filesystem', ['path' => dirname($activePath)], 'Could not create active specs directory.');
        }

        if (!rename($context->paths()->join((string) $draftEntry['path']), $context->paths()->join($activePath))) {
            throw new FoundryError(
                'CLI_SPEC_PROMOTE_MOVE_FAILED',
                'filesystem',
                ['draft_path' => $draftEntry['path'], 'active_path' => $activePath],
                'Could not move draft execution spec to active specs.',
            );
        }

        $jsonContext = new CommandContext($context->paths()->root(), true);
        $verifyContext = (new VerifyContextCommand())->run(['verify', 'context', '--feature=' . $feature], $jsonContext);
        $featureInspect = (new FeatureSystemCommand())->run(['feature:inspect', $feature], $jsonContext);
        $featureMap = (new FeatureSystemCommand())->run(['feature:map'], $jsonContext);

        $status = 0;
        $verifyPayload = is_array($verifyContext['payload'] ?? null) ? $verifyContext['payload'] : [];
        if (
            $verifyContext['status'] !== 0
            || (((bool) ($verifyPayload['can_proceed'] ?? true)) === false)
            || ((bool) ($verifyPayload['requires_repair'] ?? false))
        ) {
            $status = 1;
        }

        $payload = [
            'ok' => $status === 0,
            'feature' => $feature,
            'id' => $draftEntry['id'],
            'name' => $draftEntry['name'],
            'draft_path' => $draftEntry['path'],
            'active_path' => $activePath,
            'steps' => [
                [
                    'label' => 'promote',
                    'command' => 'move draft to active',
                    'status' => 0,
                ],
                [
                    'label' => 'verify_context',
                    'command' => 'verify context --feature=' . $feature,
                    'status' => (int) $verifyContext['status'],
                    'payload' => $verifyContext['payload'],
                ],
                [
                    'label' => 'feature_inspect',
                    'command' => 'feature:inspect ' . $feature,
                    'status' => (int) $featureInspect['status'],
                    'payload' => $featureInspect['payload'],
                ],
                [
                    'label' => 'feature_map',
                    'command' => 'feature:map',
                    'status' => (int) $featureMap['status'],
                    'payload' => $featureMap['payload'],
                ],
            ],
        ];

        return [
            'status' => $status,
            'message' => $context->expectsJson() ? null : $this->renderMessage($payload),
            'payload' => $context->expectsJson() ? $payload : null,
        ];
    }

    /**
     * @param list<string> $positionals
     * @return array{0:string,1:array<string,mixed>}
     */
    private function resolveDraftTarget(array $positionals, CommandContext $context): array
    {
        if (count($positionals) === 2) {
            $feature = FeatureNaming::canonical(trim((string) $positionals[0]));
            $id = trim((string) $positionals[1]);
            $entry = $this->resolveDraftWithinFeature($feature, $id, $context);
            if ($entry === null) {
                throw new FoundryError(
                    'CLI_SPEC_PROMOTE_DRAFT_NOT_FOUND',
                    'not_found',
                    ['feature' => $feature, 'id' => $id],
                    'Matching draft execution spec not found.',
                );
            }

            return [$feature, $entry];
        }

        $target = trim((string) $positionals[0]);
        $normalized = str_ends_with($target, '.md') ? substr($target, 0, -3) : $target;
        if (str_contains($normalized, '/')) {
            [$feature, $name] = $this->splitFeatureAndName($normalized);
            $entry = $this->resolveDraftWithinFeature($feature, $name, $context);
            if ($entry === null) {
                throw new FoundryError(
                    'CLI_SPEC_PROMOTE_DRAFT_NOT_FOUND',
                    'not_found',
                    ['feature' => $feature, 'name' => $name],
                    'Matching draft execution spec not found.',
                );
            }

            return [$feature, $entry];
        }

        if (ExecutionSpecFilename::isCanonicalName($normalized)) {
            $matches = $this->resolveDraftByName($normalized, $context);
            if (count($matches) !== 1) {
                throw new FoundryError(
                    'CLI_SPEC_PROMOTE_DRAFT_AMBIGUOUS',
                    'validation',
                    ['target' => $normalized, 'matches' => array_values(array_map(static fn(array $row): string => (string) $row['path'], $matches))],
                    'Expected exactly one matching draft execution spec.',
                );
            }

            $entry = $matches[0];

            return [(string) $entry['feature'], $entry];
        }

        throw new FoundryError(
            'CLI_SPEC_PROMOTE_ARGUMENTS_INVALID',
            'validation',
            ['arguments' => $positionals],
            'Spec promote accepts <feature> <id>, <feature>/<id>-<slug>, or <id>-<slug>.',
        );
    }

    /**
     * @return array<string,mixed>|null
     */
    private function resolveDraftWithinFeature(string $feature, string $nameOrId, CommandContext $context): ?array
    {
        $entries = (new ExecutionSpecCatalog($context->paths()))->entries($feature);
        $nameOrId = trim($nameOrId);
        $name = str_ends_with($nameOrId, '.md') ? substr($nameOrId, 0, -3) : $nameOrId;

        $matches = array_values(array_filter(
            $entries,
            static fn(array $entry): bool => (string) ($entry['status'] ?? '') === 'draft'
                && ((string) ($entry['name'] ?? '') === $name || (string) ($entry['id'] ?? '') === $name),
        ));

        if (count($matches) !== 1) {
            return null;
        }

        return $matches[0];
    }

    /**
     * @return list<array<string,mixed>>
     */
    private function resolveDraftByName(string $name, CommandContext $context): array
    {
        $candidates = [];
        foreach ((glob($context->paths()->join('Modules/*/specs/drafts/' . $name . '.md')) ?: []) as $path) {
            $candidates[] = $path;
        }
        foreach ((glob($context->paths()->join('Features/*/specs/drafts/' . $name . '.md')) ?: []) as $path) {
            $candidates[] = $path;
        }
        foreach ((glob($context->paths()->join('docs/features/*/specs/drafts/' . $name . '.md')) ?: []) as $path) {
            $candidates[] = $path;
        }

        $rows = [];
        foreach ($candidates as $absolutePath) {
            $relative = $this->relativePath($absolutePath, $context);
            if ($relative === null) {
                continue;
            }

            $parsed = ExecutionSpecFilename::parseDraftPath($relative);
            if ($parsed === null) {
                continue;
            }

            $rows[] = [
                'feature' => $parsed['feature'],
                'path' => $relative,
                'id' => $parsed['id'],
                'name' => $parsed['name'],
            ];
        }

        usort($rows, static fn(array $a, array $b): int => strcmp((string) $a['path'], (string) $b['path']));

        return $rows;
    }

    private function activePathFromDraftPath(string $draftPath): ?string
    {
        if (str_contains($draftPath, '/specs/drafts/')) {
            return str_replace('/specs/drafts/', '/specs/', $draftPath);
        }

        return null;
    }

    /**
     * @return array{0:string,1:string}
     */
    private function splitFeatureAndName(string $value): array
    {
        $parts = explode('/', str_replace('\\', '/', $value));
        if (count($parts) !== 2) {
            throw new FoundryError(
                'CLI_SPEC_PROMOTE_ARGUMENTS_INVALID',
                'validation',
                ['target' => $value],
                'Spec promote target must be <feature>/<id>-<slug> when using a slash.',
            );
        }

        return [FeatureNaming::canonical(trim($parts[0])), trim($parts[1])];
    }

    private function relativePath(string $absolutePath, CommandContext $context): ?string
    {
        $root = rtrim($context->paths()->root(), '/');
        if (!str_starts_with($absolutePath, $root . '/')) {
            return null;
        }

        return substr($absolutePath, strlen($root) + 1);
    }

    /**
     * @param array<string,mixed> $payload
     */
    private function renderMessage(array $payload): string
    {
        return implode(PHP_EOL, [
            'Spec promoted: ' . (string) $payload['name'],
            'Feature: ' . (string) $payload['feature'],
            'Draft path: ' . (string) $payload['draft_path'],
            'Active path: ' . (string) $payload['active_path'],
            'Status: ' . (((bool) ($payload['ok'] ?? false)) ? 'ok' : 'failed'),
        ]);
    }
}
