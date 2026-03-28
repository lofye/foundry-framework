<?php

declare(strict_types=1);

namespace Foundry\Tests\Unit;

use Foundry\Compiler\GraphSpec\CanonicalGraphSpecification;
use Foundry\Compiler\GraphSpec\UnknownGraphNodeType;
use Foundry\Compiler\IR\NodeFactory;
use PHPUnit\Framework\TestCase;

final class NodeFactoryTest extends TestCase
{
    public function test_from_array_throws_for_unknown_node_type(): void
    {
        $this->expectException(UnknownGraphNodeType::class);

        NodeFactory::fromArray([
            'id' => 'mystery:node',
            'type' => 'mystery_node',
            'source_path' => 'app/features/mystery/feature.yaml',
            'payload' => ['feature' => 'mystery'],
            'graph_compatibility' => [CanonicalGraphSpecification::instance()->currentGraphVersion()],
        ]);
    }
}
