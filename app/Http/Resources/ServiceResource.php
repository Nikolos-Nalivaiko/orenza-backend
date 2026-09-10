<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Service;
use App\Models\ServiceWorker;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Service
 */
final class ServiceResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'unit' => $this->unit,
            'planned_volume' => (float) $this->planned_volume,
            'actual_volume' => $this->actual_volume === null ? null : (float) $this->actual_volume,
            'client_price' => $this->client_price === null ? null : (float) $this->client_price,
            'status' => [
                'value' => $this->status->value,
                'label' => $this->status->label(),
            ],
            'workers' => $this->workers
                ->map(static fn (ServiceWorker $worker): array => [
                    'employee_id' => $worker->employee_id,
                    'volume' => (float) $worker->volume,
                    'rate' => (float) $worker->rate,
                ])
                ->values()
                ->all(),
        ];
    }
}
