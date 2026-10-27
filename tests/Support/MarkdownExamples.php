<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Tests\Support;

use RuntimeException;

/**
 * Runs the PHP examples of a Markdown file: its `php` blocks run in order as one script, with their `use` statements
 * gathered at the top, and the variables they define are returned.
 */
final class MarkdownExamples
{
    /**
     * @return array<string, mixed>
     */
    public static function run(string $file): array
    {
        $markdown = (string) file_get_contents($file);

        if (preg_match_all('/^```php\n(.*?)^```$/ms', $markdown, $blocks) === 0) {
            throw new RuntimeException(sprintf('%s has no PHP example.', $file));
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
