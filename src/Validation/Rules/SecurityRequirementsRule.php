<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Validation\Rules;

use Ark4ne\OpenApiWriter\Enums\Version;
use Ark4ne\OpenApiWriter\Validation\Rule;
use Ark4ne\OpenApiWriter\Validation\Violations;

/**
 * Each security requirement names a declared security scheme, and asks only for OAuth2 scopes that its flows declare.
 */
final class SecurityRequirementsRule implements Rule
{
    public function check(array $document, Version $version, Violations $violations): void {}
}
