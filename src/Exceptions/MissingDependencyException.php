<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Exceptions;

use LogicException;

/**
 * An optional package (suggested in composer.json) is needed for the requested feature.
 */
final class MissingDependencyException extends LogicException implements OpenApiWriterException
{
    /**
     * `symfony/yaml is required to write YAML: composer require symfony/yaml`.
     */
    public static function forPackage(string $package, string $feature): self
    {
        return new self(sprintf('%s is required to %s: composer require %s', $package, $feature, $package));
    }
}
