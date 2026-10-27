<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Tests\Unit\Serialization;

use Ark4ne\OpenApiWriter\Enums\Strictness;
use Ark4ne\OpenApiWriter\Enums\Version;
use Ark4ne\OpenApiWriter\Exceptions\InvalidArgumentException;
use Ark4ne\OpenApiWriter\Objects\Info;
use Ark4ne\OpenApiWriter\Objects\MediaType;
use Ark4ne\OpenApiWriter\Objects\OpenApi;
use Ark4ne\OpenApiWriter\Objects\Operation;
use Ark4ne\OpenApiWriter\Objects\PathItem;
use Ark4ne\OpenApiWriter\Objects\Response;
use Ark4ne\OpenApiWriter\Objects\Schema;
use Ark4ne\OpenApiWriter\Serialization\DiagnosticLevel;
use Ark4ne\OpenApiWriter\Serialization\Rewriters\RewriterChain;
use Ark4ne\OpenApiWriter\Serialization\Serializer;
use Ark4ne\OpenApiWriter\Tests\Support\RenamingRewriter;
use Ark4ne\OpenApiWriter\Tests\TestCase;

final class SerializerTest extends TestCase
{
    public function test_for_defaults_to_3_1_2_in_strict_mode_with_the_default_rewriters(): void
    {
        $serializer = Serializer::for();

        self::assertSame(Version::V3_1_2, $serializer->version);
        self::assertSame(Strictness::Strict, $serializer->strictness);
        self::assertEquals(RewriterChain::default(), $serializer->rewriters);
    }

    public function test_for_accepts_the_enums_or_their_values(): void
    {
        $fromValues = Serializer::for('3.0.4', 'lenient');
        $fromEnums = Serializer::for(Version::V3_2_0, Strictness::Strict);

        self::assertSame(Version::V3_0_4, $fromValues->version);
        self::assertSame(Strictness::Lenient, $fromValues->strictness);
        self::assertSame(Version::V3_2_0, $fromEnums->version);
    }

    public function test_for_rejects_an_unknown_version(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Serializer::for('2.0');
    }

    public function test_for_rejects_an_unknown_strictness(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Serializer::for(Version::V3_1_2, 'loose');
    }

    public function test_serialize_returns_the_document_and_its_version(): void
    {
        $result = Serializer::for(Version::V3_0_4)->serialize(self::document());

        self::assertSame(Version::V3_0_4, $result->version);
        self::assertTrue($result->diagnostics()->isEmpty());
        self::assertResultSerializesTo($result, OpenApi::class, Version::V3_0_4, 'serializer/document');
    }

    public function test_the_target_version_wins_over_the_version_declared_in_the_document(): void
    {
        $result = Serializer::for(Version::V3_1_2)->serialize(self::document()->openapi(Version::V3_0_4));

        self::assertSame(Version::V3_1_2, $result->version);
        self::assertResultSerializesTo($result, OpenApi::class, Version::V3_1_2, 'serializer/document');
    }

    public function test_the_result_writes_the_same_document_in_every_format(): void
    {
        $result = Serializer::for(Version::V3_0_4)->serialize(self::document());

        self::assertResultSerializesTo($result, OpenApi::class, Version::V3_0_4, 'serializer/document');
        self::assertSame(self::document()->toArray(Version::V3_0_4), $result->toArray());
        self::assertSame(self::document()->toJson(Version::V3_0_4), $result->toJson());
        self::assertSame(self::document()->toYaml(Version::V3_0_4), $result->toYaml());
    }

    public function test_lenient_mode_drops_what_the_version_cannot_express_and_reports_it(): void
    {
        $document = self::document()->info(Info::create()->title('Users API')->summary('Users')->version('1.0.0'));

        $result = Serializer::for(Version::V3_0_4, Strictness::Lenient)->serialize($document);

        self::assertDiagnostics([[DiagnosticLevel::Dropped, '/info/summary']], $result->diagnostics());
        self::assertResultSerializesTo($result, OpenApi::class, Version::V3_0_4, 'serializer/document');
    }

    public function test_strict_mode_throws_once_with_every_unsupported_feature(): void
    {
        $document = self::document()
            ->info(Info::create()->title('Users API')->summary('Users')->version('1.0.0'))
            ->webhooks(PathItem::create('userCreated')->post(Operation::post()->responses(Response::ok())));

        self::assertUnsupported(['/info/summary', '/webhooks'], static fn(): string => $document->toJson(Version::V3_0_4));
    }

    public function test_conversions_are_reported_in_both_modes(): void
    {
        $document = self::document()->paths(PathItem::create('/users')->get(
            Operation::get()->responses(Response::ok()->content(MediaType::json()->schema(Schema::string()->nullable(true)))),
        ));

        $strict = Serializer::for(Version::V3_0_4)->serialize($document);
        $lenient = Serializer::for(Version::V3_0_4, Strictness::Lenient)->serialize($document);

        $pointer = '/paths/~1users/get/responses/200/content/application~1json/schema';
        self::assertDiagnostics([[DiagnosticLevel::Converted, $pointer]], $strict->diagnostics());
        self::assertDiagnostics([[DiagnosticLevel::Converted, $pointer]], $lenient->diagnostics());
    }

    public function test_a_custom_rewriter_chain_replaces_the_default_one(): void
    {
        $serializer = Serializer::for(Version::V3_1_2, Strictness::Strict, new RewriterChain(new RenamingRewriter()));

        $result = $serializer->serialize(Info::create()->title('Users API')->version('1.0.0'));

        self::assertSame(['title' => 'Renamed', 'version' => '1.0.0'], $result->toArray());
    }

    private static function document(): OpenApi
    {
        return OpenApi::create()
            ->info(Info::create()->title('Users API')->version('1.0.0'))
            ->paths(PathItem::create('/users')->get(Operation::get()->operationId('listUsers')->responses(Response::ok())));
    }
}
