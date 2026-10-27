<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Serialization\Encoders;

use Ark4ne\OpenApiWriter\Exceptions\MissingDependencyException;
use Symfony\Component\Yaml\Yaml;

/**
 * YAML through `symfony/yaml`, a suggested dependency.
 */
final readonly class SymfonyYamlEncoder implements Encoder
{
    /**
     * Empty objects stay `{}` and empty lists `[]`, multi-line strings are literal blocks, status codes are quoted.
     */
    private const FLAGS = Yaml::DUMP_OBJECT_AS_MAP | Yaml::DUMP_EMPTY_ARRAY_AS_SEQUENCE | Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK
        | Yaml::DUMP_NUMERIC_KEY_AS_STRING;

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
        if (!class_exists(Yaml::class)) {
            throw MissingDependencyException::forPackage('symfony/yaml', 'write YAML');
        }

        return Yaml::dump($document, $this->inline, $this->indent, self::FLAGS);
    }
}
