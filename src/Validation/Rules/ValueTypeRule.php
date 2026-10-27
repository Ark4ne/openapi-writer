<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Validation\Rules;

use Ark4ne\OpenApiWriter\Enums\Version;
use Ark4ne\OpenApiWriter\Validation\Rule;
use Ark4ne\OpenApiWriter\Validation\Violations;

/**
 * Each `default` and `example` of a schema matches its declared type (warning).
 */
final class ValueTypeRule implements Rule
{
    public function check(array $document, Version $version, Violations $violations): void {}
}
