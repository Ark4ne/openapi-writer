<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Objects;

use Ark4ne\OpenApiWriter\Contracts\HeaderContract;
use Ark4ne\OpenApiWriter\Contracts\LinkContract;
use Ark4ne\OpenApiWriter\Contracts\MediaTypeContract;
use Ark4ne\OpenApiWriter\Contracts\ResponseContract;
use Ark4ne\OpenApiWriter\Enums\Version;
use Ark4ne\OpenApiWriter\Exceptions\DuplicateObjectIdException;
use Ark4ne\OpenApiWriter\Exceptions\InvalidArgumentException;
use Ark4ne\OpenApiWriter\Exceptions\MissingObjectIdException;
use Ark4ne\OpenApiWriter\Metadata\Field;
use Ark4ne\OpenApiWriter\Support\Undefined;

/**
 * One response of an operation; its objectId is the status code. The named constructors set the reason phrase as description.
 *
 * @see https://spec.openapis.org/oas/v3.2.0.html#response-object
 *
 * @property-read string|null $summary
 * @property-read string|null $description
 * @property-read array<string, HeaderContract>|null $headers
 * @property-read array<string, MediaTypeContract>|null $content
 * @property-read array<string, LinkContract>|null $links
 */
final class Response extends ExtensibleSpecObject implements ResponseContract
{
    #[Field(since: Version::V3_2_0)] protected string|Undefined $summary = Undefined::Value;
    #[Field] protected string|Undefined $description = Undefined::Value;
    /** @var array<string, HeaderContract>|Undefined */
    #[Field(map: true)] protected array|Undefined $headers = Undefined::Value;
    /** @var array<string, MediaTypeContract>|Undefined */
    #[Field(map: true)] protected array|Undefined $content = Undefined::Value;
    /** @var array<string, LinkContract>|Undefined */
    #[Field(map: true)] protected array|Undefined $links = Undefined::Value;

    public static function ok(): static
    {
        return static::create('200')->description('OK');
    }

    public static function created(): static
    {
        return static::create('201')->description('Created');
    }

    public static function accepted(): static
    {
        return static::create('202')->description('Accepted');
    }

    public static function noContent(): static
    {
        return static::create('204')->description('No Content');
    }

    public static function movedPermanently(): static
    {
        return static::create('301')->description('Moved Permanently');
    }

    public static function found(): static
    {
        return static::create('302')->description('Found');
    }

    public static function notModified(): static
    {
        return static::create('304')->description('Not Modified');
    }

    public static function badRequest(): static
    {
        return static::create('400')->description('Bad Request');
    }

    public static function unauthorized(): static
    {
        return static::create('401')->description('Unauthorized');
    }

    public static function forbidden(): static
    {
        return static::create('403')->description('Forbidden');
    }

    public static function notFound(): static
    {
        return static::create('404')->description('Not Found');
    }

    public static function methodNotAllowed(): static
    {
        return static::create('405')->description('Method Not Allowed');
    }

    public static function conflict(): static
    {
        return static::create('409')->description('Conflict');
    }

    public static function unprocessableEntity(): static
    {
        return static::create('422')->description('Unprocessable Entity');
    }

    public static function tooManyRequests(): static
    {
        return static::create('429')->description('Too Many Requests');
    }

    public static function internalServerError(): static
    {
        return static::create('500')->description('Internal Server Error');
    }

    public static function serviceUnavailable(): static
    {
        return static::create('503')->description('Service Unavailable');
    }

    /**
     * Response for any status code (`418`) or range (`'4XX'`), without description.
     *
     * @throws InvalidArgumentException on a malformed status code
     */
    public static function status(int|string $status): static
    {
        return static::create((string) $status);
    }

    /**
     * Reference to `#/components/responses/<name>`. The path is built here, since a Reference does not know its
     * section; a value containing `#` or `/` is written as is.
     *
     * @param string|null $objectId key of the reference in its parent
     */
    public static function ref(string $ref, ?string $objectId = null): Reference
    {
        return Reference::create($objectId);
    }

    public function summary(?string $summary): static
    {
        return $this->with('summary', $summary);
    }

    /**
     * Required before 3.2: written `""` when missing.
     */
    public function description(?string $description): static
    {
        return $this->with('description', $description);
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
     * Keyed by the objectId of each entry.
     *
     * @throws MissingObjectIdException
     * @throws DuplicateObjectIdException
     */
    public function content(MediaTypeContract ...$content): static
    {
        return $this->with('content', $content);
    }

    /**
     * Appends entries keyed by their objectId; an objectId already present throws.
     *
     * @throws MissingObjectIdException
     * @throws DuplicateObjectIdException
     */
    public function addContent(MediaTypeContract ...$content): static
    {
        return $this->withAdded('content', $content);
    }

    /**
     * Keyed by the objectId of each entry.
     *
     * @throws MissingObjectIdException
     * @throws DuplicateObjectIdException
     */
    public function links(LinkContract ...$links): static
    {
        return $this->with('links', $links);
    }

    /**
     * Appends entries keyed by their objectId; an objectId already present throws.
     *
     * @throws MissingObjectIdException
     * @throws DuplicateObjectIdException
     */
    public function addLinks(LinkContract ...$links): static
    {
        return $this->withAdded('links', $links);
    }
}
