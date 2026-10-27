<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Serialization;

use Ark4ne\OpenApiWriter\Enums\Version;
use Ark4ne\OpenApiWriter\Enums\VersionedEnum;
use Ark4ne\OpenApiWriter\Exceptions\InvalidArgumentException;
use Ark4ne\OpenApiWriter\Metadata\Field;
use Ark4ne\OpenApiWriter\Metadata\MetadataFactory;
use Ark4ne\OpenApiWriter\Objects\ExtensibleSpecObject;
use Ark4ne\OpenApiWriter\Objects\SpecObject;
use Ark4ne\OpenApiWriter\Serialization\Rewriters\RewriterChain;
use Ark4ne\OpenApiWriter\Support\Undefined;
use BackedEnum;
use JsonSerializable;
use stdClass;

/**
 * Walks the object graph with its ClassMetadata and builds the array (Visitor). It skips the fields unknown to the
 * target version (`since` / `until`, VersionedEnum) and reports them, then hands each node to the rewriters.
 * Empty objects and maps are written as `\stdClass`, so that JSON writes `{}` and never `[]`. It holds no version rule of its own.
 *
 * Keys are written in the order of the fields, whatever the rewriters did (`ClassMetadata::rank()`).
 */
final readonly class Normalizer
{
    public function __construct(public RewriterChain $rewriters) {}

    /**
     * @return array<string, mixed>
     *
     * @throws InvalidArgumentException on a free value that cannot be written (`NAN`, `INF`, a resource…)
     */
    public function normalize(SpecObject $object, Context $context): array
    {
        return $this->node($object, $context) ?? [];
    }

    /**
     * @return array<string, mixed>|null null when the object holds an enum case unknown to the version: the whole
     *                                   object is then left out
     */
    private function node(SpecObject $object, Context $context): ?array
    {
        $metadata = MetadataFactory::for($object::class);

        foreach (array_keys($metadata->fields) as $property) {
            if ($metadata->enum($property) === null) {
                continue;
            }

            $value = $metadata->read($object, $property);

            if ($value instanceof VersionedEnum && $value instanceof BackedEnum && !$context->version->isAtLeast($value->since())) {
                $context->unsupported(sprintf('%s "%s" needs %s', $metadata->outputName($property), $value->value, $value->since()->value));

                return null;
            }
        }

        $node = [];

        foreach ($metadata->fields as $property => $field) {
            $value = $metadata->read($object, $property);

            if ($value === Undefined::Value || !$field->emit) {
                continue;
            }

            $name = $metadata->outputName($property);

            if (!self::isAvailable($field, $context->version)) {
                $context->at($name)->unsupported(self::unavailable($name, $field));

                continue;
            }

            if ($field->patterned && is_array($value)) {
                foreach ($value as $key => $item) {
                    $written = $this->value($item, $context->at($key));

                    if ($written !== Undefined::Value) {
                        $node[(string) $key] = $written;
                    }
                }

                continue;
            }

            $written = $metadata->isFreeValue($property)
                ? $this->free($value, $context->at($name))
                : $this->value($value, $context->at($name), $field->map);

            if ($written !== Undefined::Value) {
                $node[$name] = $written;
            }
        }

        if ($object instanceof ExtensibleSpecObject) {
            foreach ($object->extensions as $name => $value) {
                $node[$name] = $this->free($value, $context->at($name));
            }
        }

        $node = $this->rewriters->rewrite($object, $node, $context);
        uksort($node, static fn(int|string $a, int|string $b): int => $metadata->rank($a) <=> $metadata->rank($b));

        return $node;
    }

    /**
     * A field value: objects and enums are written, collections item by item, anything else as a free value.
     * `Undefined::Value` means that the value is left out.
     */
    private function value(mixed $value, Context $context, bool $map = false): mixed
    {
        if ($value instanceof SpecObject) {
            $node = $this->node($value, $context);

            return $node === null ? Undefined::Value : ($node === [] ? new stdClass() : $node);
        }

        if ($value instanceof BackedEnum) {
            return $value->value;
        }

        if (!is_array($value)) {
            return $this->free($value, $context);
        }

        $items = [];

        foreach ($value as $key => $item) {
            $written = $item instanceof SpecObject || $item instanceof BackedEnum
                ? $this->value($item, $context->at($key))
                : $this->free($item, $context->at($key));

            if ($written !== Undefined::Value) {
                $items[$key] = $written;
            }
        }

        if ($map) {
            // PHP turns numeric keys into integers: a map keyed "0", "1"… would otherwise be written as a JSON list.
            return $items === [] || array_is_list($items) ? (object) $items : $items;
        }

        return array_values($items);
    }

    /**
     * A free value (`default`, `example`, an extension…) is written as given, once checked that JSON and YAML can
     * write it. A JsonSerializable is replaced by its data, so that both formats write the same thing.
     *
     * @throws InvalidArgumentException
     */
    private function free(mixed $value, Context $context): mixed
    {
        if (is_float($value) && !is_finite($value)) {
            throw InvalidArgumentException::forValue($context->pointer, sprintf('%s cannot be written', is_nan($value) ? 'NAN' : 'INF'));
        }

        if ($value instanceof JsonSerializable) {
            return $this->free($value->jsonSerialize(), $context);
        }

        if (is_array($value) || $value instanceof stdClass) {
            $items = [];

            foreach (is_array($value) ? $value : get_object_vars($value) as $key => $item) {
                $items[$key] = $this->free($item, $context->at($key));
            }

            return is_array($value) ? $items : (object) $items;
        }

        if (is_resource($value) || is_object($value)) {
            throw InvalidArgumentException::forValue($context->pointer, sprintf('a value of type %s cannot be written', get_debug_type($value)));
        }

        return $value;
    }

    private static function isAvailable(Field $field, Version $version): bool
    {
        return ($field->since === null || $version->isAtLeast($field->since))
            && ($field->until === null || $field->until->isAtLeast($version));
    }

    private static function unavailable(string $name, Field $field): string
    {
        return $field->since !== null
            ? sprintf('%s needs %s', $name, $field->since->value)
            : sprintf('%s is not available after %s', $name, $field->until?->value);
    }
}
