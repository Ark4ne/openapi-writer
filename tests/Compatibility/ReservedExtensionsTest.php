<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Tests\Compatibility;

use Ark4ne\OpenApiWriter\Enums\Strictness;
use Ark4ne\OpenApiWriter\Enums\Version;
use Ark4ne\OpenApiWriter\Objects\Info;
use Ark4ne\OpenApiWriter\Objects\OpenApi;
use Ark4ne\OpenApiWriter\Serialization\DiagnosticLevel;
use Ark4ne\OpenApiWriter\Serialization\Serializer;
use Ark4ne\OpenApiWriter\Tests\Support\CompleteObjects;
use Ark4ne\OpenApiWriter\Tests\Support\Documents;
use Ark4ne\OpenApiWriter\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * The prefixes `x-oai-` and `x-oas-` are reserved from 3.1: written in 3.0, rejected (strict) or dropped (lenient)
 * from 3.1. Other extensions are written in every version.
 */
final class ReservedExtensionsTest extends TestCase
{
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
    public function test_strict_mode_writes_reserved_extensions_in_3_0_only(Version $version): void
    {
        $document = self::document();

        if (CompleteObjects::atLeast($version, Version::V3_1_0)) {
            self::assertUnsupported(['/info/x-oai-status', '/x-oas-generator'], static fn(): string => $document->toJson($version));

            return;
        }

        self::assertSerializesTo($document, $version, 'unsupported/reserved-extensions');
    }

    #[DataProvider('versions')]
    public function test_lenient_mode_drops_reserved_extensions_from_3_1(Version $version): void
    {
        $result = Serializer::for($version, Strictness::Lenient)->serialize(self::document());

        $dropped = CompleteObjects::atLeast($version, Version::V3_1_0) ? ['/info/x-oai-status', '/x-oas-generator'] : [];
        self::assertResultSerializesTo($result, OpenApi::class, $version, 'unsupported/reserved-extensions');
        self::assertDiagnostics(array_map(static fn(string $pointer): array => [DiagnosticLevel::Dropped, $pointer], $dropped), $result->diagnostics());
    }

    private static function document(): OpenApi
    {
        return Documents::compatibility()
            ->info(Info::create()->title('Compatibility')->version('1.0.0')->x('oai-status', 'beta')->x('internal', true))
            ->x('x-oas-generator', 'ark4ne/openapi-writer');
    }
}
