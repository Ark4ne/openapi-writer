<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Tests\Unit\Enums;

use Ark4ne\OpenApiWriter\Enums\HttpMethod;
use Ark4ne\OpenApiWriter\Enums\ParameterLocation;
use Ark4ne\OpenApiWriter\Enums\ParameterStyle;
use Ark4ne\OpenApiWriter\Enums\SecuritySchemeType;
use Ark4ne\OpenApiWriter\Enums\Version;
use Ark4ne\OpenApiWriter\Enums\VersionedEnum;
use Ark4ne\OpenApiWriter\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

final class VersionedEnumTest extends TestCase
{
    /**
     * Every case of every versioned enum, with the version that introduced it.
     *
     * @return iterable<string, array{VersionedEnum, Version}>
     */
    public static function cases(): iterable
    {
        foreach ([HttpMethod::class, ParameterLocation::class, ParameterStyle::class, SecuritySchemeType::class] as $enum) {
            foreach ($enum::cases() as $case) {
                $since = match ($case) {
                    HttpMethod::Query, ParameterLocation::Querystring, ParameterStyle::Cookie => Version::V3_2_0,
                    SecuritySchemeType::MutualTLS => Version::V3_1_0,
                    default => Version::V3_0_0,
                };

                yield $enum . '::' . $case->name => [$case, $since];
            }
        }
    }

    #[DataProvider('cases')]
    public function test_each_case_declares_the_version_that_introduced_it(VersionedEnum $case, Version $since): void
    {
        self::assertSame($since, $case->since());
    }
}
