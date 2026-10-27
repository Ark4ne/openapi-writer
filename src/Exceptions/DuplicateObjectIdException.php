<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Exceptions;

use InvalidArgumentException;

/**
 * Two objects with the same objectId in one map: an entry is never replaced silently.
 */
final class DuplicateObjectIdException extends InvalidArgumentException implements OpenApiWriterException
{
    /**
     * @param class-string $class
     */
    public static function forField(string $class, string $field, string $objectId): self
    {
        return new self();
    }
}
