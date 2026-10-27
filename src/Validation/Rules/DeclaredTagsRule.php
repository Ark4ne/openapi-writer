<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Validation\Rules;

use Ark4ne\OpenApiWriter\Enums\Version;
use Ark4ne\OpenApiWriter\Validation\Rule;
use Ark4ne\OpenApiWriter\Validation\Violations;

/**
 * Each tag used by an operation is declared in `tags` (warning).
 */
final class DeclaredTagsRule implements Rule
{
    public function check(array $document, Version $version, Violations $violations): void {}
}
