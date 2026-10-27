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
        if (is_string($version)) {
            $version = Version::tryFrom($version) ?? throw InvalidArgumentException::forEnumValue(self::class, 'version', Version::class, $version);
        }

        if (is_string($strictness)) {
            $strictness = Strictness::tryFrom($strictness) ?? throw InvalidArgumentException::forEnumValue(self::class, 'strictness', Strictness::class, $strictness);
        }

        return new self($version ?? Version::default(), $strictness, $rewriters ?? RewriterChain::default());
    }

    /**
     * @throws UnsupportedFeatureException in strict mode, listing every unsupported feature
     */
    public function serialize(SpecObject $object): SerializationResult
    {
        $context = Context::root($this->version, $this->strictness);
        $document = (new Normalizer($this->rewriters))->normalize($object, $context);
        $unsupported = $context->diagnostics->ofLevel(DiagnosticLevel::Unsupported);

        if ($unsupported !== [] && $this->strictness === Strictness::Strict) {
            $diagnostics = new Diagnostics();

            foreach ($unsupported as $diagnostic) {
                $diagnostics->add($diagnostic);
            }

            throw UnsupportedFeatureException::fromDiagnostics($diagnostics);
        }

        $diagnostics = new Diagnostics();

        foreach ($context->diagnostics as $diagnostic) {
            $diagnostics->add($diagnostic->level === DiagnosticLevel::Unsupported
                ? new Diagnostic(DiagnosticLevel::Dropped, $diagnostic->pointer, $diagnostic->message)
                : $diagnostic);
        }

        return new SerializationResult($document, $this->version, $diagnostics);
    }
}
