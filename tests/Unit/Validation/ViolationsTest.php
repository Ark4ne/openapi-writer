<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Tests\Unit\Validation;

use Ark4ne\OpenApiWriter\Support\JsonPointer;
use Ark4ne\OpenApiWriter\Tests\TestCase;
use Ark4ne\OpenApiWriter\Validation\Rules\UniqueOperationIdRule;
use Ark4ne\OpenApiWriter\Validation\Severity;
use Ark4ne\OpenApiWriter\Validation\Violation;
use Ark4ne\OpenApiWriter\Validation\Violations;

final class ViolationsTest extends TestCase
{
    public function test_a_new_collection_is_valid_and_empty(): void
    {
        $violations = new Violations();

        self::assertTrue($violations->isValid());
        self::assertCount(0, $violations);
        self::assertSame([], $violations->all());
    }

    public function test_it_keeps_every_violation_in_order_and_splits_them_by_severity(): void
    {
        $error = new Violation(JsonPointer::fromString('/a'), 'rule', 'a');
        $warning = new Violation(JsonPointer::fromString('/b'), 'rule', 'b', Severity::Warning);
        $otherError = new Violation(JsonPointer::fromString('/c'), 'rule', 'c');
        $violations = new Violations();

        $violations->add($error);
        $violations->add($warning);
        $violations->add($otherError);

        self::assertCount(3, $violations);
        self::assertSame([$error, $warning, $otherError], $violations->all());
        self::assertSame([$error, $warning, $otherError], iterator_to_array($violations));
        self::assertSame([$error, $otherError], $violations->errors());
        self::assertSame([$warning], $violations->warnings());
        self::assertFalse($violations->isValid());
    }

    public function test_warnings_alone_keep_the_collection_valid(): void
    {
        $violations = new Violations();

        $violations->add(new Violation(JsonPointer::root(), 'rule', 'no operation', Severity::Warning));

        self::assertTrue($violations->isValid());
        self::assertCount(1, $violations);
    }

    public function test_a_violation_reads_as_severity_pointer_message_and_short_rule_name(): void
    {
        $builtIn = new Violation(JsonPointer::fromString('/paths/~1pets/get/operationId'), UniqueOperationIdRule::class, 'operationId "list" is already used');
        $custom = new Violation(JsonPointer::fromString('/info'), 'summary-required', 'Info has no summary', Severity::Warning);

        self::assertSame('[error] /paths/~1pets/get/operationId: operationId "list" is already used (UniqueOperationIdRule)', (string) $builtIn);
        self::assertSame('[warning] /info: Info has no summary (summary-required)', (string) $custom);
    }
}
