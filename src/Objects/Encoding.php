<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Objects;

use Ark4ne\OpenApiWriter\Contracts\HeaderContract;
use Ark4ne\OpenApiWriter\Enums\ParameterStyle;
use Ark4ne\OpenApiWriter\Enums\Version;
use Ark4ne\OpenApiWriter\Exceptions\DuplicateObjectIdException;
use Ark4ne\OpenApiWriter\Exceptions\InvalidArgumentException;
use Ark4ne\OpenApiWriter\Exceptions\MissingObjectIdException;
use Ark4ne\OpenApiWriter\Metadata\Field;
use Ark4ne\OpenApiWriter\Support\Undefined;

/**
 * Encoding of one property of a multipart or form body; its objectId is the property name.
 *
 * @see https://spec.openapis.org/oas/v3.2.0.html#encoding-object
 *
 * @property-read string|null $contentType
 * @property-read array<string, HeaderContract>|null $headers
 * @property-read ParameterStyle|null $style
 * @property-read bool|null $explode
 * @property-read bool|null $allowReserved
 * @property-read array<string, Encoding>|null $encoding
 * @property-read list<Encoding>|null $prefixEncoding
 * @property-read Encoding|null $itemEncoding
 */
final class Encoding extends ExtensibleSpecObject
{
    #[Field] protected string|Undefined $contentType = Undefined::Value;
    /** @var array<string, HeaderContract>|Undefined */
    #[Field(map: true)] protected array|Undefined $headers = Undefined::Value;
    #[Field] protected ParameterStyle|Undefined $style = Undefined::Value;
    #[Field] protected bool|Undefined $explode = Undefined::Value;
    #[Field] protected bool|Undefined $allowReserved = Undefined::Value;
    /** @var array<string, Encoding>|Undefined */
    #[Field(since: Version::V3_2_0, map: true)] protected array|Undefined $encoding = Undefined::Value;
    /** @var list<Encoding>|Undefined */
    #[Field(since: Version::V3_2_0)] protected array|Undefined $prefixEncoding = Undefined::Value;
    #[Field(since: Version::V3_2_0)] protected Encoding|Undefined $itemEncoding = Undefined::Value;

    public function contentType(?string $contentType): static
    {
        return $this->with('contentType', $contentType);
    }

    /**
     * Keyed by the objectId of each entry.
     *
     * @throws MissingObjectIdException
     * @throws DuplicateObjectIdException
     */
    public function headers(HeaderContract ...$headers): static
    {
        return $this->with('headers', $headers);
    }

    /**
     * Appends entries keyed by their objectId; an objectId already present throws.
     *
     * @throws MissingObjectIdException
     * @throws DuplicateObjectIdException
     */
    public function addHeaders(HeaderContract ...$headers): static
    {
        return $this->withAdded('headers', $headers);
    }

    /**
     * @throws InvalidArgumentException when the string is not a value of the enum
     */
    public function style(ParameterStyle|string|null $style): static
    {
        return $this->with('style', $style);
    }

    public function explode(?bool $explode = true): static
    {
        return $this->with('explode', $explode);
    }

    public function allowReserved(?bool $allowReserved = true): static
    {
        return $this->with('allowReserved', $allowReserved);
    }

    /**
     * Keyed by the objectId of each entry.
     *
     * @throws MissingObjectIdException
     * @throws DuplicateObjectIdException
     */
    public function encoding(Encoding ...$encoding): static
    {
        $this->exclusiveOfPositionalEncodings($encoding);

        return $this->with('encoding', $encoding);
    }

    /**
     * Appends entries keyed by their objectId; an objectId already present throws.
     *
     * @throws MissingObjectIdException
     * @throws DuplicateObjectIdException
     */
    public function addEncoding(Encoding ...$encoding): static
    {
        $this->exclusiveOfPositionalEncodings($encoding);

        return $this->withAdded('encoding', $encoding);
    }

    public function prefixEncoding(Encoding ...$prefixEncoding): static
    {
        if ($prefixEncoding !== []) {
            $this->exclusiveOf('prefixEncoding', 'encoding');
        }

        return $this->with('prefixEncoding', $prefixEncoding);
    }

    public function addPrefixEncoding(Encoding ...$prefixEncoding): static
    {
        if ($prefixEncoding !== []) {
            $this->exclusiveOf('prefixEncoding', 'encoding');
        }

        return $this->withAdded('prefixEncoding', $prefixEncoding);
    }

    public function itemEncoding(?Encoding $itemEncoding): static
    {
        if ($itemEncoding !== null) {
            $this->exclusiveOf('itemEncoding', 'encoding');
        }

        return $this->with('itemEncoding', $itemEncoding);
    }

    /**
     * `encoding` names the parts of a form, `prefixEncoding` and `itemEncoding` the items of a sequence: both kinds
     * cannot be set together.
     *
     * @param array<Encoding> $encoding
     */
    private function exclusiveOfPositionalEncodings(array $encoding): void
    {
        if ($encoding !== []) {
            $this->exclusiveOf('encoding', 'prefixEncoding');
            $this->exclusiveOf('encoding', 'itemEncoding');
        }
    }
}
