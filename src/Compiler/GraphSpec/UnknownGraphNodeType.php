<?php

declare(strict_types=1);

namespace Foundry\Compiler\GraphSpec;

final class UnknownGraphNodeType extends \RuntimeException
{
    public function __construct(string $type, ?string $nodeId = null)
    {
        parent::__construct(sprintf(
            'Unknown graph node type %s cannot be deserialized%s.',
            $type !== '' ? $type : '(empty)',
            $nodeId !== null && $nodeId !== '' ? ' (node ' . $nodeId . ')' : '',
        ));
    }
}
