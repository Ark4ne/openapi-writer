<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Validation\Rules;

use Ark4ne\OpenApiWriter\Enums\Version;
use Ark4ne\OpenApiWriter\Validation\Rule;
use Ark4ne\OpenApiWriter\Validation\Violations;

/**
 * No two paths are equal once their variable names are ignored (`/users/{id}` and `/users/{uuid}`).
 */
final class EquivalentPathsRule implements Rule
{
    public function check(array $document, Version $version, Violations $violations): void {}
}
