<?php

declare(strict_types=1);

namespace Foundry\CLI\Workflow;

/**
 * Runs a deterministic sequence of workflow steps and returns an aggregate result.
 */
final class BatchWorkflowRunner
{
    /**
     * @param list<array{
     *     label:string,
     *     command:string,
     *     run:\Closure():array{status:int,message:?string,payload:array<string,mixed>|null}
     * }> $steps
     * @return array{
     *     ok:bool,
     *     status:int,
     *     workflow:string,
     *     steps:list<array{
     *         label:string,
     *         command:string,
     *         status:int,
     *         ok:bool,
     *         payload:array<string,mixed>|null,
     *         error:?array<string,mixed>
     *     }>,
     *     failed_step:?string,
     *     summary:array{total:int,passed:int,failed:int},
     *     next_actions:list<string>
     * }
     */
    public function run(string $workflow, array $steps, bool $continueOnFailure = false): array
    {
        $rows = [];
        $failedStep = null;
        $nextActions = [];

        foreach ($steps as $step) {
            $result = $step['run']();
            $status = (int) ($result['status'] ?? 1);
            $payload = is_array($result['payload'] ?? null) ? $result['payload'] : null;
            $error = is_array($payload['error'] ?? null) ? $payload['error'] : null;
            $ok = $status === 0;

            $rows[] = [
                'label' => $step['label'],
                'command' => $step['command'],
                'status' => $status,
                'ok' => $ok,
                'payload' => $payload,
                'error' => $error,
            ];

            if (!$ok) {
                $failedStep = $step['label'];
                $nextActions = $this->extractActions($payload);
                if (!$continueOnFailure) {
                    break;
                }
            }
        }

        $failed = count(array_filter(
            $rows,
            static fn(array $row): bool => ((bool) ($row['ok'] ?? false)) === false,
        ));
        $passed = count($rows) - $failed;

        return [
            'ok' => $failed === 0,
            'status' => $failed === 0 ? 0 : 1,
            'workflow' => $workflow,
            'steps' => $rows,
            'failed_step' => $failedStep,
            'summary' => [
                'total' => count($rows),
                'passed' => $passed,
                'failed' => $failed,
            ],
            'next_actions' => $nextActions,
        ];
    }

    /**
     * @param array<string,mixed>|null $payload
     * @return list<string>
     */
    private function extractActions(?array $payload): array
    {
        if ($payload === null) {
            return [];
        }

        $actions = [];
        foreach ((array) ($payload['required_actions'] ?? []) as $action) {
            if (!is_string($action) || trim($action) === '') {
                continue;
            }
            $actions[] = $action;
        }
        foreach ((array) ($payload['suggested_actions'] ?? []) as $action) {
            if (!is_string($action) || trim($action) === '') {
                continue;
            }
            $actions[] = $action;
        }

        return array_values(array_unique($actions));
    }
}
