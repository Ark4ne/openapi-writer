<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Tests\Unit\Serialization;

use Ark4ne\OpenApiWriter\Enums\Strictness;
use Ark4ne\OpenApiWriter\Enums\Version;
use Ark4ne\OpenApiWriter\Objects\Info;
use Ark4ne\OpenApiWriter\Objects\Schema;
use Ark4ne\OpenApiWriter\Serialization\Context;
use Ark4ne\OpenApiWriter\Serialization\Rewriters;
use Ark4ne\OpenApiWriter\Serialization\Rewriters\Rewriter;
use Ark4ne\OpenApiWriter\Serialization\Rewriters\RewriterChain;
use Ark4ne\OpenApiWriter\Tests\Support\EmptyingSchemaRewriter;
use Ark4ne\OpenApiWriter\Tests\Support\RenamingRewriter;
use Ark4ne\OpenApiWriter\Tests\Support\SuffixingRewriter;
use Ark4ne\OpenApiWriter\Tests\TestCase;

final class RewriterChainTest extends TestCase
{
    public function test_the_default_chain_lists_every_rewriter_in_an_explicit_order(): void
    {
        $classes = array_map(static fn(Rewriter $rewriter): string => $rewriter::class, RewriterChain::default()->rewriters);

        self::assertSame([
            Rewriters\OpenApiVersionRewriter::class,
            Rewriters\NullableRewriter::class,
            Rewriters\NullableWithoutTypeRewriter::class,
            Rewriters\NullTypeRewriter::class,
            Rewriters\ExclusiveBoundsRewriter::class,
            Rewriters\ConstRewriter::class,
            Rewriters\SchemaExamplesRewriter::class,
            Rewriters\MultiTypeRewriter::class,
            Rewriters\RefSiblingsRewriter::class,
            Rewriters\ContentMediaTypeRewriter::class,
            Rewriters\ContentEncodingRewriter::class,
            Rewriters\BooleanSchemaRewriter::class,
            Rewriters\ResponseDescriptionRewriter::class,
            Rewriters\EmptyPathsRewriter::class,
            Rewriters\NonOAuthScopesRewriter::class,
            Rewriters\MediaTypeReferenceRewriter::class,
            Rewriters\ReservedExtensionRewriter::class,
        ], $classes);
    }

    public function test_every_rewriter_of_the_library_is_in_the_default_chain(): void
    {
        $files = glob(__DIR__ . '/../../../src/Serialization/Rewriters/*Rewriter.php');
        self::assertIsArray($files);
        $declared = array_map(static fn(string $file): string => 'Ark4ne\\OpenApiWriter\\Serialization\\Rewriters\\' . basename($file, '.php'), $files);
        $declared = array_values(array_diff($declared, [Rewriter::class]));

        $chained = array_map(static fn(Rewriter $rewriter): string => $rewriter::class, RewriterChain::default()->rewriters);
        sort($declared);
        sort($chained);

        self::assertSame($declared, $chained);
    }

    public function test_rewrite_applies_the_supporting_rewriters_in_order(): void
    {
        $chain = new RewriterChain(new RenamingRewriter(), new SuffixingRewriter(), new EmptyingSchemaRewriter());
        $context = Context::root(Version::V3_1_2, Strictness::Strict);

        $node = $chain->rewrite(Info::create(), ['title' => 'Users API', 'version' => '1.0.0'], $context);

        self::assertSame(['title' => 'Renamed!', 'version' => '1.0.0'], $node);
    }

    public function test_rewrite_leaves_a_node_that_no_rewriter_supports_unchanged(): void
    {
        $chain = new RewriterChain(new RenamingRewriter());
        $context = Context::root(Version::V3_1_2, Strictness::Strict);

        self::assertSame(['type' => 'string'], $chain->rewrite(Schema::string(), ['type' => 'string'], $context));
    }
}
