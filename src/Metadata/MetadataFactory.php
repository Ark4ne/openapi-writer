<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Metadata;

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
        return self::$cache[$class] ?? new ClassMetadata($class, []);
    }
}
