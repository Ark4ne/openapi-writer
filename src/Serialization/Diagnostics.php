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

    public function add(Diagnostic $diagnostic): void {}

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
        return [];
    }

    public function isEmpty(): bool
    {
        return false;
    }

    public function count(): int
    {
        return 0;
    }

    /**
     * @return Traversable<int, Diagnostic>
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->diagnostics);
    }
}
