<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Serialization\Rewriters;

use Ark4ne\OpenApiWriter\Enums\Version;
use Ark4ne\OpenApiWriter\Objects\OpenApi;
use Ark4ne\OpenApiWriter\Objects\SpecObject;
use Ark4ne\OpenApiWriter\Serialization\Context;
use stdClass;

/**
 * No path defined: `paths: {}` in 3.0, `paths` omitted from 3.1.
 */
final class EmptyPathsRewriter implements Rewriter
{
    public function supports(SpecObject $object): bool
    {
        return $object instanceof OpenApi && !$object->has('paths');
    }

    public function rewrite(SpecObject $object, array $node, Context $context): array
    {
        if ($context->version->isAtLeast(Version::V3_1_0)) {
            return $node;
        }

        $node['paths'] = new stdClass();
        $context->at('paths')->converted('missing paths written as an empty map');

        return $node;
    }
}
