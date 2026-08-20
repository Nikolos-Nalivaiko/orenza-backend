<?php

declare(strict_types=1);

namespace App\Support;

use JsonSerializable;

/**
 * Sentinel used by DTOs to distinguish "value was not provided" from "value is null".
 *
 * Properties holding an Optional instance are skipped when the DTO is converted
 * to an array, which makes partial updates (PATCH) trivial to express.
 */
final class Optional implements JsonSerializable
{
    private static ?self $instance = null;

    /**
     * Public so that it can be used as a property default (`new Optional`),
     * which PHP only allows for plain constructor calls.
     */
    public function __construct() {}

    public static function create(): self
    {
        return self::$instance ??= new self;
    }

    public static function isMissing(mixed $value): bool
    {
        return $value instanceof self;
    }

    public static function isPresent(mixed $value): bool
    {
        return ! self::isMissing($value);
    }

    public function jsonSerialize(): mixed
    {
        return null;
    }

    public function __toString(): string
    {
        return '';
    }
}
