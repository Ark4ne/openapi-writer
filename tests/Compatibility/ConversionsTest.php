<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Tests\Compatibility;

use Ark4ne\OpenApiWriter\Enums\Strictness;
use Ark4ne\OpenApiWriter\Enums\Version;
use Ark4ne\OpenApiWriter\Objects\Components;
use Ark4ne\OpenApiWriter\Objects\Info;
use Ark4ne\OpenApiWriter\Objects\OpenApi;
use Ark4ne\OpenApiWriter\Objects\Response;
use Ark4ne\OpenApiWriter\Objects\Schema;
use Ark4ne\OpenApiWriter\Serialization\DiagnosticLevel;
use Ark4ne\OpenApiWriter\Serialization\Serializer;
use Ark4ne\OpenApiWriter\Tests\Support\Documents;
use Ark4ne\OpenApiWriter\Tests\Support\Fixtures;
use Ark4ne\OpenApiWriter\Tests\TestCase;
use Closure;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Each row of the table "Conversions automatiques" (specification, section 6), and the combinations of rewriters,
 * for every version: the expected output (`tests/fixtures/<line>/compatibility/<row>.json`) and the `Converted`
 * diagnostics. A conversion is reported when the output differs from the form the model describes (the form of the
 * latest line); it never depends on the strictness.
 */
final class ConversionsTest extends TestCase
{
    /**
     * Keyed by row: the document, and the pointers reported as Converted, by line.
     *
     * @return array<string, array{Closure(): OpenApi, array<string, list<string>>}>
     */
    public static function rows(): array
    {
        $schema = '/components/schemas/';

        return [
            'nullable-with-type' => [
                static fn(): OpenApi => self::schemas(Schema::string('Nickname')->nullable(true)),
                ['3.0' => [$schema . 'Nickname'], '3.1' => [], '3.2' => []],
            ],
            'nullable-on-a-ref' => [
                static fn(): OpenApi => self::schemas(Schema::ref('User', 'Owner')->nullable(true)),
                ['3.0' => [$schema . 'Owner'], '3.1' => [$schema . 'Owner'], '3.2' => [$schema . 'Owner']],
            ],
            'null-type' => [
                static fn(): OpenApi => self::schemas(Schema::null('Nothing')),
                ['3.0' => [$schema . 'Nothing'], '3.1' => [], '3.2' => []],
            ],
            'exclusive-bounds' => [
                static fn(): OpenApi => self::schemas(Schema::number('Ratio')->exclusiveMinimum(0)->exclusiveMaximum(1)),
                ['3.0' => [$schema . 'Ratio'], '3.1' => [], '3.2' => []],
            ],
            'const' => [
                static fn(): OpenApi => self::schemas(Schema::string('Role')->const('admin')),
                ['3.0' => [$schema . 'Role'], '3.1' => [], '3.2' => []],
            ],
            'schema-examples' => [
                static fn(): OpenApi => self::schemas(Schema::string('Name')->examples('Alice', 'Bob')),
                ['3.0' => [$schema . 'Name'], '3.1' => [], '3.2' => []],
            ],
            'multiple-types' => [
                static fn(): OpenApi => self::schemas(Schema::create('Id')->description('A numeric or text id')->type('string', 'integer')),
                ['3.0' => [$schema . 'Id'], '3.1' => [], '3.2' => []],
            ],
            'ref-with-siblings' => [
                static fn(): OpenApi => self::schemas(Schema::ref('User', 'Owner')->description('The owner of the pet')->readOnly(true)),
                ['3.0' => [$schema . 'Owner'], '3.1' => [], '3.2' => []],
            ],
            'binary-content' => [
                static fn(): OpenApi => self::schemas(Schema::string('Avatar')->contentMediaType('image/png')),
                ['3.0' => [$schema . 'Avatar'], '3.1' => [], '3.2' => []],
            ],
            'base64-content' => [
                static fn(): OpenApi => self::schemas(Schema::string('Photo')->contentEncoding('base64')),
                ['3.0' => [$schema . 'Photo'], '3.1' => [], '3.2' => []],
            ],
            'boolean-subschemas' => [
                static fn(): OpenApi => self::schemas(Schema::object('Flexible')->additionalProperties(false)->properties(
                    Schema::array('anything')->items(true),
                    Schema::array('nothing')->items(false),
                )),
                ['3.0' => [$schema . 'Flexible/properties/anything', $schema . 'Flexible/properties/nothing'], '3.1' => [], '3.2' => []],
            ],
            'response-without-description' => [
                static fn(): OpenApi => Documents::compatibility()->components(Components::create()->responses(Response::create('Empty'))),
                ['3.0' => ['/components/responses/Empty'], '3.1' => ['/components/responses/Empty'], '3.2' => []],
            ],
            'no-path' => [
                static fn(): OpenApi => OpenApi::create()
                    ->info(Info::create()->title('Compatibility')->version('1.0.0'))
                    ->components(Components::create()->schemas(Schema::string('Name'))),
                ['3.0' => ['/paths'], '3.1' => [], '3.2' => []],
            ],
            'nullable-with-multiple-types' => [
                static fn(): OpenApi => self::schemas(Schema::create('Id')->type('string', 'integer')->nullable(true)),
                ['3.0' => [$schema . 'Id'], '3.1' => [], '3.2' => []],
            ],
            'nullable-on-a-ref-with-siblings' => [
                static fn(): OpenApi => self::schemas(Schema::ref('User', 'Owner')->description('The owner, if any')->nullable(true)),
                ['3.0' => [$schema . 'Owner'], '3.1' => [$schema . 'Owner'], '3.2' => [$schema . 'Owner']],
            ],
            'base64-binary-content' => [
                static fn(): OpenApi => self::schemas(Schema::string('Photo')->contentMediaType('image/png')->contentEncoding('base64')),
                ['3.0' => [$schema . 'Photo'], '3.1' => [], '3.2' => []],
            ],
        ];
    }

    /**
     * @return iterable<string, array{string, Version}>
     */
    public static function cases(): iterable
    {
        foreach (array_keys(self::rows()) as $row) {
            foreach (Version::cases() as $version) {
                yield $row . ' in ' . $version->value => [$row, $version];
            }
        }
    }

    #[DataProvider('cases')]
    public function test_each_conversion_is_written_for_each_version(string $row, Version $version): void
    {
        [$build, $converted] = self::rows()[$row];

        $result = Serializer::for($version)->serialize($build());

        self::assertResultSerializesTo($result, OpenApi::class, $version, 'compatibility/' . $row);
        self::assertDiagnostics(self::converted($converted[Fixtures::line($version)]), $result->diagnostics());
    }

    #[DataProvider('cases')]
    public function test_each_conversion_is_the_same_in_lenient_mode(string $row, Version $version): void
    {
        [$build, $converted] = self::rows()[$row];

        $result = Serializer::for($version, Strictness::Lenient)->serialize($build());

        self::assertResultSerializesTo($result, OpenApi::class, $version, 'compatibility/' . $row);
        self::assertDiagnostics(self::converted($converted[Fixtures::line($version)]), $result->diagnostics());
    }

    /**
     * @param list<string> $pointers
     *
     * @return list<array{DiagnosticLevel, string}>
     */
    private static function converted(array $pointers): array
    {
        return array_map(static fn(string $pointer): array => [DiagnosticLevel::Converted, $pointer], $pointers);
    }

    private static function schemas(Schema ...$schemas): OpenApi
    {
        return Documents::compatibility()->components(Components::create()->schemas(...$schemas));
    }
}
