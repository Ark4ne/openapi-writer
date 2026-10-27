<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Tests\Validation\Rules;

use Ark4ne\OpenApiWriter\Enums\Version;
use Ark4ne\OpenApiWriter\Tests\Validation\RuleTestCase;
use Ark4ne\OpenApiWriter\Validation\Rule;
use Ark4ne\OpenApiWriter\Validation\Rules\PathParametersRule;
use Ark4ne\OpenApiWriter\Validation\Severity;

final class PathParametersRuleTest extends RuleTestCase
{
    protected static function rule(): Rule
    {
        return new PathParametersRule();
    }

    public function test_variables_declared_on_the_path_item_or_on_each_operation_are_valid(): void
    {
        self::assertSame([], self::check('path-parameters-valid', Version::V3_1_2));
    }

    public function test_an_operation_without_a_path_parameter_for_a_variable_is_reported(): void
    {
        self::assertSame([
            ['/paths/~1users~1{id}/get', Severity::Error],
            ['/paths/~1teams~1{team}/get', Severity::Error],
        ], self::check('path-parameters-invalid', Version::V3_1_2));
    }
}
