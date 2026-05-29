<?php

declare(strict_types=1);

namespace Foundry\CLI\Commands;

use Foundry\CLI\Command;
use Foundry\CLI\CommandContext;
use Foundry\Support\FoundryError;
use Foundry\Tooling\ProcessRunner;

final class TestFeatureCommand extends Command
{
    #[\Override]
    public function supportedSignatures(): array
    {
        return ['test feature'];
    }

    #[\Override]
    public function matches(array $args): bool
    {
        return ($args[0] ?? null) === 'test' && ($args[1] ?? null) === 'feature';
    }

    #[\Override]
    public function run(array $args, CommandContext $context): array
    {
        $feature = trim((string) ($args[2] ?? ''));
        if ($feature === '') {
            throw new FoundryError(
                'CLI_TEST_FEATURE_REQUIRED',
                'validation',
                [],
                'Test feature requires a feature slug.',
            );
        }

        $featurePath = $this->featureTestPath($context, $feature);
        if ($featurePath === null) {
            throw new FoundryError(
                'CLI_TEST_FEATURE_MISSING_TESTS',
                'validation',
                ['feature' => $feature],
                'Feature test directory not found.',
            );
        }

        $runner = new ProcessRunner();
        $phpBinary = $this->extractOption($args, '--phpunit') ?? 'php';
        $phpunit = $context->paths()->join('vendor/bin/phpunit');
        $filter = $this->extractOption($args, '--filter');
        $full = in_array('--full', $args, true);
        $coverage = in_array('--coverage', $args, true);
        $coverageMin = (int) ($this->extractOption($args, '--coverage-min') ?? '90');

        $steps = [];
        $featureCommand = [$phpBinary, $phpunit, $featurePath];
        if ($filter !== null && $filter !== '') {
            $featureCommand[] = '--filter=' . $filter;
        }
        $steps[] = [
            'label' => 'phpunit_feature',
            'command' => $featureCommand,
            'result' => $runner->run($featureCommand, $context->paths()->root()),
        ];

        if ($full) {
            $steps[] = [
                'label' => 'phpunit_all',
                'command' => [$phpBinary, $phpunit],
                'result' => $runner->run([$phpBinary, $phpunit], $context->paths()->root()),
            ];
        }

        if ($coverage) {
            $coverageCommand = $this->coverageCommand($context, $phpBinary, $phpunit);
            $coverageRun = $runner->run($coverageCommand, $context->paths()->root());
            $steps[] = [
                'label' => 'phpunit_coverage',
                'command' => $coverageCommand,
                'result' => $coverageRun,
            ];

            if ($coverageRun['ok']) {
                $coverageResult = (new VerifyCoverageCommand())->run(
                    ['verify', 'coverage', '--min=' . $coverageMin, '--clover=build/coverage/clover.xml'],
                    new CommandContext($context->paths()->root(), true),
                );
                $steps[] = [
                    'label' => 'verify_coverage',
                    'command' => ['verify', 'coverage', '--min=' . $coverageMin, '--clover=build/coverage/clover.xml'],
                    'result' => [
                        'ok' => $coverageResult['status'] === 0,
                        'exit_code' => $coverageResult['status'],
                        'stdout' => '',
                        'stderr' => '',
                        'command' => ['verify', 'coverage', '--min=' . $coverageMin, '--clover=build/coverage/clover.xml'],
                        'payload' => $coverageResult['payload'],
                    ],
                ];
            }
        }

        $payloadSteps = [];
        $allOk = true;
        foreach ($steps as $step) {
            $result = $step['result'];
            $ok = (bool) ($result['ok'] ?? false);
            $allOk = $allOk && $ok;
            $payloadSteps[] = [
                'label' => $step['label'],
                'command' => $step['command'],
                'ok' => $ok,
                'exit_code' => (int) ($result['exit_code'] ?? 1),
                'stdout' => (string) ($result['stdout'] ?? ''),
                'stderr' => (string) ($result['stderr'] ?? ''),
                'payload' => is_array($result['payload'] ?? null) ? $result['payload'] : null,
            ];
        }

        $payload = [
            'feature' => $feature,
            'feature_test_path' => $featurePath,
            'ok' => $allOk,
            'steps' => $payloadSteps,
            'summary' => [
                'total' => count($payloadSteps),
                'passed' => count(array_filter($payloadSteps, static fn(array $row): bool => (bool) ($row['ok'] ?? false))),
                'failed' => count(array_filter($payloadSteps, static fn(array $row): bool => ((bool) ($row['ok'] ?? false)) === false)),
            ],
        ];

        return [
            'status' => $allOk ? 0 : 1,
            'message' => $context->expectsJson() ? null : $this->renderMessage($payload),
            'payload' => $context->expectsJson() ? $payload : null,
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

    /**
     * @return list<string>
     */
    private function coverageCommand(CommandContext $context, string $phpBinary, string $phpunit): array
    {
        $wrapper = $context->paths()->join('bin/phpunit-coverage');
        if (is_file($wrapper) && is_executable($wrapper)) {
            return ['bin/phpunit-coverage', '--coverage-clover', 'build/coverage/clover.xml'];
        }

        return ['env', 'XDEBUG_MODE=coverage', $phpBinary, $phpunit, '--coverage-clover', 'build/coverage/clover.xml'];
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
        return implode(PHP_EOL, [
            'Test feature: ' . (string) $payload['feature'],
            'Status: ' . (((bool) ($payload['ok'] ?? false)) ? 'ok' : 'failed'),
            'Summary: ' . (int) (($payload['summary']['passed'] ?? 0)) . '/' . (int) (($payload['summary']['total'] ?? 0)) . ' steps passed',
        ]);
    }
}
