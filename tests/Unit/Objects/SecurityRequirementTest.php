<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Tests\Unit\Objects;

use Ark4ne\OpenApiWriter\Objects\SecurityRequirement;

final class SecurityRequirementTest extends SpecObjectTestCase
{
    protected static function objectClass(): string
    {
        return SecurityRequirement::class;
    }

    public static function fields(): array
    {
        $schemes = ['petstore_auth' => ['write:pets', 'read:pets'], 'api_key' => []];

        return [
            'schemes' => [[$schemes], $schemes],
        ];
    }

    protected static function extensible(): bool
    {
        return false;
    }

    public function test_a_requirement_without_scheme_means_no_authentication(): void
    {
        $requirement = SecurityRequirement::create()->schemes([]);

        self::assertTrue($requirement->has('schemes'));
        self::assertSame([], $requirement->schemes);
    }
}
