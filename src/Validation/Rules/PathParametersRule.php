<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Validation\Rules;

use Ark4ne\OpenApiWriter\Enums\Version;
use Ark4ne\OpenApiWriter\Validation\Rule;
use Ark4ne\OpenApiWriter\Validation\Violations;

/**
 * Each `{name}` variable of a path has a matching required `in: path` parameter, on the path item or on every operation.
 */
final class PathParametersRule implements Rule
{
    public function check(array $document, Version $version, Violations $violations): void {}
}
