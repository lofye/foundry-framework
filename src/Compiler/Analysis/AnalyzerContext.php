<?php
declare(strict_types=1);

namespace Foundry\Compiler\Analysis;

use Foundry\Compiler\IR\GraphNode;

final readonly class AnalyzerContext
{
    public function __construct(
        public ?string $featureFilter = null,
    ) {
    }

    public function includesFeature(string $feature): bool
    {
        if ($this->featureFilter === null || $this->featureFilter === '') {
            return true;
        }

        return $this->featureFilter === $feature;
    }

    public function includesNode(GraphNode $node): bool
    {
        if ($this->featureFilter === null || $this->featureFilter === '') {
            return true;
        }

        $payload = $node->payload();
        $feature = (string) ($payload['feature'] ?? '');
        if ($feature !== '') {
            return $this->includesFeature($feature);
        }

        return match ($node->type()) {
            'feature' => $node->id() === ('feature:' . $this->featureFilter),
            'route' => in_array($this->featureFilter, array_map('strval', (array) ($payload['features'] ?? [])), true),
            'event' => in_array($this->featureFilter, array_map('strval', (array) ($payload['emitters'] ?? [])), true)
                || in_array($this->featureFilter, array_map('strval', (array) ($payload['subscribers'] ?? [])), true),
            'permission' => in_array($this->featureFilter, array_map('strval', (array) ($payload['features'] ?? [])), true)
                || in_array($this->featureFilter, array_map('strval', (array) ($payload['declared_by'] ?? [])), true)
                || in_array($this->featureFilter, array_map('strval', (array) ($payload['referenced_by'] ?? [])), true),
            'cache' => in_array($this->featureFilter, array_map('strval', (array) ($payload['features'] ?? [])), true)
                || in_array($this->featureFilter, array_map('strval', (array) ($payload['invalidated_by'] ?? [])), true),
            default => false,
        };
    }
}

