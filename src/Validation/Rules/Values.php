<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Validation\Rules;

/**
 * Tells the rules which nodes of a written document hold free values (`example`, `default`, extensions…), where a
 * `$ref` or a `schema` key is data, not structure.
 *
 * @internal
 */
final class Values
{
    /**
     * Fields whose value is free in every object that has them.
     */
    private const FREE = ['example', 'default', 'const', 'enum', 'value', 'dataValue'];

    /**
     * Fields whose keys are names chosen by the author: a key `default` or `x-name` there is an entry, not a field.
     */
    private const MAPS_OF_NAMES = [
        'properties', 'patternProperties', '$defs', 'definitions', 'dependentSchemas', 'schemas', 'responses', 'parameters',
        'examples', 'requestBodies', 'headers', 'securitySchemes', 'links', 'callbacks', 'pathItems', 'mediaTypes',
        'content', 'encoding', 'webhooks', 'scopes', 'variables', 'mapping', 'additionalOperations',
    ];

    /**
     * True when the field $key of a node found under $parentKey holds a free value: a free field, an extension, or the
     * `examples` list of a schema.
     *
     * @param array<mixed> $value
     */
    public static function isFree(int|string|null $parentKey, int|string $key, array $value): bool
    {
        if (self::isMapOfNames($parentKey)) {
            return false;
        }

        return in_array($key, self::FREE, true) || Document::isExtension($key) || $key === 'examples' && array_is_list($value);
    }

    public static function isMapOfNames(int|string|null $key): bool
    {
        return in_array($key, self::MAPS_OF_NAMES, true);
    }
}
