<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Objects;

use Ark4ne\OpenApiWriter\Contracts\Extensible;
use Ark4ne\OpenApiWriter\Exceptions\InvalidArgumentException;

/**
 * Base class of the objects that the specification declares extensible with `x-*` fields.
 *
 * @property-read array<string, mixed> $extensions keyed by full name (`x-logo`), in the order they were added
 */
abstract class ExtensibleSpecObject extends SpecObject implements Extensible
{
    /**
     * @var array<string, mixed>
     */
    protected array $extensions = [];

    /**
     * @throws InvalidArgumentException when the name is empty
     */
    public function x(string $name, mixed $value): static
    {
        $name = self::extensionName($name);

        if ($name === 'x-') {
            throw InvalidArgumentException::forField(static::class, 'x', 'an extension needs a name after "x-"');
        }

        $clone = clone $this;
        $clone->extensions[$name] = $value;

        return $clone;
    }

    public function withoutX(string $name): static
    {
        $clone = clone $this;
        unset($clone->extensions[self::extensionName($name)]);

        return $clone;
    }

    public function __get(string $name): mixed
    {
        return $name === 'extensions' ? $this->extensions : parent::__get($name);
    }

    public function __isset(string $name): bool
    {
        return $name === 'extensions' ? $this->extensions !== [] : parent::__isset($name);
    }

    private static function extensionName(string $name): string
    {
        return str_starts_with($name, 'x-') ? $name : 'x-' . $name;
    }
}
