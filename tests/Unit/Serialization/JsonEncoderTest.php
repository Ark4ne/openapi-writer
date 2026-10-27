<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Tests\Unit\Serialization;

use Ark4ne\OpenApiWriter\Serialization\Encoders\JsonEncoder;
use Ark4ne\OpenApiWriter\Tests\Support\Fixtures;
use Ark4ne\OpenApiWriter\Tests\TestCase;
use JsonException;
use stdClass;

final class JsonEncoderTest extends TestCase
{
    public function test_it_writes_compact_json_with_the_default_flags(): void
    {
        $json = (new JsonEncoder())->encode(self::document());

        self::assertSame(self::compact(Fixtures::DIRECTORY . 'encoders/document.json'), $json);
    }

    public function test_extra_flags_are_added_to_the_default_flags(): void
    {
        $json = (new JsonEncoder(JSON_PRETTY_PRINT))->encode(self::document());

        self::assertSame((string) file_get_contents(Fixtures::DIRECTORY . 'encoders/document.json'), $json . "\n");
    }

    public function test_a_value_that_json_cannot_write_throws(): void
    {
        $this->expectException(JsonException::class);

        (new JsonEncoder())->encode(['value' => NAN]);
    }

    /**
     * @return array<string, mixed>
     */
    private static function document(): array
    {
        return [
            'url' => 'https://example.com/users/{id}',
            'title' => 'Élève — 学生',
            'float' => 1.0,
            'emptyMap' => new stdClass(),
            'emptyList' => [],
        ];
    }

    private static function compact(string $file): string
    {
        return json_encode(json_decode((string) file_get_contents($file), false, 512, JSON_THROW_ON_ERROR), JsonEncoder::DEFAULT_FLAGS | JSON_THROW_ON_ERROR);
    }
}
