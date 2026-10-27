<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Serialization;

use Ark4ne\OpenApiWriter\Support\JsonPointer;
use Stringable;

/**
 * One conversion or removal made while writing a document.
 */
final readonly class Diagnostic implements Stringable
{
    public function __construct(
        public DiagnosticLevel $level,
        public JsonPointer $pointer,
        public string $message,
    ) {}

    /**
     * `[unsupported] /webhooks: webhooks needs 3.1.0`.
     */
    public function __toString(): string
    {
        return sprintf('[%s] %s: %s', $this->level->value, $this->pointer->toString(), $this->message);
    }
}
