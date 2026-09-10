<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Payment
 */
final class PaymentResource extends JsonResource
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
            'amount' => (float) $this->amount,
            'status' => [
                'value' => $this->status->value,
                'label' => $this->status->label(),
            ],
            'paid_at' => $this->paid_at?->format('Y-m-d'),
            'client_visible' => $this->client_visible,
        ];
    }
}
