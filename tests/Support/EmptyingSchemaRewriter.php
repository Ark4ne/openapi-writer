<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Tests\Support;

use Ark4ne\OpenApiWriter\Objects\Schema;
use Ark4ne\OpenApiWriter\Objects\SpecObject;
use Ark4ne\OpenApiWriter\Serialization\Context;
use Ark4ne\OpenApiWriter\Serialization\Rewriters\Rewriter;

/**
 * A custom rewriter that supports only schemas, and empties them.
 */
final class EmptyingSchemaRewriter implements Rewriter
{
    public function supports(SpecObject $object): bool
    {
        return $object instanceof Schema;
    }

    public function rewrite(SpecObject $object, array $node, Context $context): array
    {
        return [];
    }
}
