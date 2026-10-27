<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Tests\Unit\Objects;

use Ark4ne\OpenApiWriter\Objects\Callback;
use Ark4ne\OpenApiWriter\Objects\Operation;
use Ark4ne\OpenApiWriter\Objects\PathItem;
use Ark4ne\OpenApiWriter\Objects\Reference;

final class CallbackTest extends ExtensibleSpecObjectTestCase
{
    protected static function objectClass(): string
    {
        return Callback::class;
    }

    public static function fields(): array
    {
        $notify = PathItem::create('{$request.body#/callbackUrl}')->post(Operation::post());
        $status = Reference::to('#/components/pathItems/Status', '{$request.query.statusUrl}');

        return [
            'pathItems' => [[$notify, $status], ['{$request.body#/callbackUrl}' => $notify, '{$request.query.statusUrl}' => $status]],
        ];
    }
}
