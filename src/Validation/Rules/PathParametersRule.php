<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Validation\Rules;

use Ark4ne\OpenApiWriter\Enums\Version;
use Ark4ne\OpenApiWriter\Support\JsonPointer;
use Ark4ne\OpenApiWriter\Validation\Rule;
use Ark4ne\OpenApiWriter\Validation\Violation;
use Ark4ne\OpenApiWriter\Validation\Violations;

/**
 * Each `{name}` variable of a path has a matching required `in: path` parameter, on the path item or on every operation.
 */
final class PathParametersRule implements Rule
{
    public function check(array $document, Version $version, Violations $violations): void
    {
        foreach (Document::entries($document['paths'] ?? null) as $path => $entry) {
            $path = (string) $path;

            if (Document::isExtension($path) || preg_match_all('/\{([^}]+)}/', $path, $matches) === 0) {
                continue;
            }

            if (!is_array($entry)) {
                continue;
            }

            // The fields written next to a `$ref` and those of its target both apply. An operation of the target is
            // reported at the reference, the place a fix happens.
            $pointer = JsonPointer::root()->append('paths', $path);
            $target = isset($entry['$ref']) ? Document::resolve($document, $entry) : null;
            $sources = is_array($target) ? [[$entry, false], [$target, true]] : [[$entry, false]];
            $declared = [];

            foreach ($sources as [$pathItem]) {
                $declared = [...$declared, ...self::declared($document, $pathItem)];
            }

            foreach ($sources as [$pathItem, $isTarget]) {
                foreach (Document::operationsOf($pathItem, $pointer) as [$operationPointer, $operation]) {
                    $missing = array_diff($matches[1], [...$declared, ...self::declared($document, $operation)]);

                    if ($missing !== []) {
                        $violations->add(new Violation(
                            $isTarget ? $pointer : $operationPointer,
                            self::class,
                            sprintf('%s has no required in: path parameter for {%s}', $operationPointer->toString(), implode('}, {', $missing)),
                        ));
                    }
                }
            }
        }
    }

    /**
     * Names of the required `in: path` parameters of a path item or an operation, references followed.
     *
     * @param array<string, mixed> $document
     * @param array<mixed>         $holder
     *
     * @return list<string>
     */
    private static function declared(array $document, array $holder): array
    {
        $names = [];

        foreach (Document::entries($holder['parameters'] ?? null) as $parameter) {
            $parameter = Document::resolve($document, $parameter);

            if (is_array($parameter) && ($parameter['in'] ?? null) === 'path' && ($parameter['required'] ?? null) === true && is_string($parameter['name'] ?? null)) {
                $names[] = $parameter['name'];
            }
        }

        return $names;
    }
}
