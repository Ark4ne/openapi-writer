<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Serialization\Rewriters;

use Ark4ne\OpenApiWriter\Enums\VendorExtensions;
use Ark4ne\OpenApiWriter\Enums\Version;
use Ark4ne\OpenApiWriter\Objects\OpenApi;
use Ark4ne\OpenApiWriter\Objects\SpecObject;
use Ark4ne\OpenApiWriter\Serialization\Context;

/**
 * Webhooks before 3.1, with `VendorExtensions::Redoc`: written unchanged under the root extension `x-webhooks`. An
 * `x-webhooks` defined by the document wins: the webhooks then stay unsupported.
 */
final class WebhooksExtensionRewriter implements Rewriter
{
    public function supports(SpecObject $object): bool
    {
        return $object instanceof OpenApi && $object->has('webhooks');
    }

    public function rewrite(SpecObject $object, array $node, Context $context): array
    {
        if (
            $context->extensions !== VendorExtensions::Redoc
            || $context->version->isAtLeast(Version::V3_1_0)
            || !array_key_exists('webhooks', $node)
            || array_key_exists('x-webhooks', $node)
        ) {
            return $node;
        }

        $node['x-webhooks'] = $node['webhooks'];
        unset($node['webhooks']);
        $context->at('webhooks')->converted('webhooks written as x-webhooks');

        return $node;
    }
}
