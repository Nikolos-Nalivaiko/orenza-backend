<?php

declare(strict_types=1);

namespace App\DataTransferObjects\Materials;

use App\DataTransferObjects\BaseData;
use App\Enums\MaterialBuyer;
use App\Enums\MaterialStatus;
use App\Support\Optional;

final class MaterialData extends BaseData
{
    public function __construct(
        public readonly string|Optional $name = new Optional,
        public readonly string|Optional $unit = new Optional,
        public readonly float|Optional $quantity = new Optional,
        public readonly MaterialBuyer|Optional $buyer = new Optional,
        public readonly float|null|Optional $costPrice = new Optional,
        public readonly float|null|Optional $clientPrice = new Optional,
        public readonly MaterialStatus|Optional $status = new Optional,
        public readonly bool|Optional $approvedByClient = new Optional,
    ) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public static function fromArray(array $attributes): static
    {
        return new self(
            name: self::pull($attributes, 'name', static fn (mixed $value): string => trim((string) $value)),
            unit: self::pull($attributes, 'unit', static fn (mixed $value): string => trim((string) $value)),
            quantity: self::pull($attributes, 'quantity', static fn (mixed $value): float => (float) $value),
            buyer: self::pull($attributes, 'buyer', static fn (mixed $value): MaterialBuyer => $value instanceof MaterialBuyer
                ? $value
                : MaterialBuyer::from((string) $value)),
            costPrice: self::pull($attributes, 'cost_price', static fn (mixed $value): float => (float) $value),
            clientPrice: self::pull($attributes, 'client_price', static fn (mixed $value): float => (float) $value),
            status: self::pull($attributes, 'status', static fn (mixed $value): MaterialStatus => $value instanceof MaterialStatus
                ? $value
                : MaterialStatus::from((string) $value)),
            approvedByClient: self::pull($attributes, 'approved_by_client', static fn (mixed $value): bool => (bool) $value),
        );
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private static function pull(array $attributes, string $key, callable $cast): mixed
    {
        if (! array_key_exists($key, $attributes)) {
            return Optional::create();
        }

        if ($attributes[$key] === null) {
            return null;
        }

        return $cast($attributes[$key]);
    }
}
