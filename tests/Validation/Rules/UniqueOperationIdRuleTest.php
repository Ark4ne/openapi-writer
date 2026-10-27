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

    /**
     * Operations of callbacks, additional operations and components count; extensions of `paths` do not.
     */
    public function test_operations_of_callbacks_additional_operations_and_components_count(): void
    {
        self::assertSame([
            ['/paths/~1users/get/callbacks/onEvent/{$request.body#~1url}/post/operationId', Severity::Error],
            ['/paths/~1users/additionalOperations/COPY/operationId', Severity::Error],
            ['/components/callbacks/Hook/{$request.body#~1hook}/post/operationId', Severity::Error],
            ['/components/pathItems/Ping/get/operationId', Severity::Error],
        ], self::check('unique-operation-id-nested', Version::V3_2_0));
    }
}
