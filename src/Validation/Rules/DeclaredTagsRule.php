<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Validation\Rules;

use Ark4ne\OpenApiWriter\Enums\Version;
use Ark4ne\OpenApiWriter\Validation\Rule;
use Ark4ne\OpenApiWriter\Validation\Severity;
use Ark4ne\OpenApiWriter\Validation\Violation;
use Ark4ne\OpenApiWriter\Validation\Violations;

/**
 * Each tag used by an operation is declared in `tags` (warning).
 */
final class DeclaredTagsRule implements Rule
{
    public function check(array $document, Version $version, Violations $violations): void
    {
        $declared = [];

        foreach (Document::entries($document['tags'] ?? null) as $tag) {
            if (is_array($tag) && is_string($tag['name'] ?? null)) {
                $declared[$tag['name']] = true;
            }
        }

        foreach (Document::operations($document, $version) as [$pointer, $operation]) {
            foreach (Document::entries($operation['tags'] ?? null) as $index => $tag) {
                if (is_string($tag) && !isset($declared[$tag])) {
                    $violations->add(new Violation(
                        $pointer->append('tags', $index),
                        self::class,
                        sprintf('tag "%s" is not declared in tags', $tag),
                        Severity::Warning,
                    ));
                }
            }
        }
    }
}
