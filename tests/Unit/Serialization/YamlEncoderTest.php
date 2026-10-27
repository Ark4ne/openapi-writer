<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Tests\Unit\Serialization;

use Ark4ne\OpenApiWriter\Enums\Version;
use Ark4ne\OpenApiWriter\Serialization\Encoders\SymfonyYamlEncoder;
use Ark4ne\OpenApiWriter\Tests\Support\Documents;
use Ark4ne\OpenApiWriter\Tests\Support\Fixtures;
use Ark4ne\OpenApiWriter\Tests\TestCase;
use stdClass;

/**
 * YAML needs `symfony/yaml`, a suggested dependency installed for development. Without it `toYaml()` throws a
 * MissingDependencyException; that case cannot run in the same process as the others and is covered by review.
 */
final class YamlEncoderTest extends TestCase
{
    public function test_it_writes_empty_maps_as_empty_mappings_and_empty_lists_as_empty_sequences(): void
    {
        $yaml = (new SymfonyYamlEncoder())->encode(['paths' => new stdClass(), 'tags' => [], 'security' => [new stdClass()]]);

        self::assertSame((string) file_get_contents(Fixtures::DIRECTORY . 'encoders/empty-values.yaml'), $yaml);
    }

    public function test_to_yaml_writes_the_same_document_as_to_json(): void
    {
        $yaml = Documents::petstore()->toYaml(Version::V3_0_4);

        self::assertSame((string) file_get_contents(Fixtures::DIRECTORY . '3.0/conformance/petstore.yaml'), $yaml);
    }
}
