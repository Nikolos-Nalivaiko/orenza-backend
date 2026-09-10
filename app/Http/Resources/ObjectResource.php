<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\ConstructionObject;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ConstructionObject
 */
final class ObjectResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'workspace_id' => $this->workspace_id,
            'name' => $this->name,
            'description' => $this->description,
            'address' => $this->address,
            'client' => $this->client === null ? null : (new ClientResource($this->client))->resolve(),
            'status' => [
                'value' => $this->status->value,
                'label' => $this->status->label(),
            ],
            'started_at' => $this->started_at?->format('Y-m-d'),
            'finished_at' => $this->finished_at?->format('Y-m-d'),
            'actual_started_at' => $this->actual_started_at?->format('Y-m-d'),
            'actual_finished_at' => $this->actual_finished_at?->format('Y-m-d'),
            'materials' => MaterialResource::collection($this->materials)->resolve(),
            'services' => ServiceResource::collection($this->services)->resolve(),
            'cover' => $this->cover_path,
            'public_token' => $this->public_token,
            'archived_at' => $this->archived_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
