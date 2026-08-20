<?php

declare(strict_types=1);

namespace App\DataTransferObjects;

use App\Support\Optional;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Str;
use JsonSerializable;

/**
 * Immutable transport object between the HTTP layer and the domain layer.
 *
 * @implements Arrayable<string, mixed>
 */
abstract class BaseData implements Arrayable, JsonSerializable
{
    /**
     * Build the DTO from a plain array (request payload, queue job, console input).
     *
     * @param  array<string, mixed>  $attributes
     */
    abstract public static function fromArray(array $attributes): static;

    /**
     * Snake-cased representation, with every "not provided" property stripped out.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $payload = [];

        foreach (get_object_vars($this) as $property => $value) {
            if (Optional::isMissing($value)) {
                continue;
            }

            $payload[Str::snake($property)] = $value instanceof Arrayable ? $value->toArray() : $value;
        }

        return $payload;
    }

    /**
     * @return array<string, mixed>
     */
    public function only(string ...$keys): array
    {
        return array_intersect_key($this->toArray(), array_flip($keys));
    }

    /**
     * @return array<string, mixed>
     */
    public function except(string ...$keys): array
    {
        return array_diff_key($this->toArray(), array_flip($keys));
    }

    /**
     * Whether the given (camelCased) property was provided.
     */
    public function has(string $property): bool
    {
        return property_exists($this, $property) && Optional::isPresent($this->{$property});
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
