<?php

declare(strict_types=1);

namespace App\DataTransferObjects\Employees;

use App\DataTransferObjects\BaseData;
use App\Enums\EmployeeStatus;
use App\Support\Optional;
use App\Support\Phone;

final class EmployeeData extends BaseData
{
    public function __construct(
        public readonly string|Optional $name = new Optional,
        public readonly string|null|Optional $role = new Optional,
        public readonly string|null|Optional $phone = new Optional,
        public readonly string|null|Optional $email = new Optional,
        public readonly EmployeeStatus|Optional $status = new Optional,
        public readonly string|null|Optional $notes = new Optional,
    ) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public static function fromArray(array $attributes): static
    {
        return new self(
            name: self::pull($attributes, 'name', static fn (mixed $value): string => trim((string) $value)),
            role: self::pull($attributes, 'role', self::text(...)),
            phone: self::pull($attributes, 'phone', static function (mixed $value): ?string {
                $phone = Phone::normalise((string) $value);

                return $phone === '' ? null : $phone;
            }),
            email: self::pull($attributes, 'email', static function (mixed $value): ?string {
                $email = mb_strtolower(trim((string) $value));

                return $email === '' ? null : $email;
            }),
            status: self::pull($attributes, 'status', static fn (mixed $value): EmployeeStatus => $value instanceof EmployeeStatus
                ? $value
                : EmployeeStatus::from((string) $value)),
            notes: self::pull($attributes, 'notes', self::text(...)),
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
