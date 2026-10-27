<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Tests\Validation\Rules;

use Ark4ne\OpenApiWriter\Enums\Version;
use Ark4ne\OpenApiWriter\Tests\Validation\RuleTestCase;
use Ark4ne\OpenApiWriter\Validation\Rule;
use Ark4ne\OpenApiWriter\Validation\Rules\TagParentsRule;
use Ark4ne\OpenApiWriter\Validation\Severity;

final class TagParentsRuleTest extends RuleTestCase
{
    protected static function rule(): Rule
    {
        return new TagParentsRule();
    }

    public function test_parents_that_exist_without_cycle_are_valid(): void
    {
        self::assertSame([], self::check('tag-parents-valid', Version::V3_2_0));
    }

    public function test_an_unknown_parent_is_reported(): void
    {
        self::assertSame([['/tags/0/parent', Severity::Error]], self::check('tag-parents-unknown', Version::V3_2_0));
    }

    public function test_every_tag_of_a_cycle_is_reported(): void
    {
        self::assertSame([
            ['/tags/0/parent', Severity::Error],
            ['/tags/1/parent', Severity::Error],
            ['/tags/2/parent', Severity::Error],
        ], self::check('tag-parents-cycle', Version::V3_2_0));
    }
}
