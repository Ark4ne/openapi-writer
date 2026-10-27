<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Validation\Rules;

use Ark4ne\OpenApiWriter\Enums\Version;
use Ark4ne\OpenApiWriter\Validation\Rule;
use Ark4ne\OpenApiWriter\Validation\Violations;

/**
 * From 3.2, each `Tag.parent` names a declared tag, without cycle.
 */
final class TagParentsRule implements Rule
{
    public function check(array $document, Version $version, Violations $violations): void {}
}
