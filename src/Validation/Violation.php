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

    /**
     * `[error] /paths/~1pets/get/operationId: operationId "list" is already used (UniqueOperationIdRule)`.
     */
    public function __toString(): string
    {
        $rule = strrchr($this->rule, '\\');

        return sprintf('[%s] %s: %s (%s)', $this->severity->value, $this->pointer->toString(), $this->message, $rule === false ? $this->rule : substr($rule, 1));
    }
}
