<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Serialization\Rewriters;

use Ark4ne\OpenApiWriter\Enums\Version;
use Ark4ne\OpenApiWriter\Objects\Response;
use Ark4ne\OpenApiWriter\Objects\SpecObject;
use Ark4ne\OpenApiWriter\Serialization\Context;

/**
 * Response without description: `description: ""` before 3.2, omitted in 3.2.
 */
final class ResponseDescriptionRewriter implements Rewriter
{
    public function supports(SpecObject $object): bool
    {
        return $object instanceof Response && !$object->has('description');
    }

    public function rewrite(SpecObject $object, array $node, Context $context): array
    {
        if ($context->version->isAtLeast(Version::V3_2_0)) {
            return $node;
        }

        $node['description'] = '';
        $context->converted('missing description written as an empty string');

        return $node;
    }
}
