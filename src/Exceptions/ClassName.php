<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Exceptions;

/**
 * Formats class names in exception messages (`License::identifier: …`).
 *
 * @internal
 */
final class ClassName
{
    /**
     * @param class-string $class
     */
    public static function short(string $class): string
    {
        return substr((string) strrchr('\\' . $class, '\\'), 1);
    }
}
