<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Exceptions;

use InvalidArgumentException;

/**
 * An object without objectId where one is needed: in a map, or as the target of `Reference::for()`.
 */
final class MissingObjectIdException extends InvalidArgumentException implements OpenApiWriterException
{
    /**
     * @param class-string $class
     */
    public static function forField(string $class, string $field): self
    {
        return new self(sprintf('%s::%s: an entry has no objectId', ClassName::short($class), $field));
    }

    /**
     * @param class-string $class
     */
    public static function forReference(string $class): self
    {
        return new self(sprintf('Reference::for: the %s has no objectId', ClassName::short($class)));
    }
}
