<?php

declare(strict_types=1);

namespace Foundry\Tests\Unit;

use Foundry\Compiler\ApplicationGraph;
use Foundry\Compiler\IR\NodeFactory;
use Foundry\Documentation\CommandCatalog;
use Foundry\Support\ApiSurfaceRegistry;
use Foundry\Support\Paths;
use Foundry\Tests\Fixtures\TempProject;
use PHPUnit\Framework\TestCase;

final class CommandCatalogTest extends TestCase
{
    private TempProject $project;

    protected function setUp(): void
    {
        $this->project = new TempProject();
    }

    protected function tearDown(): void
    {
        $this->project->cleanup();
    }

    public function test_data_includes_state_store_command_rows_with_deterministic_examples(): void
    {
        $catalog = new CommandCatalog(new Paths($this->project->root), new ApiSurfaceRegistry());
        $data = $catalog->data($this->graph());

        $this->assertArrayHasKey('summary', $data);
        $this->assertArrayHasKey('filters', $data);
        $this->assertArrayHasKey('commands', $data);

        $inspectStore = $this->commandRow($data, 'inspect state-store');
        $verifyStore = $this->commandRow($data, 'verify state-store');
        $inspectMarketplace = $this->commandRow($data, 'inspect marketplace');
        $verifyMarketplace = $this->commandRow($data, 'verify marketplace');
        $login = $this->commandRow($data, 'login');
        $logout = $this->commandRow($data, 'logout');
        $whoami = $this->commandRow($data, 'whoami');
        $entitlements = $this->commandRow($data, 'entitlements');
        $packPurchase = $this->commandRow($data, 'pack purchase');
        $generateIntent = $this->commandRow($data, 'generate <intent>');
        $help = $this->commandRow($data, 'help');

        $this->assertSame('Architecture', $inspectStore['category']);
        $this->assertSame('inspect', $inspectStore['commandType']);
        $this->assertContains('foundry inspect state-store --json', $inspectStore['examples']);
        $this->assertContains('foundry help inspect state-store --json', $inspectStore['examples']);

        $this->assertSame('Verification', $verifyStore['category']);
        $this->assertContains('foundry verify state-store --json', $verifyStore['examples']);
        $this->assertContains('foundry help verify state-store --json', $verifyStore['examples']);
        $this->assertContains('foundry inspect marketplace --json', $inspectMarketplace['examples']);
        $this->assertContains('foundry verify marketplace --json', $verifyMarketplace['examples']);
        $this->assertContains('foundry login --user=demo-user --token=token_demo_1234 --json', $login['examples']);
        $this->assertContains('foundry logout --json', $logout['examples']);
        $this->assertContains('foundry whoami --json', $whoami['examples']);
        $this->assertContains('foundry entitlements --json', $entitlements['examples']);
        $this->assertContains('foundry pack purchase vendor/premium-pack --json', $packPurchase['examples']);

        $this->assertContains('foundry help generate Add --json', $generateIntent['examples']);
        $this->assertSame('Sample command JSON output (`help --json` index)', $help['sampleOutputLabel']);
        $this->assertArrayHasKey('policy', $help['sampleOutput']);
    }

    public function test_data_derives_related_graph_targets_from_workflow_and_extension_nodes(): void
    {
        $catalog = new CommandCatalog(new Paths($this->project->root), new ApiSurfaceRegistry());
        $data = $catalog->data($this->graph());

        $workflow = $this->commandRow($data, 'inspect workflow');
        $inspectRoute = $this->commandRow($data, 'inspect route');

        $workflowTargets = array_column($workflow['explainTargets'], 'title');
        $routeExamples = $inspectRoute['examples'];

        $this->assertContains('workflow:editorial', $workflowTargets);
        $this->assertContains('command:inspect workflow', $workflowTargets);
        $this->assertContains('foundry inspect route GET / --json', $routeExamples);
    }

    /**
     * @param array<string,mixed> $data
     * @return array<string,mixed>
     */
    private function commandRow(array $data, string $signature): array
    {
        foreach ((array) ($data['commands'] ?? []) as $row) {
            if (is_array($row) && (string) ($row['signature'] ?? '') === $signature) {
                return $row;
            }
        }

        self::fail(sprintf('Expected command row for signature "%s".', $signature));
    }

    private function graph(): ApplicationGraph
    {
        $graph = new ApplicationGraph(
            graphVersion: 2,
            frameworkVersion: 'dev-main',
            compiledAt: '2026-05-04T00:00:00+00:00',
            sourceHash: 'abc123',
        );

        $graph->addNode(NodeFactory::fromArray([
            'id' => 'feature:publish_post',
            'type' => 'feature',
            'source_path' => 'app/features/publish_post/feature.yaml',
            'payload' => ['feature' => 'publish_post', 'extension' => 'platform'],
        ]));
        $graph->addNode(NodeFactory::fromArray([
            'id' => 'route:invalid',
            'type' => 'route',
            'source_path' => 'app/features/publish_post/feature.yaml',
            'payload' => ['signature' => 'INVALID_ROUTE_SIGNATURE'],
        ]));
        $graph->addNode(NodeFactory::fromArray([
            'id' => 'event:post.created',
            'type' => 'event',
            'source_path' => 'app/features/publish_post/events.yaml',
            'payload' => ['name' => 'post.created'],
        ]));
        $graph->addNode(NodeFactory::fromArray([
            'id' => 'workflow:editorial',
            'type' => 'workflow',
            'source_path' => 'app/definitions/workflows/editorial.workflow.yaml',
            'payload' => ['resource' => 'editorial', 'name' => 'editorial'],
        ]));
        $graph->addNode(NodeFactory::fromArray([
            'id' => 'schema:input',
            'type' => 'schema',
            'source_path' => 'app/features/publish_post/input.schema.json',
            'payload' => ['path' => 'app/features/publish_post/input.schema.json'],
        ]));

        return $graph;
    }
}
