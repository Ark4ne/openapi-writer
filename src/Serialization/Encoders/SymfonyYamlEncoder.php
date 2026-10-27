<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Serialization\Encoders;

use Ark4ne\OpenApiWriter\Exceptions\MissingDependencyException;

/**
 * YAML through `symfony/yaml`, a suggested dependency.
 */
final readonly class SymfonyYamlEncoder implements Encoder
{
    /**
     * @param int $inline level from which YAML switches to inline notation
     * @param int $indent number of spaces per level
     */
    public function __construct(
        public int $inline = 10,
        public int $indent = 2,
    ) {}

    /**
     * @throws MissingDependencyException when `symfony/yaml` is not installed
     */
    public function encode(array $document): string
    {
        return '';
    }
}
