<?php

declare(strict_types=1);

namespace App\DataTransferObjects\Services;

use App\DataTransferObjects\BaseData;
use App\Enums\ServiceStatus;
use App\Support\Optional;

final class ServiceData extends BaseData
{
    /**
     * @param  array<int, ServiceWorkerData>|null|Optional  $workers
     */
    public function __construct(
        public readonly string|Optional $name = new Optional,
        public readonly string|null|Optional $description = new Optional,
        public readonly string|Optional $unit = new Optional,
        public readonly float|Optional $plannedVolume = new Optional,
        public readonly float|null|Optional $actualVolume = new Optional,
        public readonly float|null|Optional $clientPrice = new Optional,
        public readonly ServiceStatus|Optional $status = new Optional,
        public readonly array|null|Optional $workers = new Optional,
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
            unit: self::pull($attributes, 'unit', static fn (mixed $value): string => trim((string) $value)),
            plannedVolume: self::pull($attributes, 'planned_volume', static fn (mixed $value): float => (float) $value),
            actualVolume: self::pull($attributes, 'actual_volume', static fn (mixed $value): float => (float) $value),
            clientPrice: self::pull($attributes, 'client_price', static fn (mixed $value): float => (float) $value),
            status: self::pull($attributes, 'status', static fn (mixed $value): ServiceStatus => $value instanceof ServiceStatus
                ? $value
                : ServiceStatus::from((string) $value)),
            workers: self::pull($attributes, 'workers', static function (mixed $value): array {
                /** @var array<int, array<string, mixed>> $rows */
                $rows = is_array($value) ? $value : [];

                return array_map(ServiceWorkerData::fromArray(...), $rows);
            }),
        );
    }

    /**
     * @return array<int, ServiceWorkerData>|null
     */
    public function crew(): ?array
    {
        if (Optional::isMissing($this->workers)) {
            return null;
        }

        return $this->workers ?? [];
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
