<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Validation\Rules;

use Ark4ne\OpenApiWriter\Enums\Version;
use Ark4ne\OpenApiWriter\Support\JsonPointer;
use Ark4ne\OpenApiWriter\Validation\Rule;
use Ark4ne\OpenApiWriter\Validation\Violation;
use Ark4ne\OpenApiWriter\Validation\Violations;

/**
 * Each local reference (`#/...`) points to an existing element of the document: `$ref` (reported at the object that
 * holds it), `operationRef` and the values of a discriminator `mapping`. A `$ref` inside a schema with an `$id` is
 * resolved in that schema, as JSON Schema does. References to other documents and free values (`example`, `default`…)
 * are not checked.
 */
final class LocalReferencesRule implements Rule
{
    public function check(array $document, Version $version, Violations $violations): void
    {
        self::walk($document, $document, $document, JsonPointer::root(), Scope::root(), $violations);
    }

    /**
     * @param array<string, mixed> $document
     * @param array<mixed>         $resource the document, or the closest schema with an `$id`: the base of a `$ref`
     * @param array<mixed>         $node
     */
    private static function walk(array $document, array $resource, array $node, JsonPointer $pointer, Scope $scope, Violations $violations): void
    {
        if (is_string($node['$id'] ?? null)) {
            $resource = $node;
        }

        $reference = Document::local($node['$ref'] ?? null);

        if ($reference !== null && !Document::find($resource, $reference)[0]) {
            $violations->add(new Violation($pointer, self::class, sprintf('$ref "%s" points to nothing', $reference)));
        }

        $operationRef = Document::local($node['operationRef'] ?? null);

        if ($operationRef !== null && !Document::find($document, $operationRef)[0]) {
            $violations->add(new Violation($pointer->append('operationRef'), self::class, sprintf('operationRef "%s" points to nothing', $operationRef)));
        }

        if ($scope->field === 'discriminator') {
            foreach (Document::entries($node['mapping'] ?? null) as $value => $mapping) {
                $mapping = Document::local($mapping);

                if ($mapping !== null && !Document::find($document, $mapping)[0]) {
                    $violations->add(new Violation($pointer->append('mapping', $value), self::class, sprintf('mapping "%s" points to nothing', $mapping)));
                }
            }
        }

        foreach ($node as $key => $child) {
            $childScope = $scope->enter($key, $child);

            if (is_array($child) && $childScope !== null) {
                self::walk($document, $resource, $child, $pointer->append($key), $childScope, $violations);
            }
        }
    }
}
