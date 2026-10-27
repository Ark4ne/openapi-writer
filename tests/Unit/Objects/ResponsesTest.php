<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Tests\Unit\Objects;

use Ark4ne\OpenApiWriter\Exceptions\DuplicateObjectIdException;
use Ark4ne\OpenApiWriter\Objects\Reference;
use Ark4ne\OpenApiWriter\Objects\Response;
use Ark4ne\OpenApiWriter\Objects\Responses;

final class ResponsesTest extends ExtensibleSpecObjectTestCase
{
    protected static function objectClass(): string
    {
        return Responses::class;
    }

    public static function fields(): array
    {
        $default = Response::create()->description('Unexpected error');
        $ok = Response::ok();
        $clientError = Response::status('4XX')->description('Client error');

        return [
            'default' => [[$default], $default],
            'responses' => [[$ok, $clientError], ['200' => $ok, '4XX' => $clientError]],
        ];
    }

    public function test_a_response_with_the_default_object_id_goes_to_the_default_field(): void
    {
        $ok = Response::ok();
        $error = Response::create('default')->description('Unexpected error');

        $responses = Responses::create()->responses($ok, $error);

        self::assertSame($error, $responses->default);
        self::assertSame(['200' => $ok], $responses->responses);
    }

    public function test_add_responses_also_routes_the_default_response(): void
    {
        $error = Reference::to('#/components/responses/Error', 'default');

        $responses = Responses::create()->addResponses($error);

        self::assertSame($error, $responses->default);
        self::assertFalse($responses->has('responses'));
    }

    public function test_a_default_response_is_rejected_when_the_default_field_is_set(): void
    {
        $responses = Responses::create()->default(Response::create()->description('Unexpected error'));

        $this->expectException(DuplicateObjectIdException::class);

        $responses->addResponses(Response::create('default')->description('Another error'));
    }

    public function test_responses_keep_the_insertion_order(): void
    {
        $responses = Responses::create()->responses(Response::notFound(), Response::ok(), Response::status('5XX'));

        self::assertSame([404, 200, '5XX'], array_keys($responses->responses ?? []));
    }
}
