<?php
declare(strict_types=1);

namespace Foundry\Explain;

final class ExplanationPlanAssembler
{
    /**
     * @param array<string,mixed> $summary
     * @param array<int,array<string,mixed>> $sections
     * @param array<string,mixed> $relationships
     * @param array<string,mixed> $executionFlow
     * @param array<string,mixed> $diagnostics
     * @param array<int,string> $relatedCommands
     * @param array<int,array<string,mixed>> $relatedDocs
     * @param array<string,mixed> $metadata
     */
    public function assemble(
        ExplainSubject $subject,
        array $summary,
        array $sections,
        array $relationships,
        array $executionFlow,
        array $diagnostics,
        array $relatedCommands,
        array $relatedDocs,
        array $metadata,
    ): ExplanationPlan {
        return new ExplanationPlan(
            subject: $subject->toArray(),
            summary: $summary,
            sections: $this->normalizeSections($sections),
            relationships: $this->normalizeRelationships($relationships),
            executionFlow: $this->normalizeExecutionFlow($executionFlow),
            diagnostics: $this->normalizeDiagnostics($diagnostics),
            relatedCommands: ExplainSupport::uniqueStrings($relatedCommands),
            relatedDocs: $this->uniqueDocs($relatedDocs),
            metadata: $metadata,
        );
    }

    /**
     * @param array<int,array<string,mixed>> $sections
     * @return array<int,array<string,mixed>>
     */
    private function normalizeSections(array $sections): array
    {
        $rows = [];
        foreach ($sections as $index => $section) {
            if (!$this->isSectionRenderable($section)) {
                continue;
            }

            $section['_render_index'] = $index;
            $rows[] = $section;
        }

        usort($rows, function (array $left, array $right): int {
            $leftId = (string) ($left['id'] ?? '');
            $rightId = (string) ($right['id'] ?? '');

            return ($this->sectionPriority($leftId) <=> $this->sectionPriority($rightId))
                ?: ((int) ($left['_render_index'] ?? 0) <=> (int) ($right['_render_index'] ?? 0))
                ?: strcmp((string) ($left['title'] ?? ''), (string) ($right['title'] ?? ''));
        });

        foreach ($rows as &$row) {
            unset($row['_render_index']);
        }
        unset($row);

        return $rows;
    }

    /**
     * @param array<string,mixed> $relationships
     * @return array<string,mixed>
     */
    private function normalizeRelationships(array $relationships): array
    {
        return [
            'depends_on' => $this->rowList($relationships['depends_on'] ?? []),
            'depended_on_by' => $this->rowList($relationships['depended_on_by'] ?? []),
            'neighbors' => $this->rowList($relationships['neighbors'] ?? []),
        ];
    }

    /**
     * @param array<string,mixed> $executionFlow
     * @return array<string,mixed>
     */
    private function normalizeExecutionFlow(array $executionFlow): array
    {
        return $executionFlow;
    }

    /**
     * @param array<string,mixed> $diagnostics
     * @return array<string,mixed>
     */
    private function normalizeDiagnostics(array $diagnostics): array
    {
        return [
            'summary' => is_array($diagnostics['summary'] ?? null)
                ? $diagnostics['summary']
                : ['error' => 0, 'warning' => 0, 'info' => 0, 'total' => 0],
            'items' => $this->rowList($diagnostics['items'] ?? []),
        ];
    }

    /**
     * @param mixed $rows
     * @return array<int,array<string,mixed>>
     */
    private function rowList(mixed $rows): array
    {
        $filtered = [];
        foreach ((array) $rows as $row) {
            if (is_array($row)) {
                $filtered[] = $row;
            }
        }

        return $filtered;
    }

    /**
     * @param array<int,array<string,mixed>> $rows
     * @return array<int,array<string,mixed>>
     */
    private function uniqueDocs(array $rows): array
    {
        $unique = [];
        foreach ($rows as $row) {
            $id = trim((string) ($row['id'] ?? $row['path'] ?? $row['title'] ?? ''));
            if ($id === '') {
                $id = md5(serialize($row));
            }

            $unique[$id] = $row;
        }

        usort(
            $unique,
            static fn (array $left, array $right): int => strcmp((string) ($left['title'] ?? ''), (string) ($right['title'] ?? ''))
                ?: strcmp((string) ($left['path'] ?? ''), (string) ($right['path'] ?? '')),
        );

        return array_values($unique);
    }

    /**
     * @param array<string,mixed> $section
     */
    private function isSectionRenderable(array $section): bool
    {
        if (!array_key_exists('items', $section)) {
            return false;
        }

        $items = $section['items'];
        if (is_array($items)) {
            return $items !== [];
        }

        return $items !== null && $items !== '';
    }

    private function sectionPriority(string $id): int
    {
        return match ($id) {
            'subject' => 0,
            'contracts' => 10,
            'route' => 20,
            'workflow' => 30,
            'event' => 40,
            'extension' => 50,
            'command' => 60,
            'job' => 70,
            'schema' => 80,
            'pipeline_stage' => 90,
            'impact' => 900,
            default => 800,
        };
    }
}
