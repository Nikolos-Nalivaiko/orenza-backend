<?php

declare(strict_types=1);

namespace App\DataTransferObjects\Services;

final readonly class ServiceWorkerData
{
    public function __construct(
        public int $employeeId,
        public float $volume,
        public float $rate,
    ) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public static function fromArray(array $attributes): self
    {
        return new self(
            employeeId: (int) ($attributes['employee_id'] ?? 0),
            volume: (float) ($attributes['volume'] ?? 0),
            rate: (float) ($attributes['rate'] ?? 0),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'employee_id' => $this->employeeId,
            'volume' => $this->volume,
            'rate' => $this->rate,
        ];
    }
}
