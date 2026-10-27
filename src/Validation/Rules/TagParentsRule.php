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

        foreach (Document::entries($document['tags'] ?? null) as $tag) {
            if (is_array($tag) && is_string($tag['name'] ?? null)) {
                $parents[$tag['name']] = is_string($tag['parent'] ?? null) ? $tag['parent'] : null;
            }
        }

        foreach (Document::entries($document['tags'] ?? null) as $index => $tag) {
            if (!is_array($tag) || !is_string($tag['name'] ?? null) || !is_string($tag['parent'] ?? null)) {
                continue;
            }

            $pointer = JsonPointer::root()->append('tags', $index, 'parent');

            if (!array_key_exists($tag['parent'], $parents)) {
                $violations->add(new Violation($pointer, self::class, sprintf('parent tag "%s" is not declared', $tag['parent'])));
            } elseif (self::inCycle($tag['name'], $parents)) {
                $violations->add(new Violation($pointer, self::class, sprintf('tag "%s" is its own ancestor', $tag['name'])));
            }
        }
    }

    /**
     * @param array<string, string|null> $parents parent of each declared tag
     */
    private static function inCycle(string $name, array $parents): bool
    {
        $ancestor = $parents[$name] ?? null;

        // A tag outside the cycle reaches it, but never comes back to itself: count($parents) steps are enough.
        for ($step = 0; $ancestor !== null && $step < count($parents); ++$step) {
            if ($ancestor === $name) {
                return true;
            }

            $ancestor = $parents[$ancestor] ?? null;
        }

        return false;
    }
}
