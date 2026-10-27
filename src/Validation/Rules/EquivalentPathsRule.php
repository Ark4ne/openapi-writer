<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Validation\Rules;

use Ark4ne\OpenApiWriter\Enums\Version;
use Ark4ne\OpenApiWriter\Support\JsonPointer;
use Ark4ne\OpenApiWriter\Validation\Rule;
use Ark4ne\OpenApiWriter\Validation\Violation;
use Ark4ne\OpenApiWriter\Validation\Violations;

/**
 * No two paths are equal once their variable names are ignored (`/users/{id}` and `/users/{uuid}`).
 */
final class EquivalentPathsRule implements Rule
{
    public function check(array $document, Version $version, Violations $violations): void
    {
        $seen = [];

        foreach (array_keys(Document::entries($document['paths'] ?? null)) as $path) {
            $path = (string) $path;

            if (Document::isExtension($path)) {
                continue;
            }

            $template = (string) preg_replace('/\{[^}]*}/', '{}', $path);

            if (isset($seen[$template])) {
                $violations->add(new Violation(
                    JsonPointer::root()->append('paths', $path),
                    self::class,
                    sprintf('%s only differs from %s by the names of its variables', $path, $seen[$template]),
                ));
            } else {
                $seen[$template] = $path;
            }
        }
    }
}
