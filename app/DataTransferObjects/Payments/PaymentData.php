<?php

declare(strict_types=1);

namespace App\DataTransferObjects\Payments;

use App\DataTransferObjects\BaseData;
use App\Enums\PaymentStatus;
use App\Support\Optional;

final class PaymentData extends BaseData
{
    public function __construct(
        public readonly string|Optional $name = new Optional,
        public readonly string|null|Optional $description = new Optional,
        public readonly float|Optional $amount = new Optional,
        public readonly PaymentStatus|Optional $status = new Optional,
        public readonly string|null|Optional $paidAt = new Optional,
        public readonly bool|Optional $clientVisible = new Optional,
    ) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public static function fromArray(array $attributes): static
    {
        return new self(
            name: self::pull($attributes, 'name', static fn (mixed $value): string => trim((string) $value)),
            description: self::pull($attributes, 'description', static function (mixed $value): ?string {
                $text = trim((string) $value);

                return $text === '' ? null : $text;
            }),
            amount: self::pull($attributes, 'amount', static fn (mixed $value): float => (float) $value),
            status: self::pull($attributes, 'status', static fn (mixed $value): PaymentStatus => $value instanceof PaymentStatus
                ? $value
                : PaymentStatus::from((string) $value)),
            paidAt: self::pull($attributes, 'paid_at', static function (mixed $value): ?string {
                $day = trim((string) $value);

                return $day === '' ? null : $day;
            }),
            clientVisible: self::pull($attributes, 'client_visible', static fn (mixed $value): bool => (bool) $value),
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
