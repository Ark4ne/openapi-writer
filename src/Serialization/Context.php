<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Serialization;

use Ark4ne\OpenApiWriter\Enums\Strictness;
use Ark4ne\OpenApiWriter\Enums\VendorExtensions;
use Ark4ne\OpenApiWriter\Enums\Version;
use Ark4ne\OpenApiWriter\Objects\SpecObject;
use Ark4ne\OpenApiWriter\Support\JsonPointer;

/**
 * State of a serialization at one node: target version, mode, vendor extensions, location, the shared diagnostics
 * and the object being serialized as a whole (`$document`, null outside a serialization).
 */
final readonly class Context
{
    public function __construct(
        public Version $version,
        public Strictness $strictness,
        public JsonPointer $pointer,
        public Diagnostics $diagnostics,
        public VendorExtensions $extensions = VendorExtensions::None,
        public ?SpecObject $document = null,
    ) {}

    public static function root(
        Version $version,
        Strictness $strictness,
        VendorExtensions $extensions = VendorExtensions::None,
        ?SpecObject $document = null,
    ): self {
        return new self($version, $strictness, JsonPointer::root(), new Diagnostics(), $extensions, $document);
    }

    /**
     * Context of a child node, sharing the same diagnostics.
     */
    public function at(string|int ...$tokens): self
    {
        return new self($this->version, $this->strictness, $this->pointer->append(...$tokens), $this->diagnostics, $this->extensions, $this->document);
    }

    /**
     * Context of a child node with diagnostics of its own, kept apart until the node is known to be written.
     */
    public function detached(string|int ...$tokens): self
    {
        return new self($this->version, $this->strictness, $this->pointer->append(...$tokens), new Diagnostics(), $this->extensions, $this->document);
    }

    /**
     * Reports a conversion at the current pointer.
     */
    public function converted(string $message): void
    {
        $this->diagnostics->add(new Diagnostic(DiagnosticLevel::Converted, $this->pointer, $message));
    }

    /**
     * Reports a feature the target version cannot express, at the current pointer.
     */
    public function unsupported(string $message): void
    {
        $this->diagnostics->add(new Diagnostic(DiagnosticLevel::Unsupported, $this->pointer, $message));
    }
}
