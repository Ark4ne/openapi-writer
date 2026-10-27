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
}
