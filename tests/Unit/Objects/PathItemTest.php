<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Tests\Unit\Objects;

use Ark4ne\OpenApiWriter\Exceptions\DuplicateObjectIdException;
use Ark4ne\OpenApiWriter\Exceptions\MissingObjectIdException;
use Ark4ne\OpenApiWriter\Objects\Operation;
use Ark4ne\OpenApiWriter\Objects\Parameter;
use Ark4ne\OpenApiWriter\Objects\PathItem;
use Ark4ne\OpenApiWriter\Objects\Reference;
use Ark4ne\OpenApiWriter\Objects\Server;

final class PathItemTest extends ExtensibleSpecObjectTestCase
{
    protected static function objectClass(): string
    {
        return PathItem::class;
    }

    public static function fields(): array
    {
        $get = Operation::get()->operationId('getUser');
        $put = Operation::put()->operationId('replaceUser');
        $post = Operation::post()->operationId('createUser');
        $delete = Operation::delete()->operationId('deleteUser');
        $options = Operation::options()->operationId('userOptions');
        $head = Operation::head()->operationId('userExists');
        $patch = Operation::patch()->operationId('updateUser');
        $trace = Operation::trace()->operationId('traceUser');
        $query = Operation::query()->operationId('searchUsers');
        $copy = Operation::create('COPY')->operationId('copyUser');
        $server = Server::create()->url('https://eu.example.com');
        $id = Parameter::path('id');
        $page = Reference::to('#/components/parameters/Page');

        return [
            'ref' => [['#/components/pathItems/User'], '#/components/pathItems/User'],
            'summary' => [['A user'], 'A user'],
            'description' => [['Operations on one user.'], 'Operations on one user.'],
            'get' => [[$get], $get],
            'put' => [[$put], $put],
            'post' => [[$post], $post],
            'delete' => [[$delete], $delete],
            'options' => [[$options], $options],
            'head' => [[$head], $head],
            'patch' => [[$patch], $patch],
            'trace' => [[$trace], $trace],
            'query' => [[$query], $query],
            'additionalOperations' => [[$copy], ['COPY' => $copy]],
            'servers' => [[$server], [$server]],
            'parameters' => [[$id, $page], [$id, $page]],
        ];
    }

    public function test_create_takes_the_path_as_object_id(): void
    {
        self::assertSame('/users/{id}', PathItem::create('/users/{id}')->objectId);
    }

    public function test_operations_places_each_operation_under_the_field_named_by_its_object_id(): void
    {
        $get = Operation::get();
        $post = Operation::post();
        $query = Operation::query();

        $pathItem = PathItem::create('/users')->operations($get, $post, $query);

        self::assertSame($get, $pathItem->get);
        self::assertSame($post, $pathItem->post);
        self::assertSame($query, $pathItem->query);
        self::assertFalse($pathItem->has('put'));
        self::assertFalse($pathItem->has('additionalOperations'));
    }

    public function test_operations_places_an_unknown_method_in_additional_operations(): void
    {
        $copy = Operation::create('COPY');
        $purge = Operation::create('PURGE');

        $pathItem = PathItem::create('/users')->operations(Operation::get(), $copy, $purge);

        self::assertSame(['COPY' => $copy, 'PURGE' => $purge], $pathItem->additionalOperations);
    }

    public function test_operations_rejects_an_operation_without_object_id(): void
    {
        $this->expectException(MissingObjectIdException::class);

        PathItem::create('/users')->operations(Operation::create());
    }

    public function test_operations_rejects_the_same_method_twice(): void
    {
        $this->expectException(DuplicateObjectIdException::class);

        PathItem::create('/users')->operations(Operation::get(), Operation::get()->summary('again'));
    }

    public function test_operations_finds_a_fixed_method_whatever_its_case(): void
    {
        $get = Operation::create('GET');

        $pathItem = PathItem::create('/users')->operations($get);

        self::assertSame($get, $pathItem->get);
        self::assertFalse($pathItem->has('additionalOperations'));
    }
}
