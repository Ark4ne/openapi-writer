<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Serialization\Rewriters;

use Ark4ne\OpenApiWriter\Enums\VendorExtensions;
use Ark4ne\OpenApiWriter\Enums\Version;
use Ark4ne\OpenApiWriter\Objects\OpenApi;
use Ark4ne\OpenApiWriter\Objects\SpecObject;
use Ark4ne\OpenApiWriter\Objects\Tag;
use Ark4ne\OpenApiWriter\Serialization\Context;

/**
 * Tag parents before 3.2, with `VendorExtensions::Redoc`: `parent` is removed from each tag and the root extension
 * `x-tagGroups` lists the groups, `{name, tags}`, in the order of the tags. Redoc groups on one level: each tag goes
 * into the group of its topmost ancestor, named after it, a tag without parent into its own group (Redoc hides the
 * tags of no group). A parent missing from `tags` still names its group; a tag whose parents loop forms its own group.
 * An `x-tagGroups` defined by the document wins: the parents then stay unsupported.
 */
final class TagGroupsExtensionRewriter implements UnavailableFieldRewriter
{
    public function supports(SpecObject $object): bool
    {
        return ($object instanceof Tag && $object->has('parent')) || ($object instanceof OpenApi && $object->has('tags'));
    }

    public function convertsTo(SpecObject $object, string $field, Context $context): ?string
    {
        return $object instanceof Tag && $field === 'parent' && self::converts($context) ? 'parent' : null;
    }

    public function rewrite(SpecObject $object, array $node, Context $context): array
    {
        if (!self::converts($context)) {
            return $node;
        }

        if ($object instanceof Tag && array_key_exists('parent', $node)) {
            unset($node['parent']);
            $context->at('parent')->converted('parent written in x-tagGroups');
        }

        if ($object instanceof OpenApi && $object === $context->document) {
            $groups = self::groups($object->tags ?? []);

            if ($groups !== []) {
                $node['x-tagGroups'] = $groups;
            }
        }

        return $node;
    }

    /**
     * Redoc extensions requested, a version without `Tag.parent`, and an OpenApi document without its own `x-tagGroups`
     * to hold the groups.
     */
    private static function converts(Context $context): bool
    {
        return $context->extensions === VendorExtensions::Redoc
            && !$context->version->isAtLeast(Version::V3_2_0)
            && $context->document instanceof OpenApi
            && !array_key_exists('x-tagGroups', $context->document->extensions);
    }

    /**
     * @param list<Tag> $tags
     *
     * @return list<array{name: string, tags: list<string>}> no group when no tag has a parent
     */
    private static function groups(array $tags): array
    {
        $parents = [];

        foreach ($tags as $tag) {
            if (is_string($tag->name) && is_string($tag->parent)) {
                $parents[$tag->name] ??= $tag->parent;
            }
        }

        if ($parents === []) {
            return [];
        }

        $groups = [];
        $indexes = [];

        foreach ($tags as $tag) {
            if (!is_string($tag->name)) {
                continue;
            }

            $group = self::topmost($tag->name, $parents);
            $index = $indexes[$group] ??= count($groups);
            $groups[$index] ??= ['name' => $group, 'tags' => []];

            if (!in_array($tag->name, $groups[$index]['tags'], true)) {
                $groups[$index]['tags'][] = $tag->name;
            }
        }

        return array_values($groups);
    }

    /**
     * @param array<string, string> $parents
     */
    private static function topmost(string $name, array $parents): string
    {
        $seen = [$name => true];
        $current = $name;

        while (isset($parents[$current])) {
            $current = $parents[$current];

            if (isset($seen[$current])) {
                return $name;
            }

            $seen[$current] = true;
        }

        return $current;
    }
}
