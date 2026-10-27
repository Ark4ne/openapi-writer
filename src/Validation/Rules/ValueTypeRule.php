<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Validation\Rules;

use Ark4ne\OpenApiWriter\Enums\Version;
use Ark4ne\OpenApiWriter\Support\JsonPointer;
use Ark4ne\OpenApiWriter\Validation\Rule;
use Ark4ne\OpenApiWriter\Validation\Severity;
use Ark4ne\OpenApiWriter\Validation\Violation;
use Ark4ne\OpenApiWriter\Validation\Violations;
use stdClass;

/**
 * Each `default` and `example` of a schema matches its declared type (warning). A schema without `type` accepts any
 * value; in 3.0, `nullable: true` accepts `null`. A JSON number with a zero fraction (`1.0`) is an integer.
 */
final class ValueTypeRule implements Rule
{
    public function check(array $document, Version $version, Violations $violations): void
    {
        self::walk($document, JsonPointer::root(), Scope::root(), $version, $violations);
    }

    /**
     * Fields of a Schema Object that hold one schema, a list of schemas, or a map of schemas.
     */
    private const SUBSCHEMA = ['items', 'additionalItems', 'additionalProperties', 'not', 'contains', 'propertyNames', 'if', 'then', 'else', 'unevaluatedItems', 'unevaluatedProperties', 'contentSchema'];

    private const SUBSCHEMA_LIST = ['allOf', 'anyOf', 'oneOf', 'prefixItems'];

    private const SUBSCHEMA_MAP = ['properties', 'patternProperties', '$defs', 'definitions', 'dependentSchemas'];

    /**
     * Looks for schemas outside of schemas: `schema` and `itemSchema` fields, and `components.schemas`.
     *
     * @param array<mixed> $node
     */
    private static function walk(array $node, JsonPointer $pointer, Scope $scope, Version $version, Violations $violations): void
    {
        foreach ($node as $key => $child) {
            $childScope = $scope->enter($key, $child);

            if (!is_array($child) || $childScope === null) {
                continue;
            }

            if (in_array($key, ['schema', 'itemSchema'], true) && !$scope->isMap()) {
                self::schema($child, $pointer->append($key), $version, $violations);
            } elseif ($key === 'schemas' && $pointer->toString() === '/components') {
                foreach ($child as $name => $schema) {
                    self::schema($schema, $pointer->append($key, $name), $version, $violations);
                }
            } else {
                self::walk($child, $pointer->append($key), $childScope, $version, $violations);
            }
        }
    }

    private static function schema(mixed $schema, JsonPointer $pointer, Version $version, Violations $violations): void
    {
        if (!is_array($schema)) {
            return;
        }

        $types = self::types($schema, $version);

        foreach ($schema as $key => $value) {
            if ($types !== null && in_array($key, ['default', 'example'], true) && !self::matchesAny($value, $types)) {
                $violations->add(new Violation(
                    $pointer->append($key),
                    self::class,
                    sprintf('%s is not of the type of the schema (%s)', $key, implode(', ', $types)),
                    Severity::Warning,
                ));
            } elseif (in_array($key, self::SUBSCHEMA, true)) {
                if (is_array($value) && array_is_list($value) && $value !== []) {
                    // `items` as a list of schemas, before JSON Schema 2020-12.
                    self::schemas($value, $pointer->append($key), $version, $violations);
                } else {
                    self::schema($value, $pointer->append($key), $version, $violations);
                }
            } elseif (in_array($key, [...self::SUBSCHEMA_LIST, ...self::SUBSCHEMA_MAP], true)) {
                self::schemas($value, $pointer->append($key), $version, $violations);
            }
        }
    }

    private static function schemas(mixed $schemas, JsonPointer $pointer, Version $version, Violations $violations): void
    {
        foreach (Document::entries($schemas) as $key => $schema) {
            self::schema($schema, $pointer->append($key), $version, $violations);
        }
    }

    /**
     * The types a value of $schema may have, `null` included when 3.0 `nullable` is set; null without `type`.
     *
     * @param array<mixed> $schema
     *
     * @return list<string>|null
     */
    private static function types(array $schema, Version $version): ?array
    {
        $type = $schema['type'] ?? null;
        $types = is_string($type) ? [$type] : (is_array($type) ? array_values(array_filter($type, is_string(...))) : null);

        if ($types !== null && !$version->isAtLeast(Version::V3_1_0) && ($schema['nullable'] ?? null) === true) {
            $types[] = 'null';
        }

        return $types;
    }

    /**
     * @param list<string> $types
     */
    private static function matchesAny(mixed $value, array $types): bool
    {
        foreach ($types as $type) {
            $matches = match ($type) {
                'null' => $value === null,
                'boolean' => is_bool($value),
                'integer' => is_int($value) || is_float($value) && is_finite($value) && floor($value) === $value,
                'number' => is_int($value) || is_float($value),
                'string' => is_string($value),
                'array' => is_array($value) && array_is_list($value),
                'object' => $value instanceof stdClass || is_array($value) && !array_is_list($value),
                default => true,
            };

            if ($matches) {
                return true;
            }
        }

        return false;
    }
}
