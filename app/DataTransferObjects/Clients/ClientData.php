<?php

declare(strict_types=1);

namespace App\DataTransferObjects\Clients;

use App\DataTransferObjects\BaseData;
use App\Enums\ClientType;
use App\Support\Optional;
use App\Support\Phone;

final class ClientData extends BaseData
{
    public function __construct(
        public readonly ClientType|Optional $type = new Optional,
        public readonly string|Optional $name = new Optional,
        public readonly string|null|Optional $contact = new Optional,
        public readonly string|null|Optional $phone = new Optional,
        public readonly string|null|Optional $email = new Optional,
        public readonly string|null|Optional $notes = new Optional,
        public readonly float|Optional $discount = new Optional,
    ) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public static function fromArray(array $attributes): static
    {
        return new self(
            type: self::pull($attributes, 'type', static fn (mixed $value): ClientType => $value instanceof ClientType
                ? $value
                : ClientType::from((string) $value)),
            name: self::pull($attributes, 'name', static fn (mixed $value): string => trim((string) $value)),
            contact: self::pull($attributes, 'contact', self::text(...)),
            phone: self::pull($attributes, 'phone', static function (mixed $value): ?string {
                $phone = Phone::normalise((string) $value);

                return $phone === '' ? null : $phone;
            }),
            email: self::pull($attributes, 'email', static function (mixed $value): ?string {
                $email = mb_strtolower(trim((string) $value));

                return $email === '' ? null : $email;
            }),
            notes: self::pull($attributes, 'notes', self::text(...)),
            discount: self::pull($attributes, 'discount', static fn (mixed $value): float => (float) $value),
        );
    }

    private static function text(mixed $value): ?string
    {
        $text = trim((string) $value);

        return $text === '' ? null : $text;
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
