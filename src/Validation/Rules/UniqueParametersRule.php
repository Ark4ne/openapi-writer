<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Validation\Rules;

use Ark4ne\OpenApiWriter\Enums\Version;
use Ark4ne\OpenApiWriter\Support\JsonPointer;
use Ark4ne\OpenApiWriter\Validation\Rule;
use Ark4ne\OpenApiWriter\Validation\Violation;
use Ark4ne\OpenApiWriter\Validation\Violations;

/**
 * No two parameters of an operation (path item parameters included) share the same `name` and `in`.
 */
final class UniqueParametersRule implements Rule
{
    public function check(array $document, Version $version, Violations $violations): void
    {
        foreach (Document::pathItems($document, $version) as [$pointer, $pathItem]) {
            foreach (Document::operationsOf($pathItem, $pointer) as [$operationPointer, $operation]) {
                self::checkList($document, $operation, $operationPointer, $violations);
            }

            self::checkList($document, $pathItem, $pointer, $violations);
        }
    }

    /**
     * Reports each parameter whose `name` and `in` were already used in the same list, references followed.
     *
     * @param array<string, mixed> $document
     * @param array<mixed>         $holder   a path item or an operation
     */
    private static function checkList(array $document, array $holder, JsonPointer $pointer, Violations $violations): void
    {
        $seen = [];

        foreach (Document::entries($holder['parameters'] ?? null) as $index => $parameter) {
            $parameter = Document::resolve($document, $parameter);

            if (!is_array($parameter) || !is_string($parameter['name'] ?? null) || !is_string($parameter['in'] ?? null)) {
                continue;
            }

            $key = $parameter['in'] . ' ' . $parameter['name'];

            if (isset($seen[$key])) {
                $violations->add(new Violation(
                    $pointer->append('parameters', $index),
                    self::class,
                    sprintf('parameter "%s" in %s is already declared at index %d', $parameter['name'], $parameter['in'], $seen[$key]),
                ));
            } else {
                $seen[$key] = $index;
            }
        }
    }
}
