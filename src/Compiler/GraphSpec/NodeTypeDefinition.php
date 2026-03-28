<?php

declare(strict_types=1);

namespace Foundry\Compiler\GraphSpec;

final readonly class NodeTypeDefinition
{
    /**
     * @param class-string<\Foundry\Compiler\IR\GraphNode> $className
     * @param array<int,string> $requiredPayloadKeys
     * @param array<int,string> $optionalPayloadKeys
     * @param array<string,string> $payloadTypes
     * @param array<int,int> $graphCompatibility
     */
    public function __construct(
        public string $type,
        public string $className,
        public string $semanticCategory,
        public string $runtimeScope,
        public array $requiredPayloadKeys,
        public array $optionalPayloadKeys = [],
        public array $payloadTypes = [],
        public bool $participatesInExecutionTopology = false,
        public bool $participatesInOwnershipTopology = false,
        public array $graphCompatibility = [],
        public bool $traceable = false,
        public bool $profileable = false,
    ) {}

    /**
     * @return array<string,mixed>
     */
    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'class' => $this->className,
            'semantic_category' => $this->semanticCategory,
            'runtime_scope' => $this->runtimeScope,
            'required_payload_keys' => $this->requiredPayloadKeys,
            'optional_payload_keys' => $this->optionalPayloadKeys,
            'payload_types' => $this->payloadTypes,
            'participates_in_execution_topology' => $this->participatesInExecutionTopology,
            'participates_in_ownership_topology' => $this->participatesInOwnershipTopology,
            'graph_compatibility' => $this->graphCompatibility,
            'traceable' => $this->traceable,
            'profileable' => $this->profileable,
        ];
    }
}
