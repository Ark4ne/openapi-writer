<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Exceptions;

use BackedEnum;

/**
 * A value that the specification forbids in every version.
 */
final class InvalidArgumentException extends \InvalidArgumentException implements OpenApiWriterException
{
    /**
     * `Info::license: url and identifier are mutually exclusive`.
     *
     * @param class-string $class
     */
    public static function forField(string $class, string $field, string $reason): self
    {
        return new self();
    }

    /**
     * A string that is not a value of the enum expected by the field.
     *
     * @param class-string             $class
     * @param class-string<BackedEnum> $enum
     */
    public static function forEnumValue(string $class, string $field, string $enum, string|int $value): self
    {
        return new self();
    }
}
