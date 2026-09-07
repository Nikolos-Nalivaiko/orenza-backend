<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Client
 */
final class ClientResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'workspace_id' => $this->workspace_id,
            'type' => [
                'value' => $this->type->value,
                'label' => $this->type->label(),
            ],
            'name' => $this->name,
            'contact' => $this->contact ?? '',
            'phone' => $this->phone ?? '',
            'email' => $this->email ?? '',
            'notes' => $this->notes ?? '',
            'discount' => (float) $this->discount,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
