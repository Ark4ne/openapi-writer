<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Serialization;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use Traversable;

/**
 * Collects every diagnostic of one serialization instead of stopping at the first (Notification).
 *
 * @implements IteratorAggregate<int, Diagnostic>
 */
final class Diagnostics implements Countable, IteratorAggregate
{
    /**
     * @var list<Diagnostic>
     */
    private array $diagnostics = [];

    public function add(Diagnostic $diagnostic): void
    {
        $this->diagnostics[] = $diagnostic;
    }

    /**
     * @return list<Diagnostic>
     */
    public function all(): array
    {
        return $this->diagnostics;
    }

    /**
     * @return list<Diagnostic>
     */
    public function ofLevel(DiagnosticLevel $level): array
    {
        return array_values(array_filter($this->diagnostics, static fn(Diagnostic $diagnostic): bool => $diagnostic->level === $level));
    }

    public function isEmpty(): bool
    {
        return $this->diagnostics === [];
    }

    public function count(): int
    {
        return count($this->diagnostics);
    }

    /**
     * @return Traversable<int, Diagnostic>
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->diagnostics);
    }
}
