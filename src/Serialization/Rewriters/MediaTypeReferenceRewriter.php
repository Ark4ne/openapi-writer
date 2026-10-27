<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Serialization\Rewriters;

use Ark4ne\OpenApiWriter\Objects\SpecObject;
use Ark4ne\OpenApiWriter\Serialization\Context;

/**
 * A Reference in a `content` map (Media Type Object or Reference): unsupported before 3.2.
 */
final class MediaTypeReferenceRewriter implements Rewriter
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
