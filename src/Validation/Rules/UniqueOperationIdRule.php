<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Validation\Rules;

use Ark4ne\OpenApiWriter\Enums\Version;
use Ark4ne\OpenApiWriter\Validation\Rule;
use Ark4ne\OpenApiWriter\Validation\Violation;
use Ark4ne\OpenApiWriter\Validation\Violations;

/**
 * Each `operationId` is unique in the document.
 */
final class UniqueOperationIdRule implements Rule
{
    public function check(array $document, Version $version, Violations $violations): void
    {
        $seen = [];

        foreach (Document::operations($document) as [$pointer, $operation]) {
            $operationId = $operation['operationId'] ?? null;

            if (!is_string($operationId)) {
                continue;
            }

            if (isset($seen[$operationId])) {
                $violations->add(new Violation(
                    $pointer->append('operationId'),
                    self::class,
                    sprintf('operationId "%s" is already used at %s', $operationId, $seen[$operationId]),
                ));
            } else {
                $seen[$operationId] = $pointer->toString();
            }
        }
    }
}
