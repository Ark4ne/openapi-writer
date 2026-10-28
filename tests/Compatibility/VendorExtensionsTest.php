<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Tests\Compatibility;

use Ark4ne\OpenApiWriter\Enums\Strictness;
use Ark4ne\OpenApiWriter\Enums\VendorExtensions;
use Ark4ne\OpenApiWriter\Enums\Version;
use Ark4ne\OpenApiWriter\Objects\Components;
use Ark4ne\OpenApiWriter\Objects\MediaType;
use Ark4ne\OpenApiWriter\Objects\OpenApi;
use Ark4ne\OpenApiWriter\Objects\Operation;
use Ark4ne\OpenApiWriter\Objects\PathItem;
use Ark4ne\OpenApiWriter\Objects\RequestBody;
use Ark4ne\OpenApiWriter\Objects\Response;
use Ark4ne\OpenApiWriter\Objects\Schema;
use Ark4ne\OpenApiWriter\Objects\SecurityRequirement;
use Ark4ne\OpenApiWriter\Objects\SecurityScheme;
use Ark4ne\OpenApiWriter\Objects\Tag;
use Ark4ne\OpenApiWriter\Serialization\DiagnosticLevel;
use Ark4ne\OpenApiWriter\Serialization\Serializer;
use Ark4ne\OpenApiWriter\Tests\Support\CompleteObjects;
use Ark4ne\OpenApiWriter\Tests\Support\Documents;
use Ark4ne\OpenApiWriter\Tests\Support\Fixtures;
use Ark4ne\OpenApiWriter\Tests\TestCase;
use Closure;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * `VendorExtensions::Redoc`: webhooks written as `x-webhooks` before 3.1, tag parents as `x-tagGroups` before 3.2
 * (`tests/fixtures/<line>/vendor-extensions/<row>.json`), reported as `Converted` in both modes. Without the option,
 * the same documents follow the specification (UnsupportedFeaturesTest).
 */
final class VendorExtensionsTest extends TestCase
{
    /**
     * Keyed by row: the document, and the pointers reported as Converted, by line.
     *
     * @return array<string, array{Closure(): OpenApi, array<string, list<string>>}>
     */
    public static function rows(): array
    {
        return [
            'webhooks' => [
                static fn(): OpenApi => self::webhooks(),
                ['3.0' => ['/webhooks', '/x-webhooks/userCreated/post/requestBody/content/application~1json/schema'], '3.1' => [], '3.2' => []],
            ],
            'tag-groups' => [
                static fn(): OpenApi => self::tagGroups(),
                ['3.0' => ['/tags/1/parent', '/tags/2/parent', '/tags/4/parent'], '3.1' => ['/tags/1/parent', '/tags/2/parent', '/tags/4/parent'], '3.2' => []],
            ],
            'tags-without-parent' => [
                static fn(): OpenApi => Documents::compatibility()->tags(Tag::create()->name('pets'), Tag::create()->name('users')),
                ['3.0' => [], '3.1' => [], '3.2' => []],
            ],
        ];
    }

    /**
     * @return iterable<string, array{string, Version, Strictness}>
     */
    public static function cases(): iterable
    {
        foreach (array_keys(self::rows()) as $row) {
            foreach (Version::cases() as $version) {
                foreach (Strictness::cases() as $strictness) {
                    yield sprintf('%s in %s, %s', $row, $version->value, $strictness->value) => [$row, $version, $strictness];
                }
            }
        }
    }

    #[DataProvider('cases')]
    public function test_each_vendor_extension_is_written_for_each_version(string $row, Version $version, Strictness $strictness): void
    {
        [$build, $converted] = self::rows()[$row];

        $result = Serializer::for($version, $strictness, VendorExtensions::Redoc)->serialize($build());

        self::assertResultSerializesTo($result, OpenApi::class, $version, 'vendor-extensions/' . $row);
        self::assertDiagnostics(
            array_map(static fn(string $pointer): array => [DiagnosticLevel::Converted, $pointer], $converted[Fixtures::line($version)]),
            $result->diagnostics(),
        );
    }

    /**
     * @return iterable<string, array{Version}>
     */
    public static function versions(): iterable
    {
        foreach (Version::cases() as $version) {
            yield $version->value => [$version];
        }
    }

    #[DataProvider('versions')]
    public function test_extensions_defined_by_the_document_win_over_the_conversion(Version $version): void
    {
        $document = self::userDefined();
        $unsupported = array_values(array_filter([
            CompleteObjects::atLeast($version, Version::V3_1_0) ? null : '/webhooks',
            CompleteObjects::atLeast($version, Version::V3_2_0) ? null : '/tags/1/parent',
        ]));

        if ($unsupported !== []) {
            self::assertUnsupported($unsupported, static fn(): string => $document->toJson($version, extensions: VendorExtensions::Redoc));
        }

        $result = Serializer::for($version, Strictness::Lenient, VendorExtensions::Redoc)->serialize($document);

        self::assertResultSerializesTo($result, OpenApi::class, $version, 'vendor-extensions/defined-by-the-document');
        self::assertDiagnostics(array_map(static fn(string $pointer): array => [DiagnosticLevel::Dropped, $pointer], $unsupported), $result->diagnostics());
    }

