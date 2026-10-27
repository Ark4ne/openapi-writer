<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Tests\Unit\Support;

use Ark4ne\OpenApiWriter\Exceptions\InvalidArgumentException;
use Ark4ne\OpenApiWriter\Support\JsonPointer;
use Ark4ne\OpenApiWriter\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

final class JsonPointerTest extends TestCase
{
    public function test_root_is_the_empty_pointer(): void
    {
        self::assertSame('', JsonPointer::root()->toString());
        self::assertSame([], JsonPointer::root()->tokens());
    }

    /**
     * @return iterable<string, array{string, list<string>}>
     */
    public static function pointers(): iterable
    {
        yield 'root' => ['', []];
        yield 'one token' => ['/paths', ['paths']];
        yield 'escaped slash' => ['/paths/~1users~1{id}/get', ['paths', '/users/{id}', 'get']];
        yield 'escaped tilde' => ['/components/schemas/a~0b', ['components', 'schemas', 'a~b']];
        yield 'tilde then slash' => ['/x/~01', ['x', '~1']];
        yield 'array index' => ['/servers/0/url', ['servers', '0', 'url']];
        yield 'empty token' => ['/paths/', ['paths', '']];
    }

    /**
     * @param list<string> $tokens
     */
    #[DataProvider('pointers')]
    public function test_from_string_unescapes_each_token(string $pointer, array $tokens): void
    {
        self::assertSame($tokens, JsonPointer::fromString($pointer)->tokens());
    }

    /**
     * @param list<string> $tokens
     */
    #[DataProvider('pointers')]
    public function test_to_string_escapes_each_token(string $pointer, array $tokens): void
    {
        self::assertSame($pointer, JsonPointer::root()->append(...$tokens)->toString());
    }

    public function test_from_string_rejects_a_pointer_without_leading_slash(): void
    {
        $this->expectException(InvalidArgumentException::class);

        JsonPointer::fromString('paths/~1users');
    }

    public function test_append_returns_a_new_pointer_and_accepts_indexes(): void
    {
        $paths = JsonPointer::fromString('/paths');

        $url = $paths->append('/users', 'get', 'parameters', 0);

        self::assertSame('/paths/~1users/get/parameters/0', $url->toString());
        self::assertSame('/paths', $paths->toString());
    }

    public function test_string_conversion_is_the_pointer(): void
    {
        self::assertSame('/info/summary', (string) JsonPointer::fromString('/info/summary'));
    }
}
