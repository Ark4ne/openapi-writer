<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Validation\Rules;

use Ark4ne\OpenApiWriter\Support\JsonPointer;
use Generator;

/**
 * Reads a written document for the rules: its path items and operations in document order, and its local references.
 * A rule may run on a document that the official schema rejects, so every access checks the shape of the node.
 *
 * @internal
 */
final class Document
{
    /**
     * Fixed methods of a Path Item, every version included (`query` exists only from 3.2).
     */
    private const METHODS = ['get', 'put', 'post', 'delete', 'options', 'head', 'patch', 'trace', 'query'];

    /**
     * Every path item written in place, in document order: `paths`, `webhooks`, then the path items and callbacks of
     * `components`, each followed by the path items of the callbacks of its operations. The target of a path item
     * `$ref` is not followed, but the fields written next to the `$ref` are listed.
     *
     * @param array<string, mixed> $document
     *
     * @return Generator<int, array{JsonPointer, array<mixed>}>
     */
    public static function pathItems(array $document): Generator
    {
        foreach (self::roots($document) as [$pointer, $pathItem]) {
            yield from self::withCallbacks($pathItem, $pointer);
        }
    }

    /**
     * Every operation written in the document, in document order: each one is followed by the operations of its callbacks.
     *
     * @param array<string, mixed> $document
     *
     * @return Generator<int, array{JsonPointer, array<mixed>}>
     */
    public static function operations(array $document): Generator
    {
        foreach (self::roots($document) as [$pointer, $pathItem]) {
            yield from self::operationsWithCallbacks($pathItem, $pointer);
        }
    }

    /**
     * The operations of one path item, fixed methods and `additionalOperations`, in document order.
     *
     * @param array<mixed> $pathItem
     *
     * @return Generator<int, array{JsonPointer, array<mixed>}>
     */
    public static function operationsOf(array $pathItem, JsonPointer $pointer): Generator
    {
        foreach ($pathItem as $key => $value) {
            if (in_array($key, self::METHODS, true) && is_array($value)) {
                yield [$pointer->append($key), $value];
            } elseif ($key === 'additionalOperations') {
                foreach (self::entries($value) as $method => $operation) {
                    if (is_array($operation)) {
                        yield [$pointer->append($key, $method), $operation];
                    }
                }
            }
        }
    }

    /**
     * The node a local reference (`#`, `#/components/schemas/User`) points to, in $root.
     *
     * @param array<mixed> $root
     *
     * @return array{bool, mixed} whether the reference resolves, and the node
     */
    public static function find(array $root, string $reference): array
    {
        $node = $root;

        foreach (JsonPointer::fromString(rawurldecode(substr($reference, 1)))->tokens() as $token) {
            if (!is_array($node) || !array_key_exists($token, $node)) {
                return [false, null];
            }

            $node = $node[$token];
        }

        return [true, $node];
    }

    /**
     * $reference when it points to a place of the same document (`#` followed by a JSON Pointer), else null.
     */
    public static function local(mixed $reference): ?string
    {
        return is_string($reference) && ($reference === '#' || str_starts_with($reference, '#/')) ? $reference : null;
    }

    /**
     * Follows the local `$ref` of $node until a node without one; a reference that does not resolve, or a cycle,
     * gives null.
     *
     * @param array<string, mixed> $document
     */
    public static function resolve(array $document, mixed $node): mixed
    {
        return self::target($document, $node, JsonPointer::root())[1] ?? null;
    }

    /**
     * Follows the local `$ref` of $node, found at $pointer, until a node without one, and gives that node with its
     * place in the document; a reference that does not resolve, or a cycle, gives null.
     *
     * @param array<string, mixed> $document
     *
     * @return array{JsonPointer, mixed}|null
     */
    public static function target(array $document, mixed $node, JsonPointer $pointer): ?array
    {
        $seen = [];

        while (is_array($node) && isset($node['$ref'])) {
            $reference = self::local($node['$ref']);

            if ($reference === null || in_array($reference, $seen, true)) {
                return null;
            }

            $seen[] = $reference;
            [$found, $node] = self::find($document, $reference);

            if (!$found) {
                return null;
            }

            $pointer = JsonPointer::fromString(rawurldecode(substr($reference, 1)));
        }

        return [$pointer, $node];
    }

