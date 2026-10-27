<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Serialization\Rewriters;

use Ark4ne\OpenApiWriter\Enums\Version;
use Ark4ne\OpenApiWriter\Objects\Schema;
use Ark4ne\OpenApiWriter\Objects\SpecObject;
use Ark4ne\OpenApiWriter\Serialization\Context;

/**
 * `examples(a, b)` on a Schema: `example: a` in 3.0, `examples: [a, b]` from 3.1. An `example` set next to it wins.
 */
final class SchemaExamplesRewriter implements Rewriter
{
    public function supports(SpecObject $object): bool
    {
        return $object instanceof Schema && $object->has('examples');
    }

    public function rewrite(SpecObject $object, array $node, Context $context): array
    {
        if ($context->version->isAtLeast(Version::V3_1_0) || !is_array($node['examples'] ?? null)) {
            return $node;
        }

        if (!array_key_exists('example', $node) && $node['examples'] !== []) {
            $node['example'] = $node['examples'][array_key_first($node['examples'])];
        }

        unset($node['examples']);
        $context->converted('examples written as one example');

        return $node;
    }
}
