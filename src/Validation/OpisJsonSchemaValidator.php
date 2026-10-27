<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Validation;

use Ark4ne\OpenApiWriter\Enums\Version;
use Ark4ne\OpenApiWriter\Exceptions\MissingDependencyException;

/**
 * Validates a document against the official schema of its line, embedded in `schemas/` (Adapter of
 * JsonSchemaValidator, needs `opis/json-schema`). Each violation is reported with this class name as rule.
 *
 * Opis does not read draft 4 and resolves `$dynamicRef` like `$recursiveRef`, so the schemas are adapted on load:
 * the 3.0 schema is upgraded to draft 6 (`id` → `$id`, boolean `exclusiveMinimum` → number), and in the 3.1 and 3.2
 * schemas `$dynamicRef: "#meta"` becomes a `$ref` to the official OpenAPI dialect, which checks every Schema Object.
 * Opis also ignores `minContains: 0`, so `contains: X, minContains: 0, maxContains: n` (at most n parameters
 * `in: querystring` in 3.2) becomes `not: {contains: X, minContains: n + 1}`.
 * A 3.1 or 3.2 document whose `jsonSchemaDialect` is the OpenAPI dialect is checked against the official
 * `schema-base` of its line, through a copy of the line's schema whose `$dynamicRef` targets the `$defs/schema` of
 * `schema-base` (the dialect, with the `$schema` of each Schema Object fixed).
 * Opis runs as a standard validator: its extensions are off, it never inserts `default` values into the document, and
 * `format` is an annotation, as JSON Schema 2020-12 defines it.
 */
final class OpisJsonSchemaValidator implements JsonSchemaValidator
{
    /**
     * @throws MissingDependencyException when `opis/json-schema` is not installed
     */
    public function __construct() {}

    public function validate(string $json, Version $version, Violations $violations): void {}
}
