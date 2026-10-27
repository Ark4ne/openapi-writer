<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Validation;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use Traversable;

/**
 * Collects every violation of one validation (Notification). No error means a valid document.
 *
 * @implements IteratorAggregate<int, Violation>
 */
final class Violations implements Countable, IteratorAggregate
{
    /**
     * @var list<Violation>
     */
    private array $violations = [];

    public function add(Violation $violation): void
    {
        $this->violations[] = $violation;
    }

    /**
     * @return list<Violation>
     */
    public function all(): array
    {
        return $this->violations;
    }

    /**
     * @return list<Violation>
     */
    public function errors(): array
    {
        return $this->ofSeverity(Severity::Error);
    }

    /**
     * @return list<Violation>
     */
    public function warnings(): array
    {
        return $this->ofSeverity(Severity::Warning);
    }

    /**
     * True when there is no error; warnings do not count.
     */
    public function isValid(): bool
    {
        return $this->errors() === [];
    }

    public function count(): int
    {
        return count($this->violations);
    }

    /**
     * @return Traversable<int, Violation>
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->violations);
    }

    /**
     * @return list<Violation>
     */
    private function ofSeverity(Severity $severity): array
    {
        return array_values(array_filter($this->violations, static fn(Violation $violation): bool => $violation->severity === $severity));
    }
}
