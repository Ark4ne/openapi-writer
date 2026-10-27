<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Tests\Validation\Rules;

use Ark4ne\OpenApiWriter\Enums\Version;
use Ark4ne\OpenApiWriter\Tests\Validation\RuleTestCase;
use Ark4ne\OpenApiWriter\Validation\Rule;
use Ark4ne\OpenApiWriter\Validation\Rules\ValueTypeRule;
use Ark4ne\OpenApiWriter\Validation\Severity;

final class ValueTypeRuleTest extends RuleTestCase
{
    protected static function rule(): Rule
    {
        return new ValueTypeRule();
    }

    public function test_values_of_the_declared_type_are_valid(): void
    {
        self::assertSame([], self::check('value-type-valid', Version::V3_1_2));
    }

    public function test_a_default_or_an_example_of_another_type_is_a_warning(): void
    {
        self::assertSame([
            ['/components/schemas/Count/default', Severity::Warning],
            ['/components/schemas/Name/example', Severity::Warning],
            ['/components/schemas/Ratio/example', Severity::Warning],
        ], self::check('value-type-invalid', Version::V3_1_2));
    }

    /**
     * 3.0: `nullable` accepts null, `1.0` is an integer, and schemas of parameters and nested schemas are checked.
     */
    public function test_nested_schemas_and_nullable_in_3_0(): void
    {
        self::assertSame([
            ['/paths/~1users/get/parameters/0/schema/default', Severity::Warning],
            ['/components/schemas/Name/default', Severity::Warning],
            ['/components/schemas/User/properties/age/example', Severity::Warning],
            ['/components/schemas/List/items/example', Severity::Warning],
            ['/components/schemas/Mixed/allOf/0/default', Severity::Warning],
        ], self::check('value-type-nested', Version::V3_0_4));
    }
}
