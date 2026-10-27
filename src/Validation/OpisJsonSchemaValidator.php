<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Validation;

use Ark4ne\OpenApiWriter\Enums\Version;
use Ark4ne\OpenApiWriter\Exceptions\MissingDependencyException;
use Ark4ne\OpenApiWriter\Support\JsonPointer;
use JsonException;
use Opis\JsonSchema\Errors\ErrorFormatter;
use Opis\JsonSchema\Parsers\SchemaParser;
use Opis\JsonSchema\Resolvers\SchemaResolver;
use Opis\JsonSchema\SchemaLoader;
use Opis\JsonSchema\Validator as Opis;
use stdClass;

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
 *
 * The schemas are loaded on the first validation, once per instance: reuse an instance to validate several documents.
 */
final class OpisJsonSchemaValidator implements JsonSchemaValidator
{
    private const DIRECTORY = __DIR__ . '/schemas/';

    /**
     * `$id` of the official schema of each line.
     */
    private const SCHEMAS = [
        '3.0' => 'https://spec.openapis.org/oas/3.0/schema/2024-10-18',
        '3.1' => 'https://spec.openapis.org/oas/3.1/schema/2026-08-03',
        '3.2' => 'https://spec.openapis.org/oas/3.2/schema/2026-08-30',
    ];

    /**
     * Synthetic id of the copy of a line's schema whose Schema Objects are checked by its `schema-base`.
     */
    private const BASE_COPY = '/with-schema-base';

    /**
     * Opis options turned off, so that it validates standard JSON Schema only.
     */
    private const OPTIONS = ['allowFilters', 'allowFormats', 'allowMappers', 'allowTemplates', 'allowGlobals', 'allowDefaults', 'allowSlots', 'allowPragmas', 'allowDataKeyword'];

    /**
     * Errors reported for one document, at most.
     */
    private const MAX_ERRORS = 10;

    private ?Opis $opis = null;

    /**
     * @var array<string, array{dialect: string, base: string}> by line, filled when the schemas are loaded
     */
    private array $dialects = [];

    /**
     * @throws MissingDependencyException when `opis/json-schema` is not installed
     */
    public function __construct()
    {
        if (!class_exists(Opis::class)) {
            throw MissingDependencyException::forPackage('opis/json-schema', 'validate documents against the official schemas');
        }
    }