    public function test_without_the_option_the_content_of_a_dropped_field_is_not_reported(): void
    {
        $result = Serializer::for(Version::V3_0_4, Strictness::Lenient)->serialize(self::webhooks());

        self::assertDiagnostics([[DiagnosticLevel::Dropped, '/webhooks']], $result->diagnostics());
    }

    public function test_the_content_of_converted_webhooks_is_written_for_the_version(): void
    {
        $document = Documents::compatibility()->webhooks(PathItem::create('search')->query(Operation::query()->responses(Response::ok())));

        self::assertUnsupported(['/x-webhooks/search/query'], static fn(): string => $document->toJson(Version::V3_0_4, extensions: 'redoc'));
    }

    public function test_scopes_of_a_non_oauth_scheme_are_emptied_in_x_webhooks_as_in_paths(): void
    {
        $document = Documents::compatibility()
            ->components(Components::create()->securitySchemes(SecurityScheme::apiKey('key')->name('X-Key')->in('header')))
            ->webhooks(PathItem::create('userCreated')->post(
                Operation::post()->security(SecurityRequirement::create()->schemes(['key' => ['read']]))->responses(Response::ok()),
            ));

        $result = Serializer::for(Version::V3_0_4, Strictness::Lenient, VendorExtensions::Redoc)->serialize($document);

        self::assertStringContainsString('"x-webhooks":{"userCreated":{"post":{"responses":{"200":{"description":"OK"}},"security":[{"key":[]}]', $result->toJson());
        self::assertDiagnostics(
            [[DiagnosticLevel::Converted, '/webhooks'], [DiagnosticLevel::Dropped, '/x-webhooks/userCreated/post/security/0/key']],
            $result->diagnostics(),
        );
    }

    public function test_an_x_webhooks_defined_by_the_document_is_left_as_written(): void
    {
        $document = Documents::compatibility()
            ->components(Components::create()->securitySchemes(SecurityScheme::apiKey('key')->name('X-Key')->in('header')))
            ->x('x-webhooks', ['legacy' => ['post' => ['security' => [['key' => ['read']]]]]]);

        $result = Serializer::for(Version::V3_0_4, Strictness::Strict, VendorExtensions::Redoc)->serialize($document);

        self::assertStringContainsString('"x-webhooks":{"legacy":{"post":{"security":[{"key":["read"]}]', $result->toJson());
        self::assertTrue($result->diagnostics()->isEmpty());
    }

    public function test_a_tag_written_alone_keeps_its_parent_unsupported(): void
    {
        $tag = Tag::create()->name('pets')->parent('animals');

        self::assertUnsupported(['/parent'], static fn(): string => $tag->toJson(Version::V3_0_4, extensions: VendorExtensions::Redoc));
    }

    /**
     * A cycle (`a` ↔ `b`) or a tag without name is not followed; a repeated tag is listed once, under its first parent.
     */
    public function test_nested_tag_parents_form_one_group_per_topmost_ancestor(): void
    {
        $document = Documents::compatibility()->tags(
            Tag::create()->name('cats')->parent('pets'),
            Tag::create()->name('pets')->parent('animals'),
            Tag::create()->name('a')->parent('b'),
            Tag::create()->name('b')->parent('a'),
            Tag::create()->name('animals'),
            Tag::create()->name('pets')->parent('other'),
            Tag::create()->parent('animals'),
        );

        self::assertSame(
            [['name' => 'animals', 'tags' => ['cats', 'pets', 'animals']], ['name' => 'a', 'tags' => ['a']], ['name' => 'b', 'tags' => ['b']]],
            $document->toArray(Version::V3_1_2, VendorExtensions::Redoc)['x-tagGroups'] ?? null,
        );
    }

    private static function webhooks(): OpenApi
    {
        return Documents::compatibility()->webhooks(PathItem::create('userCreated')->post(
            Operation::post()
                ->requestBody(RequestBody::create()->content(MediaType::json()->schema(Schema::object()->properties(Schema::string('nickname'))->nullable(true))))
                ->responses(Response::ok()),
        ));
    }

    private static function tagGroups(): OpenApi
    {
        return Documents::compatibility()->tags(
            Tag::create()->name('animals')->description('Every animal'),
            Tag::create()->name('pets')->parent('animals'),
            Tag::create()->name('cats')->parent('pets'),
            Tag::create()->name('billing'),
            Tag::create()->name('invoices')->parent('accounting'),
        );
    }

    private static function userDefined(): OpenApi
    {
        return Documents::compatibility()
            ->webhooks(PathItem::create('userCreated')->post(Operation::post()->responses(Response::ok())))
            ->tags(Tag::create()->name('animals'), Tag::create()->name('pets')->parent('animals'))
            ->x('x-webhooks', ['legacy' => ['post' => ['responses' => ['200' => ['description' => 'OK']]]]])
            ->x('x-tagGroups', [['name' => 'Everything', 'tags' => ['animals', 'pets']]]);
    }
}
