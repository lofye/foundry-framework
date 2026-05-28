<?php

declare(strict_types=1);

namespace Foundry\CLI\Commands;

use Foundry\CLI\Command;
use Foundry\CLI\CommandContext;
use Foundry\CLI\Workflow\BatchWorkflowRunner;
use Foundry\Support\FoundryError;
use Foundry\Tooling\ProcessRunner;

final class VerifyDoneCommand extends Command
{
    #[\Override]
    public function supportedSignatures(): array
    {
        return ['verify done'];
    }

    #[\Override]
    public function matches(array $args): bool
    {
        return ($args[0] ?? null) === 'verify' && ($args[1] ?? null) === 'done';
    }

    #[\Override]
    public function run(array $args, CommandContext $context): array
    {
        $feature = $this->extractOption($args, '--feature');
        if ($feature === null || trim($feature) === '') {
            throw new FoundryError('CLI_VERIFY_DONE_FEATURE_REQUIRED', 'validation', [], 'Verify done requires --feature=<feature>.');
        }

        $coverageMin = (int) ($this->extractOption($args, '--coverage-min') ?? '90');
        $skipCoverage = in_array('--skip-coverage', $args, true);
        $phpBinary = $this->extractOption($args, '--phpunit') ?? 'php';
        $phpunit = $context->paths()->join('vendor/bin/phpunit');
        $featureTestPath = $this->featureTestPath($context, $feature);
        $jsonContext = new CommandContext($context->paths()->root(), true);
        $processRunner = new ProcessRunner();
        $steps = [
            [
                'label' => 'verify_feature_work',
                'command' => 'verify feature-work ' . $feature,
                'run' => static fn(): array => (new VerifyFeatureWorkCommand())->run(['verify', 'feature-work', $feature], $jsonContext),
            ],
            [
                'label' => 'verify_architecture',
                'command' => 'verify architecture',
                'run' => static fn(): array => (new VerifyArchitectureCommand())->run(['verify', 'architecture'], $jsonContext),
            ],
        ];

        if ($featureTestPath !== null) {
            $steps[] = [
                'label' => 'phpunit_feature',
                'command' => $phpBinary . ' ' . $phpunit . ' ' . $featureTestPath,
                'run' => fn(): array => $this->runProcessStep($processRunner, [$phpBinary, $phpunit, $featureTestPath], $context),
            ];
        }

        $steps[] = [
            'label' => 'phpunit_all',
            'command' => $phpBinary . ' ' . $phpunit,
            'run' => fn(): array => $this->runProcessStep($processRunner, [$phpBinary, $phpunit], $context),
        ];

        if (!$skipCoverage) {
            $steps[] = [
                'label' => 'phpunit_coverage',
                'command' => 'env XDEBUG_MODE=coverage ' . $phpBinary . ' ' . $phpunit . ' --coverage-clover build/coverage/clover.xml',
                'run' => fn(): array => $this->runProcessStep($processRunner, ['env', 'XDEBUG_MODE=coverage', $phpBinary, $phpunit, '--coverage-clover', 'build/coverage/clover.xml'], $context),
            ];
            $steps[] = [
                'label' => 'verify_coverage',
                'command' => 'verify coverage --min=' . $coverageMin . ' --clover=build/coverage/clover.xml',
                'run' => static fn(): array => (new VerifyCoverageCommand())->run(['verify', 'coverage', '--min=' . $coverageMin, '--clover=build/coverage/clover.xml'], $jsonContext),
            ];
        }

        $batch = (new BatchWorkflowRunner())->run('verify done', $steps);
        $payload = [
            'feature' => $feature,
            'coverage_min' => $coverageMin,
            'skip_coverage' => $skipCoverage,
            'workflow' => $batch['workflow'],
            'ok' => $batch['ok'],
            'status' => $batch['status'],
            'summary' => $batch['summary'],
            'failed_step' => $batch['failed_step'],
            'next_actions' => $batch['next_actions'],
            'steps' => $batch['steps'],
        ];

        return [
            'status' => (int) $batch['status'],
            'message' => $context->expectsJson() ? null : $this->renderMessage($payload),
            'payload' => $context->expectsJson() ? $payload : null,
        ];
    }

    /**
     * @param array<int,string> $command
     * @return array{status:int,message:?string,payload:array<string,mixed>}
     */
    private function runProcessStep(ProcessRunner $runner, array $command, CommandContext $context): array
    {
        $result = $runner->run($command, $context->paths()->root());

        return [
            'status' => $result['ok'] ? 0 : 1,
            'message' => null,
            'payload' => [
                'ok' => $result['ok'],
                'exit_code' => $result['exit_code'],
                'command' => $result['command'],
                'stdout' => $result['stdout'],
                'stderr' => $result['stderr'],
            ],
        ];
    }

    private function featureTestPath(CommandContext $context, string $feature): ?string
    {
        $slug = $this->canonicalFeature($feature);
        $pascal = $this->pascalFromSlug($slug);
        $candidates = [
            'Features/' . $pascal . '/tests',
            'Modules/' . $pascal . '/tests',
            'docs/features/' . $slug . '/tests',
        ];

        foreach ($candidates as $candidate) {
            if (is_dir($context->paths()->join($candidate))) {
                return $candidate;
            }
        }

        return null;
    }

    private function canonicalFeature(string $value): string
    {
        $normalized = strtolower(trim($value));
        $normalized = preg_replace('/[^a-z0-9]+/', '-', $normalized) ?? $normalized;

        return trim($normalized, '-');
    }

    private function pascalFromSlug(string $slug): string
    {
        $parts = array_filter(explode('-', $slug), static fn(string $part): bool => $part !== '');

        return implode('', array_map(static fn(string $part): string => ucfirst($part), $parts));
    }

    /**
     * @param array<int,string> $args
     */
    private function extractOption(array $args, string $name): ?string
    {
        foreach ($args as $index => $arg) {
            if (str_starts_with($arg, $name . '=')) {
                $value = substr($arg, strlen($name . '='));

                return $value !== '' ? $value : null;
            }

            if ($arg === $name) {
                $value = (string) ($args[$index + 1] ?? '');

                return $value !== '' ? $value : null;
            }
        }

        return null;
    }

    /**
     * @param array<string,mixed> $payload
     */
    private function renderMessage(array $payload): string
    {
        $lines = [
            'Verify done: ' . (string) $payload['feature'],
            'Status: ' . (((bool) ($payload['ok'] ?? false)) ? 'ok' : 'failed'),
            'Summary: ' . (int) (($payload['summary']['passed'] ?? 0)) . '/' . (int) (($payload['summary']['total'] ?? 0)) . ' steps passed',
        ];

        if (($payload['failed_step'] ?? null) !== null) {
            $lines[] = 'Failed step: ' . (string) $payload['failed_step'];
        }

        foreach ((array) ($payload['next_actions'] ?? []) as $action) {
            if (!is_string($action)) {
                continue;
            }
            $lines[] = '- ' . $action;
        }

        return implode(PHP_EOL, $lines);
    }
}

