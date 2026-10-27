<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Tests\Validation\Rules;

use Ark4ne\OpenApiWriter\Enums\Version;
use Ark4ne\OpenApiWriter\Tests\Validation\RuleTestCase;
use Ark4ne\OpenApiWriter\Validation\Rule;
use Ark4ne\OpenApiWriter\Validation\Rules\UniqueOperationIdRule;
use Ark4ne\OpenApiWriter\Validation\Severity;

final class UniqueOperationIdRuleTest extends RuleTestCase
{
    protected static function rule(): Rule
    {
        return new UniqueOperationIdRule();
    }

    public function test_distinct_operation_ids_in_paths_and_webhooks_are_valid(): void
    {
        self::assertSame([], self::check('unique-operation-id-valid', Version::V3_1_2));
    }

    public function test_each_reuse_of_an_operation_id_is_reported(): void
    {
        self::assertSame([
            ['/paths/~1pets/get/operationId', Severity::Error],
            ['/webhooks/petCreated/post/operationId', Severity::Error],
        ], self::check('unique-operation-id-invalid', Version::V3_1_2));
    }
}
