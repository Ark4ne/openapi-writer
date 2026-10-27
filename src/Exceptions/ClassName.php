<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Exceptions;

/**
 * Formats class names in messages (`License::identifier: …`, `(UniqueOperationIdRule)`).
 *
 * @internal
 */
final class ClassName
{
    /**
     * The name without its namespace; a name without namespace is returned as is.
     */
    public static function short(string $class): string
    {
        return substr((string) strrchr('\\' . $class, '\\'), 1);
    }
}
