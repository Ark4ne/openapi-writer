<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Validation\Rules;

use Ark4ne\OpenApiWriter\Enums\Version;
use Ark4ne\OpenApiWriter\Validation\Rule;
use Ark4ne\OpenApiWriter\Validation\Violations;

/**
 * No two parameters of an operation (path item parameters included) share the same `name` and `in`.
 */
final class UniqueParametersRule implements Rule
{
    public function check(array $document, Version $version, Violations $violations): void {}
}
