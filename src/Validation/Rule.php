<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Validation;

use Ark4ne\OpenApiWriter\Enums\Version;

/**
 * One semantic rule that the official schema does not check. Works on the written document, not on the model.
 */
interface Rule
{
    /**
     * @param array<string, mixed> $document the document as written for $version
     */
    public function check(array $document, Version $version, Violations $violations): void;
}
