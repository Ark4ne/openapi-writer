<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Tests\Unit\Objects;

use Ark4ne\OpenApiWriter\Enums\ParameterLocation;
use Ark4ne\OpenApiWriter\Enums\ParameterStyle;
use Ark4ne\OpenApiWriter\Exceptions\InvalidArgumentException;
use Ark4ne\OpenApiWriter\Objects\Example;
use Ark4ne\OpenApiWriter\Objects\MediaType;
use Ark4ne\OpenApiWriter\Objects\Parameter;
use Ark4ne\OpenApiWriter\Objects\Schema;
use PHPUnit\Framework\Attributes\DataProvider;

final class ParameterTest extends ExtensibleSpecObjectTestCase
{
    protected static function objectClass(): string
    {
        return Parameter::class;
    }

    public static function fields(): array
    {
        $schema = Schema::integer();
        $first = Example::create('first')->value(1);
        $last = Example::create('last')->value(100);
        $json = MediaType::json()->schema(Schema::object());

        return [
            'name' => [['page'], 'page'],
            'in' => [['query'], ParameterLocation::Query],
            'description' => [['The page to return'], 'The page to return'],
            'required' => [[true], true],
            'deprecated' => [[true], true],
            'allowEmptyValue' => [[true], true],
            'style' => [['form'], ParameterStyle::Form],
            'explode' => [[false], false],
            'allowReserved' => [[true], true],
            'schema' => [[$schema], $schema],
            'example' => [[2], 2],
            'examples' => [[$first, $last], ['first' => $first, 'last' => $last]],
            'content' => [[$json], ['application/json' => $json]],
        ];
    }

    /**
     * @return iterable<string, array{Parameter, string, ParameterLocation}>
     */
    public static function shortcuts(): iterable
    {
        yield 'path' => [Parameter::path('id'), 'id', ParameterLocation::Path];
        yield 'query' => [Parameter::query('page'), 'page', ParameterLocation::Query];
        yield 'header' => [Parameter::header('X-Request-Id'), 'X-Request-Id', ParameterLocation::Header];
        yield 'cookie' => [Parameter::cookie('session'), 'session', ParameterLocation::Cookie];
        yield 'querystring' => [Parameter::querystring('filter'), 'filter', ParameterLocation::Querystring];
    }

    #[DataProvider('shortcuts')]
    public function test_each_location_shortcut_sets_the_name_and_location(Parameter $parameter, string $name, ParameterLocation $in): void
    {
        self::assertSame($name, $parameter->name);
        self::assertSame($in, $parameter->in);
    }

    public function test_path_shortcut_makes_the_parameter_required(): void
    {
        self::assertTrue(Parameter::path('id')->required);
        self::assertFalse(Parameter::query('page')->has('required'));
    }

    public function test_shortcuts_take_an_optional_object_id(): void
    {
        self::assertSame('Page', Parameter::query('page', 'Page')->objectId);
        self::assertNull(Parameter::query('page')->objectId);
    }

    public function test_in_accepts_the_enum(): void
    {
        self::assertSame(ParameterLocation::Header, Parameter::create()->in(ParameterLocation::Header)->in);
    }

    public function test_example_and_examples_together_are_rejected(): void
    {
        self::assertThrows(InvalidArgumentException::class, static fn(): Parameter => Parameter::query('page')->example(1)->examples(Example::create('first')->value(1)));
        self::assertThrows(InvalidArgumentException::class, static fn(): Parameter => Parameter::query('page')->examples(Example::create('first')->value(1))->example(1));
    }

    public function test_empty_examples_do_not_exclude_example(): void
    {
        self::assertSame(1, Parameter::query('page')->examples()->example(1)->example);
        self::assertSame([], Parameter::query('page')->example(1)->examples()->examples);
    }
}
