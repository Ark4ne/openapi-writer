<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Serialization;

use Ark4ne\OpenApiWriter\Enums\Version;
use Ark4ne\OpenApiWriter\Exceptions\MissingDependencyException;

/**
 * A written document and the diagnostics collected while writing it.
 */
final readonly class SerializationResult
{
    /**
     * @param array<string, mixed> $document
     */
    public function __construct(
        private array $document,
        public Version $version,
        private Diagnostics $diagnostics,
    ) {}

    /**
     * Empty objects and maps are written as `\stdClass`, so that JSON writes `{}` and never `[]`.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->document;
    }

    /**
     * @param int $flags added to `JsonEncoder::DEFAULT_FLAGS`, e.g. `JSON_PRETTY_PRINT`
     */
    public function toJson(int $flags = 0): string
    {
        return '';
    }

    /**
     * @throws MissingDependencyException when `symfony/yaml` is not installed
     */
    public function toYaml(): string
    {
        return '';
    }

    public function diagnostics(): Diagnostics
    {
        return $this->diagnostics;
    }
}
