<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Tests\Support;

use Ark4ne\OpenApiWriter\Enums\Version;
use Ark4ne\OpenApiWriter\Objects;
use JsonException;
use RuntimeException;
use stdClass;

/**
 * Places the JSON of one object inside a minimal document, at a place where the official schema checks it, so that
 * the output of a single object can be validated like a whole document.
 */
final class Envelope
{
    /**
     * The fragment is decoded to objects, so that an empty JSON object stays `{}` once wrapped.
     *
     * @param class-string<Objects\SpecObject> $class
     */
    public static function wrap(string $json, string $class, Version $version): string
    {
        try {
            $fragment = json_decode($json, false, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return $json;
        }

        $document = [
            'openapi' => $version->value,
            'info' => ['title' => 'Envelope', 'version' => '1.0.0'],
            'paths' => new stdClass(),
        ];

        $document = match ($class) {
            Objects\OpenApi::class => $fragment,
            Objects\Info::class => ['info' => $fragment] + $document,
            Objects\Contact::class => ['info' => ['title' => 'Envelope', 'version' => '1.0.0', 'contact' => $fragment]] + $document,
            Objects\License::class => ['info' => ['title' => 'Envelope', 'version' => '1.0.0', 'license' => $fragment]] + $document,
            Objects\Server::class => $document + ['servers' => [$fragment]],
            Objects\ServerVariable::class => $document + ['servers' => [['url' => 'https://{variable}.example.com', 'variables' => ['variable' => $fragment]]]],
            Objects\Components::class => $document + ['components' => $fragment],
            Objects\PathItem::class => ['paths' => ['/envelope' => $fragment]] + $document,
            Objects\Operation::class => ['paths' => ['/envelope' => ['get' => $fragment]]] + $document,
            Objects\Responses::class => ['paths' => ['/envelope' => ['get' => ['responses' => $fragment]]]] + $document,
            Objects\ExternalDocumentation::class => $document + ['externalDocs' => $fragment],
            Objects\Tag::class => $document + ['tags' => [$fragment]],
            Objects\SecurityRequirement::class => $document + ['security' => [$fragment]],
            Objects\Encoding::class => $document + ['components' => ['requestBodies' => ['envelope' => ['content' => ['multipart/form-data' => ['encoding' => ['envelope' => $fragment]]]]]]],
            Objects\MediaType::class => $document + ['components' => ['requestBodies' => ['envelope' => ['content' => ['application/json' => $fragment]]]]],
            Objects\Discriminator::class => $document + ['components' => ['schemas' => ['envelope' => ['discriminator' => $fragment]]]],
            Objects\Xml::class => $document + ['components' => ['schemas' => ['envelope' => ['xml' => $fragment]]]],
            Objects\OAuthFlows::class => $document + ['components' => ['securitySchemes' => ['envelope' => ['type' => 'oauth2', 'flows' => $fragment]]]],
            Objects\OAuthFlow::class => $document + ['components' => ['securitySchemes' => ['envelope' => ['type' => 'oauth2', 'flows' => [self::flow($fragment) => $fragment]]]]],
            default => $document + ['components' => [self::section($class) => ['envelope' => $fragment]]],
        };

        return json_encode($document, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION);
    }

    /**
     * @param class-string<Objects\SpecObject> $class
     */
    private static function section(string $class): string
    {
        return match ($class) {
            Objects\Schema::class => 'schemas',
            Objects\Response::class, Objects\Reference::class => 'responses',
            Objects\Parameter::class => 'parameters',
            Objects\Example::class => 'examples',
            Objects\RequestBody::class => 'requestBodies',
            Objects\Header::class => 'headers',
            Objects\SecurityScheme::class => 'securitySchemes',
            Objects\Link::class => 'links',
            Objects\Callback::class => 'callbacks',
            default => throw new RuntimeException(sprintf('No envelope for %s.', $class)),
        };
    }

    /**
     * The OAuth flow whose required fields match the fragment.
     */
    private static function flow(mixed $fragment): string
    {
        $fields = is_object($fragment) ? get_object_vars($fragment) : [];

        return match (true) {
            isset($fields['deviceAuthorizationUrl']) => 'deviceAuthorization',
            isset($fields['authorizationUrl'], $fields['tokenUrl']) => 'authorizationCode',
            isset($fields['authorizationUrl']) => 'implicit',
            default => 'clientCredentials',
        };
    }
}
