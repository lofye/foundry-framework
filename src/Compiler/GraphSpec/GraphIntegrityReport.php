<?php

declare(strict_types=1);

namespace Foundry\Compiler\GraphSpec;

use Foundry\Verification\VerificationResult;

final readonly class GraphIntegrityReport
{
    /**
     * @param array<int,array<string,mixed>> $issues
     */
    public function __construct(
        public bool $ok,
        public ?int $graphVersion,
        public ?int $graphSpecVersion,
        public array $issues,
    ) {}

    /**
     * @return array<string,int>
     */
    public function summary(): array
    {
        $summary = [
            'error' => 0,
            'warning' => 0,
            'info' => 0,
            'total' => 0,
        ];

        foreach ($this->issues as $issue) {
            $severity = (string) ($issue['severity'] ?? 'error');
            $summary[$severity] = ($summary[$severity] ?? 0) + 1;
            $summary['total']++;
        }

        return $summary;
    }

    /**
     * @return array<int,string>
     */
    public function errors(): array
    {
        return array_values(array_map(
            static fn(array $issue): string => (string) ($issue['message'] ?? 'Graph integrity error.'),
            array_values(array_filter($this->issues, static fn(array $issue): bool => (string) ($issue['severity'] ?? 'error') === 'error')),
        ));
    }

    /**
     * @return array<int,string>
     */
    public function warnings(): array
    {
        return array_values(array_map(
            static fn(array $issue): string => (string) ($issue['message'] ?? 'Graph integrity warning.'),
            array_values(array_filter($this->issues, static fn(array $issue): bool => (string) ($issue['severity'] ?? 'warning') === 'warning')),
        ));
    }

    public function toVerificationResult(): VerificationResult
    {
        return new VerificationResult(
            ok: $this->ok,
            errors: $this->errors(),
            warnings: $this->warnings(),
        );
    }

    /**
     * @return array<string,mixed>
     */
    public function toArray(): array
    {
        return [
            'ok' => $this->ok,
            'graph_version' => $this->graphVersion,
            'graph_spec_version' => $this->graphSpecVersion,
            'summary' => $this->summary(),
            'issues' => $this->issues,
        ];
    }
}
