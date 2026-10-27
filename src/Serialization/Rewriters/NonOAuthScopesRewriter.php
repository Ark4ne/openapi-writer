<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Serialization\Rewriters;

use Ark4ne\OpenApiWriter\Objects\SpecObject;
use Ark4ne\OpenApiWriter\Serialization\Context;

/**
 * Scopes in a security requirement whose scheme is not OAuth2 / OpenID Connect: unsupported in 3.0.
 *
 * Supports the OpenApi object only: its node is the whole written document, where `components.securitySchemes`
 * gives the type of each scheme named by `security` requirements.
 */
final class NonOAuthScopesRewriter implements Rewriter
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
