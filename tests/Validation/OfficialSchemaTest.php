<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Tests\Validation;

use Ark4ne\OpenApiWriter\Enums\Version;
use Ark4ne\OpenApiWriter\Tests\Support\Fixtures;
use Ark4ne\OpenApiWriter\Tests\Support\OfficialSchema;
use Ark4ne\OpenApiWriter\Tests\TestCase;
use Ark4ne\OpenApiWriter\Validation\OpisJsonSchemaValidator;
use Ark4ne\OpenApiWriter\Validation\Severity;
use Ark4ne\OpenApiWriter\Validation\Violation;
use Ark4ne\OpenApiWriter\Validation\Violations;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Level 1: the official schema of each line, embedded in `src/Validation/schemas/`, through opis/json-schema.
 */
final class OfficialSchemaTest extends TestCase
{
    /**
     * @return iterable<string, array{string, Version}>
     */
    public static function validDocuments(): iterable
    {
        foreach (['3.0' => Version::V3_0_4, '3.1' => Version::V3_1_2, '3.2' => Version::V3_2_0] as $line => $version) {
            $files = glob(Fixtures::DIRECTORY . $line . '/conformance/*.json');

            foreach ($files === false ? [] : $files as $file) {
                yield $line . ' ' . basename($file, '.json') => ['conformance/' . basename($file, '.json'), $version];
            }
        }
    }

    #[DataProvider('validDocuments')]
    public function test_an_official_example_is_valid(string $fixture, Version $version): void
    {
        $violations = new Violations();

        (new OpisJsonSchemaValidator())->validate(Fixtures::expected($version, $fixture), $version, $violations);

        self::assertTrue($violations->isValid());
        self::assertSame([], $violations->all());
    }

    /**
     * @return iterable<string, array{Version, list<string>}>
     */
    public static function invalidDocuments(): iterable
    {
        yield '3.0 info without version, response without description' => [Version::V3_0_4, ['/info', '/paths/~1users/get/responses/200']];
        yield '3.1 path that does not start with a slash' => [Version::V3_1_2, ['/paths']];
        yield '3.2 schema keyword of the wrong type' => [Version::V3_2_0, ['/components/schemas/Count/minimum']];
    }

    /**
     * @param list<string> $pointers
     */
    #[DataProvider('invalidDocuments')]
    public function test_an_invalid_document_is_reported_with_the_pointer_of_each_error(Version $version, array $pointers): void
    {
        $violations = new Violations();

        (new OpisJsonSchemaValidator())->validate(Fixtures::expected($version, 'validation/schema-invalid'), $version, $violations);

        self::assertFalse($violations->isValid());
        self::assertSame($pointers, array_map(static fn(Violation $violation): string => $violation->pointer->toString(), $violations->all()));
        foreach ($violations as $violation) {
            self::assertSame(OpisJsonSchemaValidator::class, $violation->rule);
            self::assertSame(Severity::Error, $violation->severity);
        }
    }

    public function test_a_document_of_another_line_is_checked_against_the_schema_of_the_target_version(): void
    {
        $violations = new Violations();

        (new OpisJsonSchemaValidator())->validate(Fixtures::expected(Version::V3_1_2, 'conformance/webhook-example'), Version::V3_0_4, $violations);

        self::assertFalse($violations->isValid());
    }

    /**
     * @return iterable<string, array{Version, string}>
     */
    public static function baseDialects(): iterable
    {
        yield '3.1' => [Version::V3_1_2, 'https://spec.openapis.org/oas/3.1/dialect/2024-11-10'];
        yield '3.2' => [Version::V3_2_0, 'https://spec.openapis.org/oas/3.2/dialect/2026-02-26'];
    }

