<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Serialization\Encoders;

use JsonException;
use stdClass;

final readonly class JsonEncoder implements Encoder
{
    public const DEFAULT_FLAGS = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION;

    /**
     * @param int $flags added to `DEFAULT_FLAGS`, e.g. `JSON_PRETTY_PRINT`
     */
    public function __construct(public int $flags = 0) {}

    /**
     * Always encodes with `JSON_THROW_ON_ERROR`, on top of `DEFAULT_FLAGS` and `$flags`.
     *
     * @throws JsonException on a value that cannot be encoded
     */
    public function encode(array $document): string
    {
        return json_encode($document === [] ? new stdClass() : $document, self::DEFAULT_FLAGS | $this->flags | JSON_THROW_ON_ERROR);
    }
}
