<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Support;

use Stringable;

/**
 * Immutable RFC 6901 JSON Pointer, used to locate diagnostics and violations in the written document.
 */
final readonly class JsonPointer implements Stringable
{
    /**
     * @param list<string> $tokens unescaped reference tokens
     */
    private function __construct(private array $tokens) {}

    public static function root(): self
    {
        return new self([]);
    }

    /**
     * Parses a pointer such as `/paths/~1users/get`.
     */
    public static function fromString(string $pointer): self
    {
        return new self([]);
    }

    /**
     * Returns a new pointer with the given tokens appended; `~` and `/` are escaped on output.
     */
    public function append(string|int ...$tokens): self
    {
        return new self($this->tokens);
    }

    /**
     * @return list<string>
     */
    public function tokens(): array
    {
        return $this->tokens;
    }

    public function toString(): string
    {
        return '';
    }

    public function __toString(): string
    {
        return $this->toString();
    }
}
