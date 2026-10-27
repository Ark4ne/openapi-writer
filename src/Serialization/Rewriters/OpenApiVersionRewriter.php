<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Serialization\Rewriters;

use Ark4ne\OpenApiWriter\Objects\OpenApi;
use Ark4ne\OpenApiWriter\Objects\SpecObject;
use Ark4ne\OpenApiWriter\Serialization\Context;

/**
 * Writes the target version in the `openapi` field of the document, first.
 */
final class OpenApiVersionRewriter implements Rewriter
{
    public function supports(SpecObject $object): bool
    {
        return $object instanceof OpenApi;
    }

    public function rewrite(SpecObject $object, array $node, Context $context): array
    {
        return ['openapi' => $context->version->value] + $node;
    }
}
