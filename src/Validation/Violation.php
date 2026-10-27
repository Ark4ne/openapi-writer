<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Validation;

use Ark4ne\OpenApiWriter\Support\JsonPointer;
use Stringable;

/**
 * One problem found in a written document.
 */
final readonly class Violation implements Stringable
{
    /**
     * @param string $rule name of the rule that found it (its class name for the built-in rules)
     */
    public function __construct(
        public JsonPointer $pointer,
        public string $rule,
        public string $message,
        public Severity $severity = Severity::Error,
    ) {}

    public function __toString(): string
    {
        return '';
    }
}
