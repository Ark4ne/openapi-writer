<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Exceptions;

use Ark4ne\OpenApiWriter\Support\JsonPointer;
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
        return new self(sprintf('%s::%s: %s', self::shortName($class), $field, $reason));
    }

    /**
     * A string that is not a value of the enum expected by the field.
     *
     * @param class-string             $class
     * @param class-string<BackedEnum> $enum
     */
    public static function forEnumValue(string $class, string $field, string $enum, string|int $value): self
    {
        $values = array_map(static fn(BackedEnum $case): string => (string) $case->value, $enum::cases());

        return self::forField($class, $field, sprintf('"%s" is not one of %s', $value, implode(', ', $values)));
    }

    /**
     * A JSON Pointer that does not start with `/` (RFC 6901).
     */
    public static function forPointer(string $pointer): self
    {
        return new self(sprintf('"%s" is not a JSON Pointer: it must be empty or start with "/"', $pointer));
    }

    /**
     * A free value (`default`, `example`, an extension…) that the output cannot write: `NAN`, `INF`, a resource…
     */
    public static function forValue(JsonPointer $pointer, string $reason): self
    {
        return new self(sprintf('%s: %s', $pointer->toString(), $reason));
    }

    /**
     * @param class-string $class
     */
    private static function shortName(string $class): string
    {
        return substr((string) strrchr('\\' . $class, '\\'), 1);
    }
}
