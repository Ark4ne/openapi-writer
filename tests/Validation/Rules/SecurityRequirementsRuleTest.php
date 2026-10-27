<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Tests\Validation\Rules;

use Ark4ne\OpenApiWriter\Enums\Version;
use Ark4ne\OpenApiWriter\Tests\Validation\RuleTestCase;
use Ark4ne\OpenApiWriter\Validation\Rule;
use Ark4ne\OpenApiWriter\Validation\Rules\SecurityRequirementsRule;
use Ark4ne\OpenApiWriter\Validation\Severity;

final class SecurityRequirementsRuleTest extends RuleTestCase
{
    protected static function rule(): Rule
    {
        return new SecurityRequirementsRule();
    }

    public function test_declared_schemes_with_declared_scopes_are_valid(): void
    {
        self::assertSame([], self::check('security-requirements-valid', Version::V3_1_2));
    }

    public function test_an_unknown_scheme_and_an_unknown_oauth2_scope_are_reported(): void
    {
        self::assertSame([
            ['/paths/~1users/get/security/0/OAuth2/1', Severity::Error],
            ['/security/0/Missing', Severity::Error],
        ], self::check('security-requirements-invalid', Version::V3_1_2));
    }

    /**
     * From 3.2 a requirement may name a scheme by URI: a local one must resolve, an external one is not checked.
     * Scopes are checked through a reference to an OAuth2 scheme, and never for OpenID Connect. A name without `/` nor
     * `#` (`oauth:main`) is a component name, even with a `:`.
     */
    public function test_uris_references_and_openid_connect_in_3_2(): void
    {
        self::assertSame([
            ['/security/1/#~1components~1securitySchemes~1Gone', Severity::Error],
            ['/security/3/Shared/0', Severity::Error],
            ['/security/5/oauth:main', Severity::Error],
        ], self::check('security-requirements-uris', Version::V3_2_0));
    }
}
