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
        return clone $this;
    }

    public function withoutX(string $name): static
    {
        return clone $this;
    }
}
