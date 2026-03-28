<?php

declare(strict_types=1);

namespace Foundry\Compiler\GraphSpec;

final readonly class EdgeTypeDefinition
{
    /**
     * @param array<int,string> $allowedSourceTypes
     * @param array<int,string> $allowedTargetTypes
     * @param array<int,string> $requiredPayloadKeys
     * @param array<string,string> $payloadTypes
     * @param array<int,string> $roles
     */
    public function __construct(
        public string $type,
        public string $semanticClass,
        public array $allowedSourceTypes,
        public array $allowedTargetTypes,
        public string $multiplicity,
        public bool $payloadAllowed = false,
        public array $requiredPayloadKeys = [],
        public array $payloadTypes = [],
        public array $roles = [],
    ) {}

    public function allowsSourceType(string $type): bool
    {
        return $this->allowedSourceTypes === ['*'] || in_array($type, $this->allowedSourceTypes, true);
    }

    public function allowsTargetType(string $type): bool
    {
        return $this->allowedTargetTypes === ['*'] || in_array($type, $this->allowedTargetTypes, true);
    }

    public function hasRole(string $role): bool
    {
        return in_array($role, $this->roles, true);
    }

    /**
     * @return array<string,mixed>
     */
    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'semantic_class' => $this->semanticClass,
            'allowed_source_types' => $this->allowedSourceTypes,
            'allowed_target_types' => $this->allowedTargetTypes,
            'multiplicity' => $this->multiplicity,
            'payload_allowed' => $this->payloadAllowed,
            'required_payload_keys' => $this->requiredPayloadKeys,
            'payload_types' => $this->payloadTypes,
            'roles' => $this->roles,
        ];
    }
}
