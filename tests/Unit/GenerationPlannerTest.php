<?php

declare(strict_types=1);

namespace Foundry\Tests\Unit;

use Foundry\Explain\ExplainModel;
use Foundry\Generate\GenerationContextPacket;
use Foundry\Generate\GenerationPlan;
use Foundry\Generate\GenerationPlanner;
use Foundry\Generate\Generator;
use Foundry\Generate\GeneratorRegistry;
use Foundry\Generate\Intent;
use Foundry\Generate\RegisteredGenerator;
use PHPUnit\Framework\TestCase;

final class GenerationPlannerTest extends TestCase
{
    public function test_planner_prefers_pack_generator_when_pack_hint_matches(): void
    {
        $registry = new GeneratorRegistry();
        $registry->register(new RegisteredGenerator(
            id: 'core.feature.new',
            origin: 'core',
            extension: null,
            generator: $this->generator('core.feature.new'),
            priority: 10,
        ));
        $registry->register(new RegisteredGenerator(
            id: 'generate blog-post',
            origin: 'pack',
            extension: 'foundry/blog',
            generator: $this->generator('generate blog-post', 'pack', 'foundry/blog'),
            capabilities: ['blog.notes'],
            priority: 50,
        ));

        $planner = new GenerationPlanner($registry);
        $plan = $planner->plan(new GenerationContextPacket(
            intent: new Intent(
                raw: 'Create blog post notes',
                mode: 'new',
                packHints: ['foundry/blog'],
            ),
            model: $this->model('pack:foundry/blog', 'pack', 'foundry/blog'),
            targets: [],
            graphRelationships: [],
            constraints: [],
            docs: [],
            validationSteps: [],
            availableGenerators: [],
            installedPacks: [],
            missingCapabilities: [],
            suggestedPacks: [],
        ));

        $this->assertSame('pack', $plan->origin);
        $this->assertSame('generate blog-post', $plan->generatorId);
        $this->assertSame('foundry/blog', $plan->extension);
    }

    private function generator(string $id, string $origin = 'core', ?string $extension = null): Generator
    {
        return new class($id, $origin, $extension) implements Generator {
            public function __construct(
                private readonly string $id,
                private readonly string $origin,
                private readonly ?string $extension,
            ) {}

            #[\Override]
            public function supports(ExplainModel $model, Intent $intent): bool
            {
                return true;
            }

            #[\Override]
            public function plan(ExplainModel $model, Intent $intent): GenerationPlan
            {
                return new GenerationPlan(
                    actions: [[
                        'type' => 'create_file',
                        'path' => 'app/features/example/feature.yaml',
                        'summary' => 'write',
                        'explain_node_id' => (string) ($model->subject['id'] ?? 'system:root'),
                        'origin' => $this->origin,
                        'extension' => $this->extension,
                    ]],
                    affectedFiles: ['app/features/example/feature.yaml'],
                    risks: [],
                    validations: [],
                    origin: $this->origin,
                    generatorId: $this->id,
                    extension: $this->extension,
                );
            }
        };
    }

    private function model(string $id, string $kind, ?string $extension = null): ExplainModel
    {
        return new ExplainModel(
            subject: [
                'id' => $id,
                'kind' => $kind,
                'label' => $id,
                'origin' => $extension !== null ? 'extension' : 'core',
                'extension' => $extension,
            ],
            graph: ['node_ids' => [], 'subject_node' => null, 'neighbors' => ['inbound' => [], 'outbound' => [], 'lateral' => []]],
            execution: ['entries' => [], 'stages' => [], 'action' => null, 'workflows' => [], 'jobs' => []],
            guards: ['items' => []],
            events: ['emits' => [], 'subscriptions' => [], 'emitters' => [], 'subscribers' => []],
            schemas: ['subject' => null, 'items' => [], 'reads' => [], 'writes' => [], 'fields' => []],
            relationships: ['dependsOn' => ['items' => []], 'usedBy' => ['items' => []], 'graph' => ['inbound' => [], 'outbound' => [], 'lateral' => []]],
            diagnostics: ['summary' => ['error' => 0, 'warning' => 0, 'info' => 0, 'total' => 0], 'items' => []],
            docs: ['related' => []],
            impact: [],
            commands: ['subject' => null, 'related' => []],
            metadata: [],
            extensions: [],
        );
    }
}
