<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Tests\Unit\Objects;

use Ark4ne\OpenApiWriter\Exceptions\InvalidArgumentException;
use Ark4ne\OpenApiWriter\Objects\Header;
use Ark4ne\OpenApiWriter\Objects\Link;
use Ark4ne\OpenApiWriter\Objects\MediaType;
use Ark4ne\OpenApiWriter\Objects\Response;
use Ark4ne\OpenApiWriter\Objects\Schema;
use PHPUnit\Framework\Attributes\DataProvider;

final class ResponseTest extends ExtensibleSpecObjectTestCase
{
    protected static function objectClass(): string
    {
        return Response::class;
    }

    public static function fields(): array
    {
        $rateLimit = Header::create('X-Rate-Limit')->schema(Schema::integer());
        $json = MediaType::json()->schema(Schema::ref('User'));
        $repositories = Link::create('repositories')->operationId('getRepositoriesByOwner');

        return [
            'summary' => [['A user'], 'A user'],
            'description' => [['The requested user'], 'The requested user'],
            'headers' => [[$rateLimit], ['X-Rate-Limit' => $rateLimit]],
            'content' => [[$json], ['application/json' => $json]],
            'links' => [[$repositories], ['repositories' => $repositories]],
        ];
    }

    /**
     * @return iterable<string, array{Response, string, string}>
     */
    public static function namedResponses(): iterable
    {
        yield 'ok' => [Response::ok(), '200', 'OK'];
        yield 'created' => [Response::created(), '201', 'Created'];
        yield 'accepted' => [Response::accepted(), '202', 'Accepted'];
        yield 'no content' => [Response::noContent(), '204', 'No Content'];
        yield 'moved permanently' => [Response::movedPermanently(), '301', 'Moved Permanently'];
        yield 'found' => [Response::found(), '302', 'Found'];
        yield 'not modified' => [Response::notModified(), '304', 'Not Modified'];
        yield 'bad request' => [Response::badRequest(), '400', 'Bad Request'];
        yield 'unauthorized' => [Response::unauthorized(), '401', 'Unauthorized'];
        yield 'forbidden' => [Response::forbidden(), '403', 'Forbidden'];
        yield 'not found' => [Response::notFound(), '404', 'Not Found'];
        yield 'method not allowed' => [Response::methodNotAllowed(), '405', 'Method Not Allowed'];
        yield 'conflict' => [Response::conflict(), '409', 'Conflict'];
        yield 'unprocessable entity' => [Response::unprocessableEntity(), '422', 'Unprocessable Entity'];
        yield 'too many requests' => [Response::tooManyRequests(), '429', 'Too Many Requests'];
        yield 'internal server error' => [Response::internalServerError(), '500', 'Internal Server Error'];
        yield 'service unavailable' => [Response::serviceUnavailable(), '503', 'Service Unavailable'];
    }

    #[DataProvider('namedResponses')]
    public function test_each_named_response_sets_its_status_code_and_reason_phrase(Response $response, string $status, string $description): void
    {
        self::assertSame($status, $response->objectId);
        self::assertSame($description, $response->description);
    }

    /**
     * @return iterable<string, array{int|string, string}>
     */
    public static function statusCodes(): iterable
    {
        yield 'integer' => [418, '418'];
        yield 'string' => ['418', '418'];
        yield 'lowest' => [100, '100'];
        yield 'highest' => [599, '599'];
        yield 'range' => ['4XX', '4XX'];
        yield 'first range' => ['1XX', '1XX'];
        yield 'last range' => ['5XX', '5XX'];
    }

    #[DataProvider('statusCodes')]
    public function test_status_takes_any_status_code_or_range_without_description(int|string $status, string $objectId): void
    {
        $response = Response::status($status);

        self::assertSame($objectId, $response->objectId);
        self::assertFalse($response->has('description'));
    }

    /**
     * @return iterable<string, array{int|string}>
     */
    public static function malformedStatusCodes(): iterable
    {
        yield 'too low' => [99];
        yield 'too high' => [600];
        yield 'zero' => [0];
        yield 'not a number' => ['abc'];
        yield 'lowercase range' => ['4xx'];
        yield 'unknown range' => ['6XX'];
        yield 'partial range' => ['40X'];
        yield 'default' => ['default'];
        yield 'empty' => [''];
    }

    #[DataProvider('malformedStatusCodes')]
    public function test_status_rejects_a_malformed_status_code(int|string $status): void
    {
        $this->expectException(InvalidArgumentException::class);

        Response::status($status);
    }
}
