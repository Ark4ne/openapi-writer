<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Serialization\Rewriters;

use Ark4ne\OpenApiWriter\Objects\SpecObject;
use Ark4ne\OpenApiWriter\Serialization\Context;

/**
 * `$ref` with sibling fields: `allOf: [{$ref}]` + siblings in 3.0, kept as is from 3.1.
 */
final class RefSiblingsRewriter implements Rewriter
{
    public function supports(SpecObject $object): bool
    {
        return false;
    }

    public function rewrite(SpecObject $object, array $node, Context $context): array
    {
        return $node;
    }
}
