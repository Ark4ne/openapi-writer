<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Tests\Unit\Objects;

use Ark4ne\OpenApiWriter\Objects\MediaType;
use Ark4ne\OpenApiWriter\Objects\RequestBody;
use Ark4ne\OpenApiWriter\Objects\Schema;

final class RequestBodyTest extends ExtensibleSpecObjectTestCase
{
    protected static function objectClass(): string
    {
        return RequestBody::class;
    }

    public static function fields(): array
    {
        $json = MediaType::json()->schema(Schema::ref('User'));
        $xml = MediaType::xml()->schema(Schema::ref('User'));

        return [
            'description' => [['The user to create'], 'The user to create'],
            'content' => [[$json, $xml], ['application/json' => $json, 'application/xml' => $xml]],
            'required' => [[true], true],
        ];
    }
}
