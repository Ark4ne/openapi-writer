<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Tests\Unit\Objects;

use Ark4ne\OpenApiWriter\Enums\ParameterStyle;
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
}
