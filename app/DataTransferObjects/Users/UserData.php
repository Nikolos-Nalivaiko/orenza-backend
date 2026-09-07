<?php

declare(strict_types=1);

namespace App\DataTransferObjects\Users;

use App\DataTransferObjects\BaseData;
use App\Support\Optional;
use App\Support\Phone;

final class UserData extends BaseData
{
    public function __construct(
        public readonly string|Optional $firstName = new Optional,
        public readonly string|Optional $lastName = new Optional,
        public readonly string|Optional $email = new Optional,
        public readonly string|null|Optional $phone = new Optional,
        public readonly string|Optional $password = new Optional,
    ) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public static function fromArray(array $attributes): static
    {
        return new self(
            firstName: self::pull($attributes, 'first_name', static fn (mixed $value): string => trim((string) $value)),
            lastName: self::pull($attributes, 'last_name', static fn (mixed $value): string => trim((string) $value)),
            email: self::pull($attributes, 'email', static fn (mixed $value): string => mb_strtolower(trim((string) $value))),
            phone: self::pull($attributes, 'phone', static fn (mixed $value): string => self::normalisePhone((string) $value)),
            password: self::pull($attributes, 'password', static fn (mixed $value): string => (string) $value),
        );
    }

    public static function normalisePhone(string $phone): string
    {
        return Phone::normalise($phone);
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
