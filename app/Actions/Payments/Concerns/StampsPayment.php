<?php

declare(strict_types=1);

namespace App\Actions\Payments\Concerns;

use App\Enums\PaymentStatus;

trait StampsPayment
{
    /**
     * @return array<string, mixed>
     */
    private function stamp(PaymentStatus $status, ?string $paidAt): array
    {
        if ($status->isPaid()) {
            return ['paid_at' => $paidAt ?? now()->toDateString()];
        }

        return ['paid_at' => null];
    }
}
