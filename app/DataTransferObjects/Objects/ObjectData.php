<?php

declare(strict_types=1);

namespace App\DataTransferObjects\Objects;

use App\DataTransferObjects\BaseData;
use App\Enums\ObjectStatus;
use App\Support\Optional;

final class ObjectData extends BaseData
{
    public function __construct(
        public readonly string|Optional $name = new Optional,
        public readonly string|null|Optional $description = new Optional,
        public readonly string|Optional $address = new Optional,
        public readonly int|null|Optional $clientId = new Optional,
        public readonly ObjectStatus|Optional $status = new Optional,
        public readonly float|null|Optional $discountPercent = new Optional,
        public readonly float|null|Optional $discountAmount = new Optional,
        public readonly string|null|Optional $startedAt = new Optional,
        public readonly string|null|Optional $finishedAt = new Optional,
        public readonly string|null|Optional $actualStartedAt = new Optional,
        public readonly string|null|Optional $actualFinishedAt = new Optional,
        public readonly bool|Optional $archived = new Optional,
    ) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public static function fromArray(array $attributes): static
    {
        return new self(
            name: self::pull($attributes, 'name', static fn (mixed $value): string => trim((string) $value)),
            description: self::pull($attributes, 'description', self::text(...)),
            address: self::pull($attributes, 'address', static fn (mixed $value): string => trim((string) $value)),
            clientId: self::pull($attributes, 'client_id', static fn (mixed $value): int => (int) $value),
            status: self::pull($attributes, 'status', static fn (mixed $value): ObjectStatus => $value instanceof ObjectStatus
                ? $value
                : ObjectStatus::from((string) $value)),
            discountPercent: self::pull($attributes, 'discount_percent', static fn (mixed $value): float => (float) $value),
            discountAmount: self::pull($attributes, 'discount_amount', static fn (mixed $value): float => (float) $value),
            startedAt: self::pull($attributes, 'started_at', self::text(...)),
            finishedAt: self::pull($attributes, 'finished_at', self::text(...)),
            actualStartedAt: self::pull($attributes, 'actual_started_at', self::text(...)),
            actualFinishedAt: self::pull($attributes, 'actual_finished_at', self::text(...)),
            archived: self::pull($attributes, 'archived', static fn (mixed $value): bool => (bool) $value),
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
