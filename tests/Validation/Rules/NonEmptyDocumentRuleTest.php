<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Tests\Validation\Rules;

use Ark4ne\OpenApiWriter\Enums\Version;
use Ark4ne\OpenApiWriter\Tests\Validation\RuleTestCase;
use Ark4ne\OpenApiWriter\Validation\Rule;
use Ark4ne\OpenApiWriter\Validation\Rules\NonEmptyDocumentRule;
use Ark4ne\OpenApiWriter\Validation\Severity;
use PHPUnit\Framework\Attributes\DataProvider;

final class NonEmptyDocumentRuleTest extends RuleTestCase
{
    /**
     * @return iterable<string, array{Version}>
     */
    public static function lines(): iterable
    {
        yield '3.0' => [Version::V3_0_4];
        yield '3.1' => [Version::V3_1_2];
        yield '3.2' => [Version::V3_2_0];
    }

    protected static function rule(): Rule
    {
        return new NonEmptyDocumentRule();
    }

    /**
     * 3.0: an operation in `paths`; 3.1: only a webhook; 3.2: only an additional operation.
     */
    #[DataProvider('lines')]
    public function test_a_document_with_one_operation_in_paths_or_webhooks_is_valid(Version $version): void
    {
        self::assertSame([], self::check('non-empty-document-valid', $version));
    }

    /**
     * 3.0: a path item with only a summary and parameters; 3.1: path items without method next to an operation
     * in `components.pathItems`; 3.2: components only.
     */
    #[DataProvider('lines')]
    public function test_a_document_without_any_operation_is_a_warning_at_the_root(Version $version): void
    {
        self::assertSame([['', Severity::Warning]], self::check('non-empty-document-invalid', $version));
    }

    public function test_a_path_item_reference_to_operations_counts(): void
    {
        self::assertSame([], self::check('non-empty-document-reference', Version::V3_1_2));
    }

    /**
     * An external path item is not read: it may declare operations.
     */
    public function test_a_path_item_reference_to_another_document_counts(): void
    {
        self::assertSame([], self::check('non-empty-document-external', Version::V3_1_2));
    }
}
