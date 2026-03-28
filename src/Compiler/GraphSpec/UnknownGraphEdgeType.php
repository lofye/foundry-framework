<?php

declare(strict_types=1);

namespace Foundry\Compiler\GraphSpec;

final class UnknownGraphEdgeType extends \RuntimeException
{
    public function __construct(string $type, ?string $edgeId = null)
    {
        parent::__construct(sprintf(
            'Unknown graph edge type %s cannot be validated%s.',
            $type !== '' ? $type : '(empty)',
            $edgeId !== null && $edgeId !== '' ? ' (edge ' . $edgeId . ')' : '',
        ));
    }
}
