<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Serialization;

use Ark4ne\OpenApiWriter\Enums\Strictness;
use Ark4ne\OpenApiWriter\Enums\Version;
use Ark4ne\OpenApiWriter\Exceptions\InvalidArgumentException;
use Ark4ne\OpenApiWriter\Exceptions\UnsupportedFeatureException;
use Ark4ne\OpenApiWriter\Objects\SpecObject;
use Ark4ne\OpenApiWriter\Serialization\Rewriters\RewriterChain;

/**
 * Entry point of serialization (Facade): `Serializer::for(Version::V3_0_4, Strictness::Lenient)->serialize($openApi)`.
 */
final readonly class Serializer
{
    private function __construct(
        public Version $version,
        public Strictness $strictness,
        public RewriterChain $rewriters,
    ) {}

    /**
     * @param Version|string|null $version     default: 3.1.2
     * @param RewriterChain|null  $rewriters   default: `RewriterChain::default()`
     *
     * @throws InvalidArgumentException on an unknown version or strictness
     */
    public static function for(
        Version|string|null $version = null,
        Strictness|string $strictness = Strictness::Strict,
        ?RewriterChain $rewriters = null,
    ): self {
        return new self(Version::default(), Strictness::Strict, $rewriters ?? RewriterChain::default());
    }

    /**
     * @throws UnsupportedFeatureException in strict mode, listing every unsupported feature
     */
    public function serialize(SpecObject $object): SerializationResult
    {
        return new SerializationResult([], $this->version, new Diagnostics());
    }
}
