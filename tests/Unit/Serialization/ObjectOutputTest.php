<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Tests\Unit\Serialization;

use Ark4ne\OpenApiWriter\Enums\Version;
use Ark4ne\OpenApiWriter\Tests\Support\CompleteObjects;
use Ark4ne\OpenApiWriter\Tests\Support\Fixtures;
use Ark4ne\OpenApiWriter\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Exact output of every object, with every field the line can express, in 3.0, 3.1 and 3.2: key order, `{}` for
 * empty maps, free values written as is, extensions last.
 */
final class ObjectOutputTest extends TestCase
{
    /**
     * @return iterable<string, array{string, Version}>
     */
    public static function cases(): iterable
    {
        foreach (CompleteObjects::cases() as $name => [, $since]) {
            foreach ([Version::V3_0_4, Version::V3_1_2, Version::V3_2_0] as $version) {
                if (CompleteObjects::atLeast($version, $since)) {
                    yield $name . ' in ' . Fixtures::line($version) => [$name, $version];
                }
            }
        }
    }

    #[DataProvider('cases')]
    public function test_each_object_is_written_exactly_for_each_line(string $name, Version $version): void
    {
        $object = CompleteObjects::cases()[$name][0]($version);

        self::assertSerializesTo($object, $version, 'objects/' . $name);
    }
}
