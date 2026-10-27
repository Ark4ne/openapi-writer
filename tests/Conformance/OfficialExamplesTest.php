<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Tests\Conformance;

use Ark4ne\OpenApiWriter\Enums\Version;
use Ark4ne\OpenApiWriter\Objects\OpenApi;
use Ark4ne\OpenApiWriter\Tests\Support\Documents;
use Ark4ne\OpenApiWriter\Tests\Support\Fixtures;
use Ark4ne\OpenApiWriter\Tests\TestCase;
use Closure;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * The official examples of the OpenAPI Initiative, rebuilt with the API, written byte for byte as the expected
 * files, for each line that can express them.
 */
final class OfficialExamplesTest extends TestCase
{
    /**
     * Keyed by example: the document, and the versions that can write it.
     *
     * @return array<string, array{Closure(): OpenApi, list<Version>}>
     */
    public static function examples(): array
    {
        $all = [Version::V3_0_4, Version::V3_1_2, Version::V3_2_0];
        $since31 = [Version::V3_1_2, Version::V3_2_0];

        return [
            'petstore' => [Documents::petstore(...), $all],
            'petstore-expanded' => [Documents::petstoreExpanded(...), $all],
            'callback-example' => [Documents::callbackExample(...), $all],
            'link-example' => [Documents::linkExample(...), $all],
            'api-with-examples' => [Documents::apiWithExamples(...), $all],
            'uspto' => [Documents::uspto(...), $all],
            'tictactoe' => [Documents::tictactoe(...), $all],
            'webhook-example' => [Documents::webhookExample(...), $since31],
            'non-oauth-scopes' => [Documents::nonOauthScopes(...), $since31],
            '3.2-query-example' => [Documents::queryExample(...), [Version::V3_2_0]],
            '3.2-tags-example' => [Documents::tagsExample(...), [Version::V3_2_0]],
        ];
    }

    /**
     * @return iterable<string, array{string, Version}>
     */
    public static function cases(): iterable
    {
        foreach (self::examples() as $example => [, $versions]) {
            foreach ($versions as $version) {
                yield $example . ' in ' . Fixtures::line($version) => [$example, $version];
            }
        }
    }

    #[DataProvider('cases')]
    public function test_each_official_example_is_written_byte_for_byte(string $example, Version $version): void
    {
        $document = self::examples()[$example][0]();

        self::assertSerializesTo($document, $version, 'conformance/' . $example);
    }

    public function test_the_webhook_example_cannot_be_written_in_3_0(): void
    {
        self::assertUnsupported(['/webhooks'], static fn(): string => Documents::webhookExample()->toJson(Version::V3_0_4));
    }

    public function test_the_non_oauth_scopes_example_cannot_be_written_in_3_0(): void
    {
        self::assertUnsupported(['/paths/~1users/get/security/0/bearerAuth'], static fn(): string => Documents::nonOauthScopes()->toJson(Version::V3_0_4));
    }

    public function test_the_3_2_examples_cannot_be_written_in_3_1(): void
    {
        self::assertUnsupported(['/paths/~1flights~1search/query'], static fn(): string => Documents::queryExample()->toJson(Version::V3_1_2));
        self::assertUnsupported(
            ['/tags/0/summary', '/tags/0/kind', '/tags/1/summary', '/tags/1/parent', '/tags/1/kind', '/tags/2/summary', '/tags/2/parent', '/tags/2/kind', '/tags/3/summary', '/tags/3/kind'],
            static fn(): string => Documents::tagsExample()->toJson(Version::V3_1_2),
        );
    }
}