    /**
     * The entries of a map or a list; an empty map (`\stdClass`) or any other value has none.
     *
     * @return array<mixed>
     */
    public static function entries(mixed $node): array
    {
        return is_array($node) ? $node : [];
    }

    public static function isExtension(int|string $key): bool
    {
        return str_starts_with((string) $key, 'x-');
    }

    /**
     * Path items of `paths`, `webhooks` and `components`, without those of callbacks of operations.
     *
     * @param array<string, mixed> $document
     *
     * @return Generator<int, array{JsonPointer, array<mixed>}>
     */
    private static function roots(array $document): Generator
    {
        $root = JsonPointer::root();

        foreach (['paths', 'webhooks'] as $field) {
            foreach (self::entries($document[$field] ?? null) as $key => $pathItem) {
                if (!self::isExtension($key) && is_array($pathItem)) {
                    yield [$root->append($field, $key), $pathItem];
                }
            }
        }

        foreach (self::entries($document['components'] ?? null) as $section => $entries) {
            foreach (self::entries($entries) as $name => $entry) {
                $pointer = $root->append('components', $section, $name);

                if ($section === 'pathItems' && is_array($entry)) {
                    yield [$pointer, $entry];
                } elseif ($section === 'callbacks') {
                    yield from self::callback($entry, $pointer);
                }
            }
        }
    }

    /**
     * A path item, then, operation by operation, the path items of its callbacks (and of theirs).
     *
     * @param array<mixed> $pathItem
     *
     * @return Generator<int, array{JsonPointer, array<mixed>}>
     */
    private static function withCallbacks(array $pathItem, JsonPointer $pointer): Generator
    {
        yield [$pointer, $pathItem];

        foreach (self::operationsOf($pathItem, $pointer) as [$operationPointer, $operation]) {
            foreach (self::callbacksOf($operation, $operationPointer) as [$callbackPointer, $callbackPathItem]) {
                yield from self::withCallbacks($callbackPathItem, $callbackPointer);
            }
        }
    }

    /**
     * @param array<mixed> $pathItem
     *
     * @return Generator<int, array{JsonPointer, array<mixed>}>
     */
    private static function operationsWithCallbacks(array $pathItem, JsonPointer $pointer): Generator
    {
        foreach (self::operationsOf($pathItem, $pointer) as [$operationPointer, $operation]) {
            yield [$operationPointer, $operation];

            foreach (self::callbacksOf($operation, $operationPointer) as [$callbackPointer, $callbackPathItem]) {
                yield from self::operationsWithCallbacks($callbackPathItem, $callbackPointer);
            }
        }
    }

    /**
     * The path items written in place in the callbacks of an operation.
     *
     * @param array<mixed> $operation
     *
     * @return Generator<int, array{JsonPointer, array<mixed>}>
     */
    private static function callbacksOf(array $operation, JsonPointer $pointer): Generator
    {
        foreach (self::entries($operation['callbacks'] ?? null) as $name => $callback) {
            yield from self::callback($callback, $pointer->append('callbacks', $name));
        }
    }

    /**
     * The path items written in place in a callback.
     *
     * @return Generator<int, array{JsonPointer, array<mixed>}>
     */
    private static function callback(mixed $callback, JsonPointer $pointer): Generator
    {
        // A Callback written as a Reference Object has no path item next to its `$ref`.
        if (!is_array($callback) || isset($callback['$ref'])) {
            return;
        }

        foreach ($callback as $expression => $pathItem) {
            if (!self::isExtension($expression) && is_array($pathItem)) {
                yield [$pointer->append($expression), $pathItem];
            }
        }
    }
}
