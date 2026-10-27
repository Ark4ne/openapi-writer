<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Validation;

use Ark4ne\OpenApiWriter\Enums\Version;

/**
 * Validation against the official JSON Schema of a version (Port). Its implementation needs a suggested package.
 */
interface JsonSchemaValidator
{
    public function validate(string $json, Version $version, Violations $violations): void;
}
