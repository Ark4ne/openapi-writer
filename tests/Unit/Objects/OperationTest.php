<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Tests\Unit\Objects;

use Ark4ne\OpenApiWriter\Exceptions\DuplicateObjectIdException;
use Ark4ne\OpenApiWriter\Exceptions\InvalidArgumentException;
use Ark4ne\OpenApiWriter\Exceptions\MissingObjectIdException;
use Ark4ne\OpenApiWriter\Objects\Callback;
use Ark4ne\OpenApiWriter\Objects\ExternalDocumentation;
use Ark4ne\OpenApiWriter\Objects\Operation;
use Ark4ne\OpenApiWriter\Objects\Parameter;
use Ark4ne\OpenApiWriter\Objects\Reference;
use Ark4ne\OpenApiWriter\Objects\RequestBody;
use Ark4ne\OpenApiWriter\Objects\Response;
use Ark4ne\OpenApiWriter\Objects\Responses;
use Ark4ne\OpenApiWriter\Objects\SecurityRequirement;
use Ark4ne\OpenApiWriter\Objects\Server;
use PHPUnit\Framework\Attributes\DataProvider;

final class OperationTest extends ExtensibleSpecObjectTestCase
{
    protected static function objectClass(): string
    {
        return Operation::class;
    }

    public static function fields(): array
    {
        $docs = ExternalDocumentation::create()->url('https://example.com/docs/users');
        $id = Parameter::path('id');
        $page = Reference::to('#/components/parameters/Page');
        $body = RequestBody::create()->required(true);
        $responses = Responses::create()->responses(Response::ok());
        $callback = Callback::create('onCreated');
        $security = SecurityRequirement::create()->schemes(['api_key' => []]);
        $server = Server::create()->url('https://eu.example.com');

        return [
            'tags' => [['users', 'admin'], ['users', 'admin']],
            'summary' => [['Get a user'], 'Get a user'],
            'description' => [['Returns one user.'], 'Returns one user.'],
            'externalDocs' => [[$docs], $docs],
            'operationId' => [['users.show'], 'users.show'],
            'parameters' => [[$id, $page], [$id, $page]],
            'requestBody' => [[$body], $body],
            'responses' => [[$responses], $responses],
            'callbacks' => [[$callback], ['onCreated' => $callback]],
            'deprecated' => [[true], true],
            'security' => [[$security], [$security]],
            'servers' => [[$server], [$server]],
        ];
    }

    /**
     * @return iterable<string, array{Operation, string}>
     */
    public static function shortcuts(): iterable
    {
        yield 'get' => [Operation::get(), 'get'];
        yield 'put' => [Operation::put(), 'put'];
        yield 'post' => [Operation::post(), 'post'];
        yield 'delete' => [Operation::delete(), 'delete'];
        yield 'options' => [Operation::options(), 'options'];
        yield 'head' => [Operation::head(), 'head'];
        yield 'patch' => [Operation::patch(), 'patch'];
        yield 'trace' => [Operation::trace(), 'trace'];
        yield 'query' => [Operation::query(), 'query'];
    }

    #[DataProvider('shortcuts')]
    public function test_each_http_method_shortcut_takes_the_method_as_object_id(Operation $operation, string $method): void
    {
        self::assertSame($method, $operation->objectId);
    }

    public function test_responses_wraps_responses_keyed_by_status_code(): void
    {
        $ok = Response::ok();
        $notFound = Response::notFound();

        $operation = Operation::get()->responses($ok, $notFound);

        self::assertInstanceOf(Responses::class, $operation->responses);
        self::assertSame(['200' => $ok, '404' => $notFound], $operation->responses->responses);
        self::assertFalse($operation->responses->has('default'));
    }

    public function test_responses_puts_a_default_response_in_the_default_field(): void
    {
        $ok = Response::ok();
        $error = Response::create('default')->description('Unexpected error');

        $operation = Operation::get()->responses($ok, $error);

        self::assertInstanceOf(Responses::class, $operation->responses);
        self::assertSame($error, $operation->responses->default);
        self::assertSame(['200' => $ok], $operation->responses->responses);
    }

    public function test_responses_accepts_references_keyed_by_their_object_id(): void
    {
        $notFound = Reference::to('#/components/responses/NotFound', '404');

        $operation = Operation::get()->responses($notFound);

        self::assertInstanceOf(Responses::class, $operation->responses);
        self::assertSame(['404' => $notFound], $operation->responses->responses);
    }

    public function test_responses_replaces_the_previous_responses(): void
    {
        $created = Response::created();

        $operation = Operation::post()->responses(Response::ok())->responses($created);

        self::assertInstanceOf(Responses::class, $operation->responses);
        self::assertSame(['201' => $created], $operation->responses->responses);
    }

    public function test_responses_rejects_a_responses_object_mixed_with_responses(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Operation::get()->responses(Responses::create(), Response::ok());
    }

    public function test_responses_rejects_a_response_without_object_id(): void
    {
        $this->expectException(MissingObjectIdException::class);

        Operation::get()->responses(Response::create()->description('No status'));
    }

    public function test_responses_rejects_the_same_status_code_twice(): void
    {
        $this->expectException(DuplicateObjectIdException::class);

        Operation::get()->responses(Response::ok(), Response::status(200));
    }

    public function test_add_responses_appends_to_the_existing_responses(): void
    {
        $ok = Response::ok();
        $notFound = Response::notFound();

        $operation = Operation::get()->responses($ok)->addResponses($notFound);

        self::assertInstanceOf(Responses::class, $operation->responses);
        self::assertSame(['200' => $ok, '404' => $notFound], $operation->responses->responses);
    }

    public function test_add_responses_creates_the_responses_when_there_is_none(): void
    {
        $ok = Response::ok();

        $operation = Operation::get()->addResponses($ok);

        self::assertInstanceOf(Responses::class, $operation->responses);
        self::assertSame(['200' => $ok], $operation->responses->responses);
    }

    public function test_add_responses_rejects_a_status_code_already_present(): void
    {
        $this->expectException(DuplicateObjectIdException::class);

        Operation::get()->responses(Response::ok())->addResponses(Response::status(200));
    }

    public function test_responses_never_changes_the_original_operation(): void
    {
        $operation = Operation::get()->responses(Response::ok());

        $operation->addResponses(Response::notFound());

        self::assertInstanceOf(Responses::class, $operation->responses);
        self::assertSame([200], array_keys($operation->responses->responses ?? []));
    }
}
