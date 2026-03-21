<?php
declare(strict_types=1);

namespace Foundry\Tests\Unit;

use Foundry\Compiler\ApplicationGraph;
use Foundry\Compiler\BuildLayout;
use Foundry\Compiler\IR\FeatureNode;
use Foundry\Explain\ExplainArtifactCatalog;
use Foundry\Explain\ExplainContext;
use Foundry\Explain\ExplainOptions;
use Foundry\Explain\ExplainSubject;
use Foundry\Explain\ExplainSubjectFactory;
use Foundry\Explain\ExplanationPlanAssembler;
use Foundry\Explain\ExplainTarget;
use Foundry\Explain\ExplainTargetResolver;
use Foundry\Support\ApiSurfaceRegistry;
use Foundry\Support\FoundryError;
use Foundry\Support\Paths;
use Foundry\Tests\Fixtures\TempProject;
use PHPUnit\Framework\TestCase;

final class ExplainFoundationTest extends TestCase
{
    private TempProject $project;
    private Paths $paths;

    protected function setUp(): void
    {
        $this->project = new TempProject();
        $this->paths = Paths::fromCwd($this->project->root);
    }

    protected function tearDown(): void
    {
        $this->project->cleanup();
    }

    public function test_target_parse_preserves_explicit_kind_even_when_unsupported(): void
    {
        $target = ExplainTarget::parse('unknown:thing');

        $this->assertSame('unknown:thing', $target->raw);
        $this->assertSame('unknown', $target->kind);
        $this->assertSame('thing', $target->selector);
    }

    public function test_resolver_fails_cleanly_for_unsupported_kind(): void
    {
        $resolver = new ExplainTargetResolver(
            new ApplicationGraph(1, '1.0.0', '2026-03-20T00:00:00+00:00', 'hash'),
            new ExplainArtifactCatalog(new BuildLayout($this->paths), $this->paths, new ApiSurfaceRegistry()),
        );

        $this->expectException(FoundryError::class);
        $this->expectExceptionMessage('Unsupported explain target kind: unknown.');

        try {
            $resolver->resolve(ExplainTarget::parse('unknown:thing'));
        } catch (FoundryError $error) {
            $this->assertSame('EXPLAIN_TARGET_KIND_UNSUPPORTED', $error->errorCode);
            $this->assertContains('feature', (array) ($error->details['supported_kinds'] ?? []));
            throw $error;
        }
    }

    public function test_subject_factory_normalizes_graph_and_command_subjects(): void
    {
        $factory = new ExplainSubjectFactory();
        $node = new FeatureNode('feature:publish_post', 'app/features/publish_post/feature.yaml', [
            'feature' => 'publish_post',
            'description' => 'publish post',
        ]);

        $graphSubject = $factory->fromGraphNode($node);
        $commandSubject = $factory->fromCommandRow([
            'signature' => 'doctor',
            'usage' => 'doctor --json',
        ]);

        $this->assertSame('feature', $graphSubject->kind);
        $this->assertSame('feature:publish_post', $graphSubject->id);
        $this->assertSame(['feature:publish_post'], $graphSubject->graphNodeIds);
        $this->assertSame('command', $commandSubject?->kind);
        $this->assertSame('command:doctor', $commandSubject?->id);
        $this->assertSame(['doctor'], $commandSubject?->aliases);
    }

    public function test_context_initializes_foundation_placeholders(): void
    {
        $context = new ExplainContext(
            new ApplicationGraph(1, '1.0.0', '2026-03-20T00:00:00+00:00', 'hash'),
            new ExplainArtifactCatalog(new BuildLayout($this->paths), $this->paths, new ApiSurfaceRegistry()),
            new ExplainSubject('feature', 'feature:publish_post', 'publish_post', ['feature:publish_post'], ['publish_post'], ['feature' => 'publish_post']),
            'php vendor/bin/foundry',
        );

        $this->assertArrayHasKey('graph_subject', $context->all());
        $this->assertArrayHasKey('pipeline', $context->all());
        $this->assertArrayHasKey('commands', $context->all());
        $this->assertArrayHasKey('workflows', $context->all());
        $this->assertArrayHasKey('events', $context->all());
        $this->assertArrayHasKey('schemas', $context->all());
        $this->assertArrayHasKey('extensions', $context->all());
        $this->assertArrayHasKey('diagnostics', $context->all());
        $this->assertArrayHasKey('docs', $context->all());
    }

    public function test_plan_assembler_orders_sections_and_omits_truly_empty_ones(): void
    {
        $assembler = new ExplanationPlanAssembler();
        $subject = new ExplainSubject(
            kind: 'feature',
            id: 'feature:publish_post',
            label: 'publish_post',
            graphNodeIds: ['feature:publish_post'],
            aliases: ['publish_post'],
            metadata: ['feature' => 'publish_post'],
        );

        $plan = $assembler->assemble(
            subject: $subject,
            summary: ['text' => 'publish_post is a feature that manages post publishing.', 'deterministic' => true],
            sections: [
                ['id' => 'impact', 'title' => 'Impact', 'items' => ['risk' => 'low']],
                ['id' => 'notes', 'title' => 'Notes', 'items' => ['owner' => 'core']],
                ['id' => 'contracts', 'title' => 'Contracts', 'items' => ['description' => 'Publish posts.']],
                ['id' => 'empty', 'title' => 'Empty', 'items' => []],
                ['id' => 'subject', 'title' => 'Subject', 'items' => ['id' => 'feature:publish_post']],
            ],
            relationships: [
                'depends_on' => [['kind' => 'feature', 'label' => 'account']],
                'depended_on_by' => [],
                'neighbors' => [],
            ],
            executionFlow: [],
            diagnostics: ['summary' => ['error' => 0, 'warning' => 0, 'info' => 0, 'total' => 0], 'items' => []],
            relatedCommands: ['php vendor/bin/foundry doctor', 'php vendor/bin/foundry doctor'],
            relatedDocs: [
                ['title' => 'Feature Docs', 'path' => 'docs/features.md'],
                ['title' => 'Feature Docs', 'path' => 'docs/features.md'],
            ],
            metadata: ['options' => (new ExplainOptions(type: 'feature'))->toArray()],
        );

        $this->assertSame(
            ['subject', 'contracts', 'notes', 'impact'],
            array_values(array_map(static fn (array $section): string => (string) ($section['id'] ?? ''), $plan->sections)),
        );
        $this->assertCount(1, $plan->relatedCommands);
        $this->assertCount(1, $plan->relatedDocs);
        $this->assertSame('feature', $plan->metadata['options']['type']);
    }
}
