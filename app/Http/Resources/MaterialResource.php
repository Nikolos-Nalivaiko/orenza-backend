<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Material;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Material
 */
final class MaterialResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'unit' => $this->unit,
            'quantity' => (float) $this->quantity,
            'buyer' => [
                'value' => $this->buyer->value,
                'label' => $this->buyer->label(),
            ],
            'cost_price' => $this->cost_price === null ? null : (float) $this->cost_price,
            'client_price' => $this->client_price === null ? null : (float) $this->client_price,
            'status' => [
                'value' => $this->status->value,
                'label' => $this->status->label(),
            ],
            'approved_by_client' => $this->approved_by_client,
        ];
    }
}
