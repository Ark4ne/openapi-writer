<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Tests\Support;

use Ark4ne\OpenApiWriter\Enums\Version;
use Ark4ne\OpenApiWriter\Support\JsonPointer;
use Ark4ne\OpenApiWriter\Validation\Rule;
use Ark4ne\OpenApiWriter\Validation\Severity;
use Ark4ne\OpenApiWriter\Validation\Violation;
use Ark4ne\OpenApiWriter\Validation\Violations;

/**
 * A custom rule: warns when the Info has no summary.
 */
final class SummaryRequiredRule implements Rule
{
    public function check(array $document, Version $version, Violations $violations): void
    {
        $info = $document['info'] ?? [];

        if (is_array($info) && !isset($info['summary'])) {
            $violations->add(new Violation(JsonPointer::fromString('/info'), 'summary-required', 'Info has no summary', Severity::Warning));
        }
    }
}
