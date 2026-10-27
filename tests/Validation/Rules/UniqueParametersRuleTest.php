<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Tests\Validation\Rules;

use Ark4ne\OpenApiWriter\Enums\Version;
use Ark4ne\OpenApiWriter\Tests\Validation\RuleTestCase;
use Ark4ne\OpenApiWriter\Validation\Rule;
use Ark4ne\OpenApiWriter\Validation\Rules\UniqueParametersRule;
use Ark4ne\OpenApiWriter\Validation\Severity;

final class UniqueParametersRuleTest extends RuleTestCase
{
    protected static function rule(): Rule
    {
        return new UniqueParametersRule();
    }

    public function test_same_name_in_another_location_and_operation_overrides_are_valid(): void
    {
        self::assertSame([], self::check('unique-parameters-valid', Version::V3_1_2));
    }

    public function test_a_second_parameter_with_the_same_name_and_location_is_reported(): void
    {
        self::assertSame([
            ['/paths/~1users/get/parameters/2', Severity::Error],
            ['/paths/~1users/parameters/1', Severity::Error],
        ], self::check('unique-parameters-invalid', Version::V3_1_2));
    }

    /**
     * A reference that does not resolve is left to LocalReferencesRule.
     */
    public function test_references_to_parameters_are_followed(): void
    {
        self::assertSame([['/paths/~1users/get/parameters/1', Severity::Error]], self::check('unique-parameters-references', Version::V3_1_2));
    }
}
