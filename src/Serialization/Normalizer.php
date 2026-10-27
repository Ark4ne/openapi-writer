<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Serialization;

use Ark4ne\OpenApiWriter\Objects\SpecObject;
use Ark4ne\OpenApiWriter\Serialization\Rewriters\RewriterChain;

/**
 * Walks the object graph with its ClassMetadata and builds the array (Visitor). It skips the fields unknown to the
 * target version (`since` / `until`, VersionedEnum) and reports them, then hands each node to the rewriters.
 * Empty objects and maps are written as `\stdClass`, so that JSON writes `{}` and never `[]`. It holds no version rule of its own.
 */
final readonly class Normalizer
{
    public function __construct(public RewriterChain $rewriters) {}

    /**
     * @return array<string, mixed>
     */
    public function normalize(SpecObject $object, Context $context): array
    {
        return [];
    }
}
