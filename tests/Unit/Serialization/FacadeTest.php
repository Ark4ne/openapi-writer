<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Tests\Unit\Serialization;

use Ark4ne\OpenApiWriter\Enums\Version;
use Ark4ne\OpenApiWriter\Exceptions\InvalidArgumentException;
use Ark4ne\OpenApiWriter\Exceptions\OpenApiWriterException;
use Ark4ne\OpenApiWriter\Objects\Components;
use Ark4ne\OpenApiWriter\Objects\Info;
use Ark4ne\OpenApiWriter\Objects\OpenApi;
use Ark4ne\OpenApiWriter\Objects\Operation;
use Ark4ne\OpenApiWriter\Objects\PathItem;
use Ark4ne\OpenApiWriter\Objects\Response;
use Ark4ne\OpenApiWriter\Objects\Schema;
use Ark4ne\OpenApiWriter\Serialization\Encoders\JsonEncoder;
use Ark4ne\OpenApiWriter\Tests\Support\Fixtures;
use Ark4ne\OpenApiWriter\Tests\TestCase;
use JsonSerializable;
use stdClass;
use Symfony\Component\Yaml\Yaml;
use Throwable;

/**
 * `toArray()`, `toJson()`, `toYaml()` and `jsonSerialize()`: the facades of SpecObject over the Serializer.
 */
final class FacadeTest extends TestCase
{
    public function test_a_document_without_declared_version_is_written_for_3_1_2(): void
    {
        $document = self::document();

        self::assertSame('3.1.2', $document->toArray()['openapi'] ?? null);
        self::assertJsonMatchesFixture($document->toJson(flags: JSON_PRETTY_PRINT), OpenApi::class, Version::V3_1_2, 'serializer/document');
    }

    public function test_a_document_is_written_for_its_declared_version_by_default(): void
    {
        $document = self::document()->openapi(Version::V3_0_4);

        self::assertJsonMatchesFixture($document->toJson(flags: JSON_PRETTY_PRINT), OpenApi::class, Version::V3_0_4, 'serializer/document');
    }

    public function test_json_serialize_writes_the_declared_version(): void
    {
        $document = self::document()->openapi('3.0.4');

        $json = json_encode($document, JsonEncoder::DEFAULT_FLAGS | JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);

        self::assertJsonMatchesFixture($json, OpenApi::class, Version::V3_0_4, 'serializer/document');
    }

    public function test_the_version_can_be_given_as_a_string(): void
    {
        self::assertJsonMatchesFixture(self::document()->toJson('3.0.4', JSON_PRETTY_PRINT), OpenApi::class, Version::V3_0_4, 'serializer/document');
        self::assertSame('3.0.4', self::document()->toArray('3.0.4')['openapi'] ?? null);
    }

    public function test_an_unknown_version_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        self::document()->toJson('3.3.0');
    }

    public function test_without_pretty_print_the_json_is_compact(): void
    {
        $expected = json_encode(json_decode(Fixtures::expected(Version::V3_1_2, 'serializer/document'), false, 512, JSON_THROW_ON_ERROR), JsonEncoder::DEFAULT_FLAGS | JSON_THROW_ON_ERROR);

        $actual = self::document()->toJson(Version::V3_1_2);

        self::assertValidOpenApi($actual, Version::V3_1_2);
        self::assertSame($expected, $actual);
    }

    public function test_an_empty_object_is_an_empty_json_object(): void
    {
        self::assertSame([], Components::create()->toArray());
        self::assertSerializesTo(Components::create(), Version::V3_1_2, 'serializer/empty-object');
    }

    public function test_an_empty_map_is_a_std_class_in_to_array(): void
    {
        $node = Schema::object()->properties()->toArray();

        self::assertSame('object', $node['type'] ?? null);
        self::assertInstanceOf(stdClass::class, $node['properties'] ?? null);
        self::assertSame([], get_object_vars($node['properties']));
    }

    public function test_status_codes_stay_json_keys_in_insertion_order(): void
    {
        $operation = Operation::get()->responses(Response::notFound(), Response::ok());

        $responses = $operation->toArray()['responses'] ?? null;

        self::assertIsArray($responses);
        self::assertSame([404, 200], array_keys($responses));
        self::assertSerializesTo($operation, Version::V3_1_2, 'serializer/status-codes');
    }

    public function test_a_free_value_that_json_cannot_write_throws_with_its_pointer(): void
    {
        try {
            Schema::number()->default(NAN)->toJson();
        } catch (Throwable $exception) {
            self::assertInstanceOf(OpenApiWriterException::class, $exception);
            self::assertStringContainsString('/default', $exception->getMessage());

            return;
        }

        self::fail('Expected an exception for a NAN default value.');
    }

    private static function document(): OpenApi
    {
        return OpenApi::create()
            ->info(Info::create()->title('Users API')->version('1.0.0'))
            ->paths(PathItem::create('/users')->get(Operation::get()->operationId('listUsers')->responses(Response::ok())));
    }

    public function test_a_map_whose_keys_look_like_a_list_stays_a_json_object(): void
    {
        self::assertSerializesTo(Schema::object()->properties(Schema::string('0'), Schema::string('1')), Version::V3_1_2, 'serializer/numeric-keys');
    }

    public function test_a_json_serializable_free_value_is_written_as_its_data_in_json_and_yaml(): void
    {
        $value = new class implements JsonSerializable {
            /**
             * @return array<string, mixed>
             */
            public function jsonSerialize(): array
            {
                return ['name' => 'Alice', 'tags' => (object) ['role' => 'admin']];
            }
        };
        $schema = Schema::object()->example($value);

        self::assertSerializesTo($schema, Version::V3_1_2, 'serializer/json-serializable-value');
        self::assertEquals(json_decode($schema->toJson(), true), Yaml::parse($schema->toYaml()));
    }

    public function test_a_value_that_json_cannot_write_inside_an_object_throws_with_its_pointer(): void
    {
        try {
            Schema::number()->default((object) ['bounds' => [NAN]])->toJson();
        } catch (Throwable $exception) {
            self::assertInstanceOf(OpenApiWriterException::class, $exception);
            self::assertStringContainsString('/default/bounds/0', $exception->getMessage());

            return;
        }

        self::fail('Expected an exception for a NAN nested in a default value.');
    }
}
