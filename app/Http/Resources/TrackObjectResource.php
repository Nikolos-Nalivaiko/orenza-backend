<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Enums\DueState;
use App\Enums\PhotoVariant;
use App\Models\ConstructionObject;
use App\Models\Material;
use App\Models\ObjectPhoto;
use App\Models\Payment;
use App\Models\Service;
use App\Support\Media\PhotoStorage;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ConstructionObject
 */
final class TrackObjectResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $finished = $this->isFinished();
        $client = $this->clientTotal();
        $paid = $this->paidTotal();
        $state = DueState::of($client, $paid);
        $readiness = $this->readiness();

        return [
            'name' => $this->name,
            'address' => $this->address,
            'description' => $this->description,
            'status' => [
                'value' => $this->status->value,
                'label' => $this->status->label(),
            ],
            'cover' => CoverResource::for($this->resource),
            'readiness' => $readiness === null ? null : round($readiness, 4),
            'works' => [
                'done' => $this->completedServicesCount(),
                'total' => $this->services->count(),
            ],
            'started_at' => $this->started_at?->format('Y-m-d'),
            'finished_at' => $this->finished_at?->format('Y-m-d'),
            'actual_started_at' => $finished ? $this->actual_started_at?->format('Y-m-d') : null,
            'actual_finished_at' => $finished ? $this->actual_finished_at?->format('Y-m-d') : null,
            'finished' => $finished,
            'materials' => $this->materials
                ->map(static fn (Material $material): array => [
                    'id' => $material->id,
                    'name' => $material->name,
                    'quantity' => (float) $material->quantity,
                    'unit' => $material->unit,
                    'status' => [
                        'value' => $material->status->value,
                        'label' => $material->status->label(),
                    ],
                ])
                ->values()
                ->all(),
            'services' => $this->services
                ->map(static fn (Service $service): array => [
                    'id' => $service->id,
                    'name' => $service->name,
                    'description' => $service->description,
                    'unit' => $service->unit,
                    'planned_volume' => (float) $service->planned_volume,
                    'actual_volume' => $service->actual_volume === null ? null : (float) $service->actual_volume,
                    'status' => [
                        'value' => $service->status->value,
                        'label' => $service->status->label(),
                    ],
                    'total' => round($service->revenue() ?? 0.0, 2),
                ])
                ->values()
                ->all(),
            'money' => [
                'client' => round($client, 2),
                'paid' => round($paid, 2),
                'due' => round($client - $paid, 2),
                'progress' => $client > 0 ? round(min(1.0, $paid / $client), 4) : 0.0,
                'state' => [
                    'value' => $state->value,
                    'label' => $state->label(),
                ],
            ],
            'photos' => $this->photos
                ->map(static fn (ObjectPhoto $photo): array => [
                    'id' => $photo->id,
                    'thumb' => app(PhotoStorage::class)->url($photo, PhotoVariant::Thumb),
                    'full' => app(PhotoStorage::class)->url($photo, PhotoVariant::Full),
                    'width' => $photo->width,
                    'height' => $photo->height,
                    'color' => $photo->color,
                    'at' => $photo->moment()?->toIso8601String(),
                ])
                ->values()
                ->all(),
            'payments' => $this->payments
                ->reject(static fn (Payment $payment): bool => $payment->status->isCancelled())
                ->map(static fn (Payment $payment): array => [
                    'id' => $payment->id,
                    'date' => $payment->paid_at?->format('Y-m-d'),
                    'amount' => (float) $payment->amount,
                    'received' => $payment->status->isPaid(),
                    'note' => $payment->client_visible ? $payment->name : null,
                ])
                ->values()
                ->all(),
        ];
    }
}
