<?php

declare(strict_types=1);

namespace Foundry\Tests\Unit;

use Foundry\Support\ApiSurfaceRegistry;
use PHPUnit\Framework\TestCase;

final class ApiSurfaceRegistryBatchCommandsTest extends TestCase
{
    public function test_classifies_batch_workflow_commands(): void
    {
        $registry = new ApiSurfaceRegistry();

        $contextBootstrap = $registry->classifyCliCommand(['context', 'bootstrap', 'event-bus']);
        $contextRecover = $registry->classifyCliCommand(['context', 'recover', 'event-bus']);
        $specPromote = $registry->classifyCliCommand(['spec:promote', 'event-bus', '001']);
        $verifyArchitecture = $registry->classifyCliCommand(['verify', 'architecture']);
        $verifyFeatureWork = $registry->classifyCliCommand(['verify', 'feature-work', 'event-bus']);
        $verifyDone = $registry->classifyCliCommand(['verify', 'done', '--feature=event-bus']);
        $testFeature = $registry->classifyCliCommand(['test', 'feature', 'event-bus']);

        $this->assertNotNull($contextBootstrap);
        $this->assertSame('stable', $contextBootstrap['stability']);
        $this->assertSame('context', $contextBootstrap['command_type']);

        $this->assertNotNull($contextRecover);
        $this->assertSame('stable', $contextRecover['stability']);
        $this->assertSame('context', $contextRecover['command_type']);

        $this->assertNotNull($specPromote);
        $this->assertSame('stable', $specPromote['stability']);
        $this->assertSame('spec:promote', $specPromote['command_type']);

        $this->assertNotNull($verifyArchitecture);
        $this->assertSame('stable', $verifyArchitecture['stability']);
        $this->assertSame('verify', $verifyArchitecture['command_type']);

        $this->assertNotNull($verifyFeatureWork);
        $this->assertSame('stable', $verifyFeatureWork['stability']);
        $this->assertSame('verify', $verifyFeatureWork['command_type']);

        $this->assertNotNull($verifyDone);
        $this->assertSame('stable', $verifyDone['stability']);
        $this->assertSame('verify', $verifyDone['command_type']);

        $this->assertNotNull($testFeature);
        $this->assertSame('experimental', $testFeature['stability']);
        $this->assertSame('test', $testFeature['command_type']);
    }
}
