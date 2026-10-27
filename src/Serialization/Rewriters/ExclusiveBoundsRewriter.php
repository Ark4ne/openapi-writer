<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Serialization\Rewriters;

use Ark4ne\OpenApiWriter\Enums\Version;
use Ark4ne\OpenApiWriter\Objects\Schema;
use Ark4ne\OpenApiWriter\Objects\SpecObject;
use Ark4ne\OpenApiWriter\Serialization\Context;

/**
 * `exclusiveMinimum(n)` / `exclusiveMaximum(n)`: `minimum: n` + `exclusiveMinimum: true` in 3.0, numeric from 3.1.
 * Next to an inclusive bound, the stricter of both is kept.
 */
final class ExclusiveBoundsRewriter implements Rewriter
{
    public function supports(SpecObject $object): bool
    {
        return $object instanceof Schema && ($object->has('exclusiveMinimum') || $object->has('exclusiveMaximum'));
    }

    public function rewrite(SpecObject $object, array $node, Context $context): array
    {
        if ($context->version->isAtLeast(Version::V3_1_0)) {
            return $node;
        }

        foreach (['exclusiveMinimum' => ['minimum', 1], 'exclusiveMaximum' => ['maximum', -1]] as $exclusive => [$inclusive, $direction]) {
            $bound = $node[$exclusive] ?? null;

            if (!is_int($bound) && !is_float($bound)) {
                continue;
            }

            $current = $node[$inclusive] ?? null;

            if ((is_int($current) || is_float($current)) && ($current <=> $bound) === $direction) {
                unset($node[$exclusive]);
            } else {
                $node[$inclusive] = $bound;
                $node[$exclusive] = true;
            }
        }

        $context->converted('numeric exclusive bounds written as boolean flags');

        return $node;
    }
}
