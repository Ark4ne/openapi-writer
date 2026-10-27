<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Tests\Unit\Enums;

use Ark4ne\OpenApiWriter\Enums\Version;
use Ark4ne\OpenApiWriter\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

final class VersionTest extends TestCase
{
    public function test_the_default_version_is_3_1_2(): void
    {
        self::assertSame(Version::V3_1_2, Version::default());
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function supportedVersions(): iterable
    {
        foreach (['3.0.0', '3.0.1', '3.0.2', '3.0.3', '3.0.4', '3.1.0', '3.1.1', '3.1.2', '3.2.0'] as $value) {
            yield $value => [$value];
        }
    }

    #[DataProvider('supportedVersions')]
    public function test_every_patch_version_of_the_three_lines_is_supported(string $value): void
    {
        self::assertSame($value, Version::tryFrom($value)?->value);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function unsupportedVersions(): iterable
    {
        foreach (['2.0', '3.0', '3.0.5', '3.1', '3.1.3', '3.2.1', '3.3.0'] as $value) {
            yield $value => [$value];
        }
    }

    #[DataProvider('unsupportedVersions')]
    public function test_no_other_version_is_supported(string $value): void
    {
        self::assertNull(Version::tryFrom($value));
    }

    /**
     * @return iterable<string, array{Version, string}>
     */
    public static function lines(): iterable
    {
        foreach (Version::cases() as $version) {
            yield $version->value => [$version, substr($version->value, 0, 3)];
        }
    }

    #[DataProvider('lines')]
    public function test_line_drops_the_patch_number(Version $version, string $line): void
    {
        self::assertSame($line, $version->line());
    }

    /**
     * @return iterable<string, array{Version, Version, bool}>
     */
    public static function comparisons(): iterable
    {
        foreach (Version::cases() as $version) {
            foreach (Version::cases() as $other) {
                yield $version->value . ' >= ' . $other->value => [$version, $other, version_compare($version->value, $other->value, '>=')];
            }
        }
    }

    #[DataProvider('comparisons')]
    public function test_is_at_least_compares_versions(Version $version, Version $other, bool $expected): void
    {
        self::assertSame($expected, $version->isAtLeast($other));
    }
}
