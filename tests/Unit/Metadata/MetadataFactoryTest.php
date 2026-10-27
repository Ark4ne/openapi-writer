<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Tests\Unit\Metadata;

use Ark4ne\OpenApiWriter\Enums\Version;
use Ark4ne\OpenApiWriter\Metadata\MetadataFactory;
use Ark4ne\OpenApiWriter\Objects\Components;
use Ark4ne\OpenApiWriter\Objects\Info;
use Ark4ne\OpenApiWriter\Objects\OAuthFlow;
use Ark4ne\OpenApiWriter\Objects\OpenApi;
use Ark4ne\OpenApiWriter\Objects\Responses;
use Ark4ne\OpenApiWriter\Objects\Schema;
use Ark4ne\OpenApiWriter\Tests\TestCase;

final class MetadataFactoryTest extends TestCase
{
    public function test_it_lists_the_fields_in_declaration_order(): void
    {
        $metadata = MetadataFactory::for(Info::class);

        self::assertSame(Info::class, $metadata->class);
        self::assertSame(['title', 'summary', 'description', 'termsOfService', 'contact', 'license', 'version'], array_keys($metadata->fields));
    }

    public function test_it_reads_the_arguments_of_each_field(): void
    {
        $schema = MetadataFactory::for(Schema::class);
        $components = MetadataFactory::for(Components::class);
        $responses = MetadataFactory::for(Responses::class);
        $flow = MetadataFactory::for(OAuthFlow::class);

        $defs = $schema->field('defs');
        self::assertNotNull($defs);
        self::assertSame(Version::V3_1_0, $defs->since);
        self::assertTrue($defs->map);
        self::assertFalse($schema->field('nullable')?->emit);
        self::assertSame(Version::V3_2_0, $components->field('mediaTypes')?->since);
        self::assertTrue($responses->field('responses')?->patterned);
        self::assertFalse($flow->field('scopes')?->keyedByObjectId);
    }

    public function test_it_knows_no_field_for_an_undeclared_property(): void
    {
        self::assertNull(MetadataFactory::for(Info::class)->field('objectId'));
        self::assertNull(MetadataFactory::for(Info::class)->field('extensions'));
    }

    public function test_output_name_is_the_declared_name_or_the_property_name(): void
    {
        $schema = MetadataFactory::for(Schema::class);
        $openApi = MetadataFactory::for(OpenApi::class);

        self::assertSame('$ref', $schema->outputName('ref'));
        self::assertSame('$defs', $schema->outputName('defs'));
        self::assertSame('title', $schema->outputName('title'));
        self::assertSame('$self', $openApi->outputName('self'));
    }

    public function test_it_returns_the_cached_metadata_for_a_class_already_read(): void
    {
        self::assertSame(MetadataFactory::for(Schema::class), MetadataFactory::for(Schema::class));
    }
}
