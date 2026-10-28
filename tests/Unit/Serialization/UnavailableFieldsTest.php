<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Tests\Unit\Serialization;

use Ark4ne\OpenApiWriter\Enums\Strictness;
use Ark4ne\OpenApiWriter\Enums\VendorExtensions;
use Ark4ne\OpenApiWriter\Enums\Version;
use Ark4ne\OpenApiWriter\Objects\Components;
use Ark4ne\OpenApiWriter\Objects\Example;
use Ark4ne\OpenApiWriter\Objects\Info;
use Ark4ne\OpenApiWriter\Objects\OpenApi;
use Ark4ne\OpenApiWriter\Objects\Schema;
use Ark4ne\OpenApiWriter\Objects\Tag;
use Ark4ne\OpenApiWriter\Serialization\Context;
use Ark4ne\OpenApiWriter\Serialization\DiagnosticLevel;
use Ark4ne\OpenApiWriter\Serialization\Rewriters\RewriterChain;
use Ark4ne\OpenApiWriter\Serialization\Serializer;
use Ark4ne\OpenApiWriter\Tests\Support\AnnouncingRewriter;
use Ark4ne\OpenApiWriter\Tests\Support\Documents;
use Ark4ne\OpenApiWriter\Tests\TestCase;

/**
 * A field unknown to the target version is left out, unless a rewriter converts it (UnavailableFieldRewriter): the
 * other rewriters never see it.
 */
final class UnavailableFieldsTest extends TestCase
{
    public function test_a_keyword_unknown_to_3_0_is_not_moved_into_the_branch_of_a_nullable_schema(): void
    {
        $document = self::schemas(Schema::create('Pair')->prefixItems(Schema::string())->nullable(true));

        self::assertUnsupported(['/components/schemas/Pair/prefixItems'], static fn(): string => $document->toJson(Version::V3_0_4, extensions: VendorExtensions::Redoc));
    }

    public function test_a_keyword_unknown_to_3_0_is_not_a_sibling_of_a_ref(): void
    {
        $document = self::schemas(Schema::ref('User', 'Owner')->comment('internal'));

        $result = Serializer::for(Version::V3_0_4, Strictness::Lenient)->serialize($document);

        self::assertStringContainsString('"Owner":{"$ref":"#/components/schemas/User"}', $result->toJson());
        self::assertDiagnostics([[DiagnosticLevel::Dropped, '/components/schemas/Owner/$comment']], $result->diagnostics());
    }

    public function test_a_value_that_cannot_be_written_in_a_field_left_out_is_not_read(): void
    {
        $document = Documents::compatibility()->components(Components::create()->examples(Example::create('Ratio')->dataValue(NAN)));

        $result = Serializer::for(Version::V3_0_4, Strictness::Lenient)->serialize($document);

        self::assertDiagnostics([[DiagnosticLevel::Dropped, '/components/examples/Ratio/dataValue']], $result->diagnostics());
    }

    public function test_a_field_whose_announced_conversion_is_not_made_is_left_out(): void
    {
        $serializer = Serializer::for(Version::V3_0_4, Strictness::Lenient, rewriters: new RewriterChain(new AnnouncingRewriter()));

        $result = $serializer->serialize(Info::create()->title('API')->summary('Short')->version('1.0.0'));

        self::assertSame(['title' => 'API', 'version' => '1.0.0'], $result->toArray());
        self::assertDiagnostics([[DiagnosticLevel::Dropped, '/summary']], $result->diagnostics());
    }

    public function test_the_chain_tells_where_a_rewriter_converts_an_unavailable_field(): void
    {
        $chain = RewriterChain::default();
        $document = OpenApi::create()->tags(Tag::create()->name('pets')->parent('animals'));
        $redoc = Context::root(Version::V3_0_4, Strictness::Strict, VendorExtensions::Redoc, $document);
        $none = Context::root(Version::V3_0_4, Strictness::Strict, VendorExtensions::None, $document);

        self::assertSame('x-webhooks', $chain->convertsTo($document->webhooks(), 'webhooks', $redoc));
        self::assertSame('parent', $chain->convertsTo(Tag::create()->parent('animals'), 'parent', $redoc));
        self::assertNull($chain->convertsTo(Tag::create()->parent('animals'), 'parent', $none));
        self::assertNull($chain->convertsTo(Tag::create()->summary('Pets'), 'summary', $redoc));
        self::assertNull($chain->convertsTo($document->x('x-webhooks', []), 'webhooks', $redoc));
    }

    private static function schemas(Schema ...$schemas): OpenApi
    {
        return Documents::compatibility()->components(Components::create()->schemas(...$schemas));
    }
}
