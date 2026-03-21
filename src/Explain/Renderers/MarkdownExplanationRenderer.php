<?php
declare(strict_types=1);

namespace Foundry\Explain\Renderers;

use Foundry\Explain\ExplanationPlan;

final class MarkdownExplanationRenderer implements ExplanationRendererInterface
{
    public function render(ExplanationPlan $plan): string
    {
        $payload = $plan->toArray();
        $lines = [
            '## Subject',
            '- **Label:** ' . (string) ($payload['subject']['label'] ?? ''),
            '- **ID:** ' . (string) ($payload['subject']['id'] ?? ''),
            '- **Kind:** ' . (string) ($payload['subject']['kind'] ?? ''),
        ];

        $summary = trim((string) ($payload['summary']['text'] ?? ''));
        if ($summary !== '') {
            $lines[] = '';
            $lines[] = '## Summary';
            $lines[] = $summary;
        }

        foreach ((array) ($payload['sections'] ?? []) as $section) {
            if (!is_array($section)) {
                continue;
            }

            $title = trim((string) ($section['title'] ?? ''));
            if ($title === '') {
                continue;
            }

            $lines[] = '';
            $lines[] = '## ' . $title;
            foreach ((array) ($section['items'] ?? []) as $key => $value) {
                $formatted = $this->formatValue($value);
                if ($formatted === '') {
                    continue;
                }

                $lines[] = '- **' . str_replace('_', ' ', ucfirst((string) $key)) . ':** ' . $formatted;
            }
        }

        $dependsOn = (array) ($payload['relationships']['depends_on'] ?? []);
        if ($dependsOn !== []) {
            $lines[] = '';
            $lines[] = '## Depends On';
            foreach ($dependsOn as $row) {
                if (!is_array($row)) {
                    continue;
                }

                $lines[] = '- ' . (string) ($row['label'] ?? $row['id'] ?? '');
            }
        }

        $usedBy = (array) ($payload['relationships']['depended_on_by'] ?? []);
        if ($usedBy !== []) {
            $lines[] = '';
            $lines[] = '## Used By';
            foreach ($usedBy as $row) {
                if (!is_array($row)) {
                    continue;
                }

                $lines[] = '- ' . (string) ($row['label'] ?? $row['id'] ?? '');
            }
        }

        $steps = array_values(array_map('strval', (array) ($payload['execution_flow']['steps'] ?? [])));
        if ($steps !== []) {
            $lines[] = '';
            $lines[] = '## Execution Flow';
            foreach ($steps as $step) {
                $lines[] = '- ' . $step;
            }
        }

        $commands = array_values(array_map('strval', (array) ($payload['related_commands'] ?? [])));
        if ($commands !== []) {
            $lines[] = '';
            $lines[] = '## Related Commands';
            foreach ($commands as $command) {
                $lines[] = '- `' . $command . '`';
            }
        }

        $docs = (array) ($payload['related_docs'] ?? []);
        if ($docs !== []) {
            $lines[] = '';
            $lines[] = '## Related Docs';
            foreach ($docs as $row) {
                if (!is_array($row)) {
                    continue;
                }

                $label = trim((string) ($row['title'] ?? $row['id'] ?? ''));
                $path = trim((string) ($row['path'] ?? ''));
                $lines[] = '- ' . ($path !== '' ? '[' . $label . '](' . $path . ')' : $label);
            }
        }

        return implode(PHP_EOL, $lines);
    }

    private function formatValue(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_scalar($value)) {
            return trim((string) $value);
        }

        if (!is_array($value) || $value === []) {
            return '';
        }

        $items = [];
        foreach ($value as $key => $item) {
            $rendered = $this->formatListItem($item);
            if ($rendered === '') {
                continue;
            }

            if (!array_is_list($value)) {
                $items[] = (string) $key . '=' . $rendered;
                continue;
            }

            $items[] = $rendered;
        }

        return implode(', ', $items);
    }

    private function formatListItem(mixed $item): string
    {
        if ($item === null) {
            return '';
        }

        if (is_bool($item)) {
            return $item ? 'true' : 'false';
        }

        if (is_scalar($item)) {
            return trim((string) $item);
        }

        if (!is_array($item)) {
            return '';
        }

        foreach (['label', 'title', 'name', 'resource', 'id', 'path'] as $key) {
            $value = trim((string) ($item[$key] ?? ''));
            if ($value !== '') {
                return $value;
            }
        }

        $parts = [];
        foreach ($item as $key => $value) {
            if (!is_scalar($value) || trim((string) $value) === '') {
                continue;
            }
            $parts[] = (string) $key . '=' . trim((string) $value);
        }

        return implode(', ', $parts);
    }
}