    public function validate(string $json, Version $version, Violations $violations): void
    {
        try {
            $document = json_decode($json, false, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            $violations->add(new Violation(JsonPointer::root(), self::class, 'invalid JSON: ' . $exception->getMessage()));

            return;
        }

        $opis = $this->opis();
        $line = $version->line();
        $schema = self::SCHEMAS[$line];

        if (isset($this->dialects[$line]) && $document instanceof stdClass && ($document->jsonSchemaDialect ?? null) === $this->dialects[$line]['dialect']) {
            $schema = $this->dialects[$line]['base'];
        }

        $error = $opis->validate($document, $schema)->error();

        if ($error === null) {
            return;
        }

        /** @var array<string, string> $errors one message by JSON Pointer */
        $errors = (new ErrorFormatter())->format($error, false);

        foreach ($errors as $pointer => $message) {
            // Opis writes the root as `/`, which RFC 6901 reads as the member named "".
            $violations->add(new Violation($pointer === '/' ? JsonPointer::root() : JsonPointer::fromString(rawurldecode($pointer)), self::class, $message));
        }
    }

    private function opis(): Opis
    {
        if ($this->opis !== null) {
            return $this->opis;
        }

        $parser = new SchemaParser();

        foreach (self::OPTIONS as $option) {
            $parser->setOption($option, false);
        }

        $resolver = new SchemaResolver();
        $opis = new Opis(new SchemaLoader($parser, $resolver), self::MAX_ERRORS);
        $metaSchemas = glob(self::DIRECTORY . 'json-schema-2020-12/*.json');

        foreach ($metaSchemas === false ? [] : $metaSchemas as $file) {
            $schema = self::read($file);
            $resolver->registerRaw($schema, self::id($schema));
        }

        foreach (['3.1', '3.2'] as $line) {
            $dialect = self::read(self::DIRECTORY . $line . '-dialect.json');
            $resolver->registerRaw($dialect, self::id($dialect));
            $meta = self::read(self::DIRECTORY . $line . '-meta.json');
            $resolver->registerRaw($meta, self::id($meta));

            $schemaId = self::SCHEMAS[$line];
            $resolver->registerRaw(self::adapted($line, self::id($dialect)), $schemaId);

            // schema-base: its Schema Objects follow its own `$defs/schema` (the dialect, with `$schema` fixed), so
            // it points to a copy of the line's schema whose `$dynamicRef` targets that definition.
            $base = self::read(self::DIRECTORY . $line . '-schema-base.json');
            $baseId = self::id($base);
            $copy = self::adapted($line, $baseId . '#/$defs/schema');
            $copy->{'$id'} = $schemaId . self::BASE_COPY;
            $resolver->registerRaw($copy, $copy->{'$id'});
            $base->{'$ref'} = $copy->{'$id'};
            $resolver->registerRaw($base, $baseId);
            $this->dialects[$line] = ['dialect' => self::id($dialect), 'base' => $baseId];
        }

        $schema = self::read(self::DIRECTORY . '3.0-schema.json');
        self::upgradeDraft4($schema);
        $resolver->registerRaw($schema, self::id($schema));

        return $this->opis = $opis;
    }

    /**
     * The schema of a 3.1 or 3.2 line, with `$dynamicRef: "#meta"` replaced by a `$ref` to $meta and without
     * `minContains: 0`.
     */
    private static function adapted(string $line, string $meta): stdClass
    {
        $schema = self::read(self::DIRECTORY . $line . '-schema.json');
        self::withMetaReference($schema, $meta);
        self::withoutMinContainsZero($schema);

        return $schema;
    }

    /**
     * Opis resolves `$dynamicRef` like `$recursiveRef`: every `$dynamicRef: "#meta"` becomes a static `$ref`.
     */
    private static function withMetaReference(mixed $node, string $meta): void
    {
        foreach (self::children($node) as $child) {
            self::withMetaReference($child, $meta);
        }

        if ($node instanceof stdClass && ($node->{'$dynamicRef'} ?? null) === '#meta') {
            $node->{'$ref'} = $meta;
            unset($node->{'$dynamicRef'});
        }
    }

    /**
     * Draft 4 → draft 6: `id` becomes `$id`, a boolean `exclusiveMinimum` / `exclusiveMaximum` becomes a number.
     */
    private static function upgradeDraft4(mixed $node): void
    {
        foreach (self::children($node) as $child) {
            self::upgradeDraft4($child);
        }

        if (!$node instanceof stdClass) {
            return;
        }

        if (isset($node->id, $node->{'$schema'}) && is_string($node->id)) {
            $node->{'$id'} = $node->id;
            $node->{'$schema'} = 'http://json-schema.org/draft-06/schema#';
            unset($node->id);
        }

        foreach (['exclusiveMinimum' => 'minimum', 'exclusiveMaximum' => 'maximum'] as $exclusive => $bound) {
            if (isset($node->{$exclusive}) && is_bool($node->{$exclusive})) {
                if ($node->{$exclusive} && isset($node->{$bound})) {
                    $node->{$exclusive} = $node->{$bound};
                    unset($node->{$bound});
                } else {
                    unset($node->{$exclusive});
                }
            }
        }
    }

    /**
     * Opis ignores `minContains: 0` (it still needs one match): `contains: X, minContains: 0, maxContains: n` becomes
     * the equivalent `not: {contains: X, minContains: n + 1}`.
     */
    private static function withoutMinContainsZero(mixed $node): void
    {
        foreach (self::children($node) as $child) {
            self::withoutMinContainsZero($child);
        }

        if ($node instanceof stdClass && isset($node->contains, $node->maxContains) && ($node->minContains ?? null) === 0 && is_int($node->maxContains)) {
            $atMost = (object) ['not' => (object) ['contains' => $node->contains, 'minContains' => $node->maxContains + 1]];
            $allOf = $node->allOf ?? [];
            $node->allOf = [...(is_array($allOf) ? $allOf : []), $atMost];
            unset($node->contains, $node->minContains, $node->maxContains);
        }
    }

    /**
     * The values of an object or a list of a decoded schema; objects are changed in place by the adaptations.
     *
     * @return list<mixed>
     */
    private static function children(mixed $node): array
    {
        if ($node instanceof stdClass) {
            return array_values(get_object_vars($node));
        }

        return is_array($node) ? array_values($node) : [];
    }

    /**
     * An embedded schema: a JSON object with an `$id` (`id` in draft 4), as the embedded schemas test checks.
     */
    private static function read(string $file): stdClass
    {
        /** @var stdClass */
        return json_decode((string) file_get_contents($file), false, 512, JSON_THROW_ON_ERROR);
    }

    private static function id(stdClass $schema): string
    {
        /** @var string */
        return $schema->{'$id'} ?? $schema->id;
    }
}
