<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Contracts;

/**
 * Object that accepts specification extensions (`x-*` fields).
 */
interface Extensible
{
    /**
     * Sets the extension `x-<name>`; the `x-` prefix is added when missing. Extensions are written last, in the
     * order they were added.
     */
    public function x(string $name, mixed $value): static;

    public function withoutX(string $name): static;
}
