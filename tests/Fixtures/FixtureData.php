<?php

declare(strict_types=1);

namespace Tests\Fixtures;

use App\DataTransferObjects\BaseData;
use App\Support\Optional;

/**
 * Заглушка для проверки BaseData: одно необязательное поле (Optional)
 * и одно обычное nullable-поле.
 */
final class FixtureData extends BaseData
{
    public function __construct(
        public readonly string|Optional $firstName = new Optional,
        public readonly ?int $age = null,
    ) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public static function fromArray(array $attributes): static
    {
        return new self(
            firstName: array_key_exists('first_name', $attributes)
                ? (string) $attributes['first_name']
                : new Optional,
            age: isset($attributes['age']) ? (int) $attributes['age'] : null,
        );
    }
}
