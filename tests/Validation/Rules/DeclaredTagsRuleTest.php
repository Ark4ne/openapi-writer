<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Tests\Validation\Rules;

use Ark4ne\OpenApiWriter\Enums\Version;
use Ark4ne\OpenApiWriter\Tests\Validation\RuleTestCase;
use Ark4ne\OpenApiWriter\Validation\Rule;
use Ark4ne\OpenApiWriter\Validation\Rules\DeclaredTagsRule;
use Ark4ne\OpenApiWriter\Validation\Severity;

final class DeclaredTagsRuleTest extends RuleTestCase
{
    protected static function rule(): Rule
    {
        return new DeclaredTagsRule();
    }

    public function test_tags_declared_at_the_root_are_valid(): void
    {
        self::assertSame([], self::check('declared-tags-valid', Version::V3_1_2));
    }

    public function test_an_undeclared_tag_is_a_warning(): void
    {
        self::assertSame([['/paths/~1users/get/tags/1', Severity::Warning]], self::check('declared-tags-invalid', Version::V3_1_2));
    }
}
