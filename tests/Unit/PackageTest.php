<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Tests\Unit;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;

#[CoversNothing]
final class PackageTest extends TestCase
{
    public function test_composer_maps_the_library_namespace_to_src(): void
    {
        $autoload = self::composer()['autoload'] ?? null;

        self::assertIsArray($autoload);
        self::assertSame(['Ark4ne\\OpenApiWriter\\' => 'src/'], $autoload['psr-4'] ?? null);
    }

    public function test_composer_requires_no_runtime_dependency_besides_php_and_ext_json(): void
    {
        self::assertSame(['php' => '^8.2', 'ext-json' => '*'], self::composer()['require'] ?? null);
    }

    /**
     * @return array<mixed>
     */
    private static function composer(): array
    {
        $composer = json_decode((string) file_get_contents(__DIR__ . '/../../composer.json'), true, flags: JSON_THROW_ON_ERROR);

        self::assertIsArray($composer);

        return $composer;
    }
}
