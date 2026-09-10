<?php

declare(strict_types=1);

namespace App\Actions\Payments;

use App\Actions\Contracts\Action;
use App\Models\Payment;
use App\Repositories\Contracts\PaymentRepositoryInterface;

final readonly class DeletePaymentAction implements Action
{
    public function __construct(private PaymentRepositoryInterface $payments) {}

    public function handle(Payment $payment): bool
    {
        return $this->payments->delete($payment);
    }
}
