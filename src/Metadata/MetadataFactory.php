<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Metadata;

use BackedEnum;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionProperty;
use ReflectionUnionType;

/**
 * Reads the `#[Field]` attributes of a class once and caches the result (Flyweight). The cache is the only static
 * state of the library: it is pure and deterministic.
 */
final class MetadataFactory
{
    /**
     * @var array<class-string, ClassMetadata>
     */
    private static array $cache = [];

    /**
     * @param class-string $class
     */
    public static function for(string $class): ClassMetadata
    {
        return self::$cache[$class] ??= self::read($class);
    }

    /**
     * @param class-string $class
     */
    private static function read(string $class): ClassMetadata
    {
        $fields = [];
        $properties = [];
        $enums = [];
        $freeValues = [];

        foreach ((new ReflectionClass($class))->getProperties() as $property) {
            $attributes = $property->getAttributes(Field::class);

            if ($attributes === []) {
                continue;
            }

            $name = $property->getName();
            $fields[$name] = $attributes[0]->newInstance();
            $properties[$name] = $property;
            $types = self::types($property);
            $freeValues[$name] = in_array('mixed', $types, true);
            $enums[$name] = null;

            foreach ($types as $type) {
                if (is_subclass_of($type, BackedEnum::class)) {
                    $enums[$name] = $type;
                }
            }
        }

        return new ClassMetadata($class, $fields, $properties, $enums, $freeValues);
    }

    /**
     * @return list<string>
     */
    private static function types(ReflectionProperty $property): array
    {
        $type = $property->getType();
        $types = $type instanceof ReflectionUnionType ? $type->getTypes() : [$type];

        return array_values(array_map(
            static fn(ReflectionNamedType $type): string => $type->getName(),
            array_filter($types, static fn(mixed $type): bool => $type instanceof ReflectionNamedType),
        ));
    }
}
