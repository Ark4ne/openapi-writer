<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Tests\Unit\Serialization;

use Ark4ne\OpenApiWriter\Enums\Strictness;
use Ark4ne\OpenApiWriter\Enums\VendorExtensions;
use Ark4ne\OpenApiWriter\Enums\Version;
use Ark4ne\OpenApiWriter\Objects\OpenApi;
use Ark4ne\OpenApiWriter\Serialization\Context;
use Ark4ne\OpenApiWriter\Serialization\DiagnosticLevel;
use Ark4ne\OpenApiWriter\Tests\TestCase;

final class ContextTest extends TestCase
{
    public function test_root_starts_at_the_root_pointer_without_diagnostic(): void
    {
        $context = Context::root(Version::V3_0_4, Strictness::Lenient);

        self::assertSame(Version::V3_0_4, $context->version);
        self::assertSame(Strictness::Lenient, $context->strictness);
        self::assertSame('', $context->pointer->toString());
        self::assertTrue($context->diagnostics->isEmpty());
        self::assertSame(VendorExtensions::None, $context->extensions);
        self::assertNull($context->document);
    }

    public function test_at_moves_the_pointer_and_shares_the_diagnostics(): void
    {
        $root = Context::root(Version::V3_1_2, Strictness::Strict);

        $child = $root->at('paths', '/users', 'get')->at('parameters', 0);

        self::assertSame('/paths/~1users/get/parameters/0', $child->pointer->toString());
        self::assertSame('', $root->pointer->toString());
        self::assertSame($root->diagnostics, $child->diagnostics);
        self::assertSame($root->version, $child->version);
    }

    public function test_a_child_keeps_the_vendor_extensions_and_the_document(): void
    {
        $document = OpenApi::create();
        $root = Context::root(Version::V3_0_4, Strictness::Strict, VendorExtensions::Redoc, $document);

        foreach ([$root->at('tags', 0), $root->detached('webhooks')] as $child) {
            self::assertSame(VendorExtensions::Redoc, $child->extensions);
            self::assertSame($document, $child->document);
            self::assertSame(Strictness::Strict, $child->strictness);
        }
    }

    public function test_detached_moves_the_pointer_with_diagnostics_of_its_own(): void
    {
        $root = Context::root(Version::V3_0_4, Strictness::Strict);

        $detached = $root->detached('webhooks', 'userCreated');
        $detached->at('post')->converted('a conversion');

        self::assertSame('/webhooks/userCreated', $detached->pointer->toString());
        self::assertTrue($root->diagnostics->isEmpty());
        self::assertDiagnostics([[DiagnosticLevel::Converted, '/webhooks/userCreated/post']], $detached->diagnostics);
    }

    public function test_converted_and_unsupported_report_at_the_current_pointer(): void
    {
        $context = Context::root(Version::V3_0_4, Strictness::Strict);

        $context->at('components', 'schemas', 'User')->converted('nullable written as nullable: true');
        $context->at('webhooks')->unsupported('webhooks needs 3.1.0');

        self::assertDiagnostics(
            [[DiagnosticLevel::Converted, '/components/schemas/User'], [DiagnosticLevel::Unsupported, '/webhooks']],
            $context->diagnostics,
        );
        self::assertSame('webhooks needs 3.1.0', $context->diagnostics->all()[1]->message);
    }
}
