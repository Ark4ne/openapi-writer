<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Tests\Unit\Objects;

use Ark4ne\OpenApiWriter\Enums\ParameterStyle;
use Ark4ne\OpenApiWriter\Exceptions\InvalidArgumentException;
use Ark4ne\OpenApiWriter\Objects\Example;
use Ark4ne\OpenApiWriter\Objects\Header;
use Ark4ne\OpenApiWriter\Objects\MediaType;
use Ark4ne\OpenApiWriter\Objects\Schema;

final class HeaderTest extends ExtensibleSpecObjectTestCase
{
    protected static function objectClass(): string
    {
        return Header::class;
    }

    public static function fields(): array
    {
        $schema = Schema::integer();
        $low = Example::create('low')->value(10);
        $plain = MediaType::of('text/plain')->schema(Schema::string());

        return [
            'description' => [['Remaining requests'], 'Remaining requests'],
            'required' => [[true], true],
            'deprecated' => [[true], true],
            'style' => [['simple'], ParameterStyle::Simple],
            'explode' => [[false], false],
            'schema' => [[$schema], $schema],
            'example' => [[99], 99],
            'examples' => [[$low], ['low' => $low]],
            'content' => [[$plain], ['text/plain' => $plain]],
        ];
    }

    public function test_example_and_examples_together_are_rejected(): void
    {
        self::assertThrows(InvalidArgumentException::class, static fn(): Header => Header::create()->example(1)->examples(Example::create('low')->value(1)));
        self::assertThrows(InvalidArgumentException::class, static fn(): Header => Header::create()->examples(Example::create('low')->value(1))->example(1));
    }

    public function test_empty_examples_do_not_exclude_example(): void
    {
        self::assertSame(1, Header::create()->examples()->example(1)->example);
        self::assertSame([], Header::create()->example(1)->examples()->examples);
    }
}
