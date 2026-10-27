<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Serialization\Encoders;

/**
 * Turns a written document into text (Port).
 */
interface Encoder
{
    /**
     * @param array<string, mixed> $document
     */
    public function encode(array $document): string;
}
