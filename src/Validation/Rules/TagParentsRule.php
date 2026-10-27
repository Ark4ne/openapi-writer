<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Validation\Rules;

use Ark4ne\OpenApiWriter\Enums\Version;
use Ark4ne\OpenApiWriter\Support\JsonPointer;
use Ark4ne\OpenApiWriter\Validation\Rule;
use Ark4ne\OpenApiWriter\Validation\Violation;
use Ark4ne\OpenApiWriter\Validation\Violations;

/**
 * From 3.2, each `Tag.parent` names a declared tag, without cycle.
 */
final class TagParentsRule implements Rule
{
    public function check(array $document, Version $version, Violations $violations): void
    {
        if (!$version->isAtLeast(Version::V3_2_0)) {
            return;
        }

        $parents = [];

        // A name declared twice keeps the parents of both declarations.
        foreach (Document::entries($document['tags'] ?? null) as $tag) {
            if (is_array($tag) && is_string($tag['name'] ?? null)) {
                $parents[$tag['name']] ??= [];

                if (is_string($tag['parent'] ?? null)) {
                    $parents[$tag['name']][] = $tag['parent'];
                }
            }
        }

        foreach (Document::entries($document['tags'] ?? null) as $index => $tag) {
            if (!is_array($tag) || !is_string($tag['name'] ?? null) || !is_string($tag['parent'] ?? null)) {
                continue;
            }

            $pointer = JsonPointer::root()->append('tags', $index, 'parent');

            if (!array_key_exists($tag['parent'], $parents)) {
                $violations->add(new Violation($pointer, self::class, sprintf('parent tag "%s" is not declared', $tag['parent'])));
            } elseif (self::isAncestor($tag['name'], $tag['parent'], $parents)) {
                $violations->add(new Violation($pointer, self::class, sprintf('tag "%s" is its own ancestor', $tag['name'])));
            }
        }
    }

    /**
     * Whether $name is $parent or one of its ancestors.
     *
     * @param array<string, list<string>> $parents parents of each declared tag
     */
    private static function isAncestor(string $name, string $parent, array $parents): bool
    {
        $pending = [$parent];
        $seen = [];

        while ($pending !== []) {
            $ancestor = array_pop($pending);

            if ($ancestor === $name) {
                return true;
            }

            if (!isset($seen[$ancestor])) {
                $seen[$ancestor] = true;
                $pending = [...$pending, ...$parents[$ancestor] ?? []];
            }
        }

        return false;
    }
}
