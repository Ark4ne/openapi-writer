<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Tests\Support;

use Ark4ne\OpenApiWriter\Enums\Version;
use JsonException;
use Opis\JsonSchema\Errors\ErrorFormatter;
use Opis\JsonSchema\Validator;
use RuntimeException;
use stdClass;

/**
 * Checks a JSON document against the official schema of its line, embedded in `src/Validation/schemas/`.
 *
 * Test infrastructure, independent of the library code under test, so that expected fixtures are checked even while
 * the library is not implemented. The schemas are adapted on load and opis is configured exactly as
 * `OpisJsonSchemaValidator` documents it.
 *
 * A 3.1 or 3.2 document whose `jsonSchemaDialect` is the OpenAPI dialect is checked against the official
 * `schema-base` of its line, which also requires the `$schema` of every Schema Object to be that dialect.
 */
final class OfficialSchema
{
    private const DIRECTORY = __DIR__ . '/../../src/Validation/schemas/';

    private const SCHEMAS = [
        '3.0' => 'https://spec.openapis.org/oas/3.0/schema/2024-10-18',
        '3.1' => 'https://spec.openapis.org/oas/3.1/schema/2026-08-03',
        '3.2' => 'https://spec.openapis.org/oas/3.2/schema/2026-08-30',
    ];

    /**
     * Synthetic id of the copy of a line's schema whose Schema Objects are checked by its `schema-base`.
     */
    private const BASE_COPY = '/with-schema-base';

    private static ?Validator $validator = null;

    /**
     * @var array<string, array{dialect: string, base: string}> by line, filled when the validator is built
     */
    private static array $dialects = [];

    /**
     * @return array<string, mixed> the errors keyed by JSON Pointer, empty when the document is valid
     */
    public static function errors(string $json, Version $version): array
    {
        try {
            $document = json_decode($json, false, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            return ['' => 'Invalid JSON: ' . $exception->getMessage()];
        }

        $validator = self::validator();
        $line = Fixtures::line($version);
        $schema = self::SCHEMAS[$line];
        $dialect = $document instanceof stdClass ? ($document->jsonSchemaDialect ?? null) : null;

        if (isset(self::$dialects[$line]) && $dialect === self::$dialects[$line]['dialect']) {
            $schema = self::$dialects[$line]['base'];
        }

        $result = $validator->validate($document, $schema);
        $error = $result->error();

        if ($error === null) {
            return [];
        }

        $errors = [];

        foreach ((new ErrorFormatter())->format($error, false) as $pointer => $message) {
            $errors[rawurldecode((string) $pointer)] = $message;
        }

        return $errors;
    }

    private static function validator(): Validator
    {
        if (self::$validator !== null) {
            return self::$validator;
        }

        $validator = new Validator();
        $validator->setMaxErrors(10);

        // Standard JSON Schema only: no opis extension, no default inserted in the data, formats as annotations.
        foreach (['allowFilters', 'allowFormats', 'allowMappers', 'allowTemplates', 'allowGlobals', 'allowDefaults', 'allowSlots', 'allowPragmas', 'allowDataKeyword'] as $option) {
            $validator->parser()->setOption($option, false);
        }
        $resolver = $validator->resolver();

        if ($resolver === null) {
            throw new RuntimeException('The opis validator has no schema resolver.');
        }

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
            self::$dialects[$line] = ['dialect' => self::id($dialect), 'base' => $baseId];
        }

        $schema = self::upgradeDraft4(self::read(self::DIRECTORY . '3.0-schema.json'));
        if (!$schema instanceof stdClass) {
            throw new RuntimeException('The 3.0 schema is not an object.');
        }
        $resolver->registerRaw($schema, self::id($schema));

        return self::$validator = $validator;
    }

    /**
     * The schema of a 3.1 or 3.2 line, with `$dynamicRef: "#meta"` replaced by a `$ref` to $meta and without
     * `minContains: 0`.
     */
    private static function adapted(string $line, string $meta): stdClass
    {
        $schema = self::withoutMinContainsZero(self::withMetaReference(self::read(self::DIRECTORY . $line . '-schema.json'), $meta));

        if (!$schema instanceof stdClass) {
            throw new RuntimeException('The ' . $line . ' schema is not an object.');
        }

        return $schema;
    }

    /**
     * Opis resolves `$dynamicRef` like `$recursiveRef`: every `$dynamicRef: "#meta"` becomes a static `$ref`.
     */
    private static function withMetaReference(mixed $node, string $meta): mixed
    {
        if (is_array($node)) {
            return array_map(static fn(mixed $item): mixed => self::withMetaReference($item, $meta), $node);
        }

        if (!$node instanceof stdClass) {
            return $node;
        }

        if (($node->{'$dynamicRef'} ?? null) === '#meta') {
            $node->{'$ref'} = $meta;
            unset($node->{'$dynamicRef'});
        }

        foreach (get_object_vars($node) as $key => $value) {
            $node->{$key} = self::withMetaReference($value, $meta);
        }

        return $node;
    }

    /**
     * Draft 4 → draft 6: `id` becomes `$id`, a boolean `exclusiveMinimum` / `exclusiveMaximum` becomes a number.
     */
    private static function upgradeDraft4(mixed $node): mixed
    {
        if (is_array($node)) {
            return array_map(self::upgradeDraft4(...), $node);
        }

        if (!$node instanceof stdClass) {
            return $node;
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

        foreach (get_object_vars($node) as $key => $value) {
            $node->{$key} = self::upgradeDraft4($value);
        }

        return $node;
    }

    /**
     * Opis ignores `minContains: 0` (it still needs one match): `contains: X, minContains: 0, maxContains: n` becomes
     * the equivalent `not: {contains: X, minContains: n + 1}`.
     */
    private static function withoutMinContainsZero(mixed $node): mixed
    {
        if (is_array($node)) {
            return array_map(self::withoutMinContainsZero(...), $node);
        }

        if (!$node instanceof stdClass) {
            return $node;
        }

        if (isset($node->contains, $node->maxContains) && ($node->minContains ?? null) === 0 && is_int($node->maxContains)) {
            $atMost = (object) ['not' => (object) ['contains' => $node->contains, 'minContains' => $node->maxContains + 1]];
            $allOf = $node->allOf ?? [];
            $node->allOf = [...(is_array($allOf) ? $allOf : []), $atMost];
            unset($node->contains, $node->minContains, $node->maxContains);
        }

        foreach (get_object_vars($node) as $key => $value) {
            $node->{$key} = self::withoutMinContainsZero($value);
        }

        return $node;
    }

    private static function read(string $file): stdClass
    {
        return self::decode((string) file_get_contents($file));
    }

    private static function decode(string $json): stdClass
    {
        $schema = json_decode($json, false, 512, JSON_THROW_ON_ERROR);

        if (!$schema instanceof stdClass) {
            throw new RuntimeException('A schema is not a JSON object.');
        }

        return $schema;
    }

    private static function id(stdClass $schema): string
    {
        $id = $schema->{'$id'} ?? $schema->id ?? null;

        if (!is_string($id)) {
            throw new RuntimeException('A schema has no $id.');
        }

        return $id;
    }
}
