<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Tests\Unit;

use Ark4ne\OpenApiWriter\Enums\Version;
use Ark4ne\OpenApiWriter\Objects\OpenApi;
use Ark4ne\OpenApiWriter\Tests\TestCase;
use Ark4ne\OpenApiWriter\Validation\Violations;
use PHPUnit\Framework\Attributes\CoversNothing;
use RuntimeException;

/**
 * The PHP examples of the README run, in order, as one script: their `use` statements are gathered at the top.
 */
#[CoversNothing]
final class ReadmeTest extends TestCase
{
    private const README = __DIR__ . '/../../README.md';

    public function test_the_quick_start_writes_the_expected_document(): void
    {
        $openApi = self::examples()['openApi'] ?? null;

        self::assertInstanceOf(OpenApi::class, $openApi);
        self::assertSerializesTo($openApi, Version::V3_1_2, 'readme/quick-start');
    }

    public function test_the_document_built_by_the_examples_is_valid_in_every_line(): void
    {
        $variables = self::examples();
        $document = $variables['document'] ?? null;
        $violations = $variables['violations'] ?? null;

        self::assertInstanceOf(OpenApi::class, $document);
        self::assertInstanceOf(Violations::class, $violations);
        self::assertSame([], array_map('strval', $violations->all()));

        foreach ([Version::V3_0_4, Version::V3_1_2, Version::V3_2_0] as $version) {
            self::assertValidOpenApi($document->toJson($version), $version);
        }
    }

    public function test_the_lenient_mode_example_reports_the_dropped_webhooks_and_the_conversions(): void
    {
        $variables = self::examples();

        self::assertSame(['[unsupported] /webhooks: webhooks needs 3.1.0'], $variables['reasons'] ?? null);
        self::assertSame(
            [
                '[dropped] /webhooks: webhooks needs 3.1.0',
                '[converted] /components/schemas/Pet/properties/birthday: nullable written as nullable: true',
            ],
            $variables['diagnostics'] ?? null,
        );
    }

    /**
     * Runs every `php` block of the README and returns the variables it defines.
     *
     * @return array<string, mixed>
     */
    private static function examples(): array
    {
        $readme = (string) file_get_contents(self::README);

        if (preg_match_all('/^```php\n(.*?)^```$/ms', $readme, $blocks) === 0) {
            throw new RuntimeException('The README has no PHP example.');
        }

        $imports = [];
        $code = '';

        foreach ($blocks[1] as $block) {
            foreach (explode("\n", $block) as $line) {
                if (str_starts_with($line, 'use ')) {
                    $imports[$line] = true;
                } else {
                    $code .= $line . "\n";
                }
            }
        }

        return (static function (string $script): array {
            eval($script);

            return get_defined_vars();
        })(implode("\n", array_keys($imports)) . "\n" . $code);
    }
}
