<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Serialization\Rewriters;

use Ark4ne\OpenApiWriter\Enums\Version;
use Ark4ne\OpenApiWriter\Objects\ExtensibleSpecObject;
use Ark4ne\OpenApiWriter\Objects\SpecObject;
use Ark4ne\OpenApiWriter\Serialization\Context;

/**
 * Extensions with the reserved prefixes `x-oai-` and `x-oas-`: unsupported from 3.1.
 */
final class ReservedExtensionRewriter implements Rewriter
{
    public function supports(SpecObject $object): bool
    {
        return $object instanceof ExtensibleSpecObject;
    }

    public function rewrite(SpecObject $object, array $node, Context $context): array
    {
        if (!$context->version->isAtLeast(Version::V3_1_0)) {
            return $node;
        }

        if (!$object instanceof ExtensibleSpecObject) {
            return $node;
        }

        foreach (array_keys($object->extensions) as $name) {
            if (str_starts_with($name, 'x-oai-') || str_starts_with($name, 'x-oas-')) {
                unset($node[$name]);
                $context->at($name)->unsupported('the x-oai- and x-oas- prefixes are reserved from 3.1.0');
            }
        }

        return $node;
    }
}