    /**
     * The `$schema` of a Schema Object is free, unless the document declares the OpenAPI dialect: the official
     * `schema-base` then requires that dialect.
     */
    #[DataProvider('baseDialects')]
    public function test_a_document_declaring_the_openapi_dialect_is_checked_against_the_schema_base(Version $version, string $dialect): void
    {
        $document = static fn(?string $declared): string => json_encode([
            'openapi' => $version->value,
            'info' => ['title' => 'Dialect', 'version' => '1.0.0'],
            ...($declared === null ? [] : ['jsonSchemaDialect' => $declared]),
            'components' => ['schemas' => ['Name' => ['$schema' => 'https://json-schema.org/draft/2020-12/schema', 'type' => 'string']]],
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);

        $free = new Violations();
        (new OpisJsonSchemaValidator())->validate($document(null), $version, $free);
        $base = new Violations();
        (new OpisJsonSchemaValidator())->validate($document($dialect), $version, $base);

        self::assertSame([], $free->all());
        self::assertSame(['/components/schemas/Name/$schema'], array_map(static fn(Violation $violation): string => $violation->pointer->toString(), $base->all()));
    }

    /**
     * Every document fixture of a line, valid or not: the adapter gives the same verdict, at the same pointers, as
     * the test infrastructure that checks the fixtures (both adapt the official schemas to opis).
     *
     * @return iterable<string, array{string, Version}>
     */
    public static function documentFixtures(): iterable
    {
        foreach (['3.0' => Version::V3_0_4, '3.1' => Version::V3_1_2, '3.2' => Version::V3_2_0] as $line => $version) {
            $files = glob(Fixtures::DIRECTORY . $line . '/*/*.json');

            foreach ($files === false ? [] : $files as $file) {
                $name = basename(dirname($file)) . '/' . basename($file, '.json');

                if (str_contains((string) file_get_contents($file), '"openapi": ')) {
                    yield $line . ' ' . $name => [$name, $version];
                }
            }
        }
    }

    #[DataProvider('documentFixtures')]
    public function test_the_verdict_is_the_one_of_the_test_infrastructure(string $fixture, Version $version): void
    {
        $json = Fixtures::expected($version, $fixture);
        $violations = new Violations();

        (new OpisJsonSchemaValidator())->validate($json, $version, $violations);

        self::assertSame(
            array_keys(OfficialSchema::errors($json, $version)),
            array_map(static fn(Violation $violation): string => $violation->pointer->toString(), $violations->all()),
        );
    }

    public function test_invalid_json_is_a_violation_at_the_root(): void
    {
        $violations = new Violations();

        (new OpisJsonSchemaValidator())->validate('{"openapi": ', Version::V3_1_2, $violations);

        self::assertCount(1, $violations);
        self::assertSame('', $violations->all()[0]->pointer->toString());
    }

    public function test_the_embedded_schemas_are_the_official_ones(): void
    {
        $directory = __DIR__ . '/../../src/Validation/schemas/';
        $ids = [
            '3.0-schema.json' => 'https://spec.openapis.org/oas/3.0/schema/2024-10-18',
            '3.1-schema.json' => 'https://spec.openapis.org/oas/3.1/schema/2026-08-03',
            '3.1-schema-base.json' => 'https://spec.openapis.org/oas/3.1/schema-base/2026-08-03',
            '3.1-dialect.json' => 'https://spec.openapis.org/oas/3.1/dialect/2024-11-10',
            '3.1-meta.json' => 'https://spec.openapis.org/oas/3.1/meta/2024-11-10',
            '3.2-schema.json' => 'https://spec.openapis.org/oas/3.2/schema/2026-08-30',
            '3.2-schema-base.json' => 'https://spec.openapis.org/oas/3.2/schema-base/2026-08-30',
            '3.2-dialect.json' => 'https://spec.openapis.org/oas/3.2/dialect/2026-02-26',
            '3.2-meta.json' => 'https://spec.openapis.org/oas/3.2/meta/2026-02-26',
        ];

        foreach ($ids as $file => $id) {
            $schema = json_decode((string) file_get_contents($directory . $file), true, 512, JSON_THROW_ON_ERROR);
            self::assertIsArray($schema);
            self::assertSame($id, $schema['$id'] ?? $schema['id'] ?? null, $file);
        }
    }
}
