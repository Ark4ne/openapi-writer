<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Serialization\Rewriters;

use Ark4ne\OpenApiWriter\Objects\Schema;
use Ark4ne\OpenApiWriter\Objects\SpecObject;
use Ark4ne\OpenApiWriter\Serialization\Context;

/**
 * Several non-null types: `anyOf` of one schema per type in 3.0, `type: [...]` from 3.1.
 */
final class MultiTypeRewriter implements Rewriter
{
    public function supports(SpecObject $object): bool
    {
        return $object instanceof Schema && $object->has('type');
    }

    public function rewrite(SpecObject $object, array $node, Context $context): array
    {
        $types = $node['type'] ?? null;

        if (is_array($types) && count($types) === 1) {
            $node['type'] = $types[0];
        }

        return $node;
    }
}
