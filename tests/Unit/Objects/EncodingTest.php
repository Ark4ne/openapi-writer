<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Tests\Unit\Objects;

use Ark4ne\OpenApiWriter\Enums\ParameterStyle;
use Ark4ne\OpenApiWriter\Exceptions\InvalidArgumentException;
use Ark4ne\OpenApiWriter\Objects\Encoding;
use Ark4ne\OpenApiWriter\Objects\Header;
use Ark4ne\OpenApiWriter\Objects\Schema;

final class EncodingTest extends ExtensibleSpecObjectTestCase
{
    protected static function objectClass(): string
    {
        return Encoding::class;
    }

    public static function fields(): array
    {
        $rateLimit = Header::create('X-Rate-Limit')->schema(Schema::integer());
        $nested = Encoding::create('thumbnail')->contentType('image/jpeg');
        $prefix = Encoding::create()->contentType('application/json');
        $item = Encoding::create()->contentType('image/png');

        return [
            'contentType' => [['image/png, image/jpeg'], 'image/png, image/jpeg'],
            'headers' => [[$rateLimit], ['X-Rate-Limit' => $rateLimit]],
            'style' => [['form'], ParameterStyle::Form],
            'explode' => [[true], true],
            'allowReserved' => [[true], true],
            'encoding' => [[$nested], ['thumbnail' => $nested]],
            'prefixEncoding' => [[$prefix], [$prefix]],
            'itemEncoding' => [[$item], $item],
        ];
    }

    public function test_encoding_and_positional_encodings_together_are_rejected(): void
    {
        $part = Encoding::create('avatar')->contentType('image/png');
        $item = Encoding::create()->contentType('image/png');

        self::assertThrows(InvalidArgumentException::class, static fn(): Encoding => Encoding::create('parts')->encoding($part)->prefixEncoding($item));
        self::assertThrows(InvalidArgumentException::class, static fn(): Encoding => Encoding::create('parts')->encoding($part)->addPrefixEncoding($item));
        self::assertThrows(InvalidArgumentException::class, static fn(): Encoding => Encoding::create('parts')->encoding($part)->itemEncoding($item));
        self::assertThrows(InvalidArgumentException::class, static fn(): Encoding => Encoding::create('parts')->prefixEncoding($item)->encoding($part));
        self::assertThrows(InvalidArgumentException::class, static fn(): Encoding => Encoding::create('parts')->itemEncoding($item)->addEncoding($part));
    }

    public function test_empty_encodings_do_not_exclude_each_other(): void
    {
        $item = Encoding::create()->contentType('image/png');

        self::assertSame($item, Encoding::create('parts')->encoding()->itemEncoding($item)->itemEncoding);
        self::assertSame([$item], Encoding::create('parts')->prefixEncoding()->encoding()->prefixEncoding($item)->prefixEncoding);
        self::assertSame([], Encoding::create('parts')->itemEncoding($item)->itemEncoding(null)->encoding()->encoding);
    }
}
