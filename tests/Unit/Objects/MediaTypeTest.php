<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Tests\Unit\Objects;

use Ark4ne\OpenApiWriter\Exceptions\InvalidArgumentException;
use Ark4ne\OpenApiWriter\Objects\Encoding;
use Ark4ne\OpenApiWriter\Objects\Example;
use Ark4ne\OpenApiWriter\Objects\MediaType;
use Ark4ne\OpenApiWriter\Objects\Schema;
use PHPUnit\Framework\Attributes\DataProvider;

final class MediaTypeTest extends ExtensibleSpecObjectTestCase
{
    protected static function objectClass(): string
    {
        return MediaType::class;
    }

    public static function fields(): array
    {
        $schema = Schema::ref('User');
        $itemSchema = Schema::ref('Event');
        $alice = Example::create('alice')->value(['name' => 'Alice']);
        $avatar = Encoding::create('avatar')->contentType('image/png');
        $metadata = Encoding::create()->contentType('application/json');
        $item = Encoding::create()->contentType('application/json');

        return [
            'description' => [['A user'], 'A user'],
            'schema' => [[$schema], $schema],
            'itemSchema' => [[$itemSchema], $itemSchema],
            'example' => [[['name' => 'Alice']], ['name' => 'Alice']],
            'examples' => [[$alice], ['alice' => $alice]],
            'encoding' => [[$avatar], ['avatar' => $avatar]],
            'prefixEncoding' => [[$metadata], [$metadata]],
            'itemEncoding' => [[$item], $item],
        ];
    }

    /**
     * @return iterable<string, array{MediaType, string}>
     */
    public static function shortcuts(): iterable
    {
        yield 'json' => [MediaType::json(), 'application/json'];
        yield 'xml' => [MediaType::xml(), 'application/xml'];
        yield 'form url encoded' => [MediaType::formUrlEncoded(), 'application/x-www-form-urlencoded'];
        yield 'multipart' => [MediaType::multipart(), 'multipart/form-data'];
        yield 'any media type' => [MediaType::of('text/csv'), 'text/csv'];
    }

    #[DataProvider('shortcuts')]
    public function test_each_shortcut_takes_the_media_type_as_object_id(MediaType $mediaType, string $objectId): void
    {
        self::assertSame($objectId, $mediaType->objectId);
    }

    public function test_example_and_examples_together_are_rejected(): void
    {
        self::assertThrows(InvalidArgumentException::class, static fn(): MediaType => MediaType::json()->example(1)->examples(Example::create('one')->value(1)));
        self::assertThrows(InvalidArgumentException::class, static fn(): MediaType => MediaType::json()->examples(Example::create('one')->value(1))->example(1));
    }
}
