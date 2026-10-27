<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Serialization\Rewriters;

use Ark4ne\OpenApiWriter\Enums\Version;
use Ark4ne\OpenApiWriter\Objects\OpenApi;
use Ark4ne\OpenApiWriter\Objects\SpecObject;
use Ark4ne\OpenApiWriter\Serialization\Context;

/**
 * Scopes in a security requirement whose scheme is not OAuth2 / OpenID Connect: unsupported in 3.0, where the scopes
 * are emptied so that the requirement stays.
 *
 * Supports the OpenApi object only: its node is the whole written document, where `components.securitySchemes`
 * gives the type of each scheme named by the `security` requirements of the document, of its operations and of the
 * operations of their callbacks. A scheme written as a Reference has no known type and is left as is.
 */
final class NonOAuthScopesRewriter implements Rewriter
{
    private const METHODS = ['get', 'put', 'post', 'delete', 'options', 'head', 'patch', 'trace'];

    private const SCOPED_TYPES = ['oauth2', 'openIdConnect'];

    public function supports(SpecObject $object): bool
    {
        return $object instanceof OpenApi;
    }

    public function rewrite(SpecObject $object, array $node, Context $context): array
    {
        if ($context->version->isAtLeast(Version::V3_1_0)) {
            return $node;
        }

        $components = is_array($node['components'] ?? null) ? $node['components'] : [];
        $schemes = is_array($components['securitySchemes'] ?? null) ? $components['securitySchemes'] : [];
        $unscoped = array_keys(array_filter(
            $schemes,
            static fn(mixed $scheme): bool => is_array($scheme) && isset($scheme['type']) && !in_array($scheme['type'], self::SCOPED_TYPES, true),
        ));

        if ($unscoped === []) {
            return $node;
        }

        if (isset($node['security'])) {
            $node['security'] = self::security($node['security'], $unscoped, $context);
        }

        if (is_array($node['paths'] ?? null)) {
            $node['paths'] = self::pathItems($node['paths'], $unscoped, $context->at('paths'));
        }

        return $node;
    }

    /**
     * @param array<array-key, mixed> $pathItems
     * @param list<array-key> $unscoped
     *
     * @return array<array-key, mixed>
     */
    private static function pathItems(array $pathItems, array $unscoped, Context $context): array
    {
        foreach ($pathItems as $path => $pathItem) {
            if (!is_array($pathItem)) {
                continue;
            }

            foreach (self::METHODS as $method) {
                if (!is_array($pathItem[$method] ?? null)) {
                    continue;
                }

                $operation = $pathItem[$method];
                $at = $context->at($path, $method);

                if (isset($operation['security'])) {
                    $operation['security'] = self::security($operation['security'], $unscoped, $at);
                }

                if (is_array($operation['callbacks'] ?? null)) {
                    $callbacks = $operation['callbacks'];

                    foreach ($callbacks as $name => $callback) {
                        $callbacks[$name] = is_array($callback) ? self::pathItems($callback, $unscoped, $at->at('callbacks', $name)) : $callback;
                    }

                    $operation['callbacks'] = $callbacks;
                }

                $pathItem[$method] = $operation;
            }

            $pathItems[$path] = $pathItem;
        }

        return $pathItems;
    }

    /**
     * Empties the scopes of the unscoped schemes in a `security` field.
     *
     * @param list<array-key> $unscoped
     */
    private static function security(mixed $security, array $unscoped, Context $context): mixed
    {
        if (!is_array($security)) {
            return $security;
        }

        foreach ($security as $index => $requirement) {
            if (!is_array($requirement)) {
                continue;
            }

            foreach ($unscoped as $scheme) {
                if (is_array($requirement[$scheme] ?? null) && $requirement[$scheme] !== []) {
                    $requirement[$scheme] = [];
                    $context->at('security', $index, $scheme)->unsupported('scopes on a non-OAuth2 security scheme need 3.1.0');
                }
            }

            $security[$index] = $requirement;
        }

        return $security;
    }
}
