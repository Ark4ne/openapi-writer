<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Exceptions;

use Ark4ne\OpenApiWriter\Serialization\Diagnostics;
use RuntimeException;

/**
 * Strict mode: the document uses features the target version cannot express. Lists all of them at once.
 */
final class UnsupportedFeatureException extends RuntimeException implements OpenApiWriterException
{
    private function __construct(string $message, private readonly Diagnostics $diagnostics)
    {
        parent::__construct($message);
    }

    /**
     * Built from the `Unsupported` diagnostics of a serialization, each with its JSON Pointer.
     */
    public static function fromDiagnostics(Diagnostics $diagnostics): self
    {
        return new self('', $diagnostics);
    }

    public function diagnostics(): Diagnostics
    {
        return $this->diagnostics;
    }
}
