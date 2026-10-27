<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Serialization\Rewriters;

use Ark4ne\OpenApiWriter\Enums\Version;
use Ark4ne\OpenApiWriter\Objects\Schema;
use Ark4ne\OpenApiWriter\Objects\SpecObject;
use Ark4ne\OpenApiWriter\Serialization\Context;

/**
 * Several non-null types: `anyOf` of one schema per type in 3.0, `type: [...]` from 3.1. A single type is written as a
 * string. In 3.0, `nullable: true` moves into each branch, and an `anyOf` already present is kept next to an
 * `allOf: [{anyOf: types}]`.
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

        if (!is_array($types)) {
            return $node;
        }

        if (count($types) === 1) {
            $node['type'] = $types[array_key_first($types)];

            return $node;
        }

        if ($context->version->isAtLeast(Version::V3_1_0)) {
            return $node;
        }

        $nullable = ($node['nullable'] ?? false) === true;
        $branches = array_map(static fn(mixed $type): array => $nullable ? ['type' => $type, 'nullable' => true] : ['type' => $type], array_values($types));
        unset($node['type'], $node['nullable']);

        if (isset($node['anyOf'])) {
            $node['allOf'] = [...(is_array($node['allOf'] ?? null) ? $node['allOf'] : []), ['anyOf' => $branches]];
        } else {
            $node['anyOf'] = $branches;
        }

        $context->converted('several types written as anyOf');

        return $node;
    }
}
