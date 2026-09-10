<?php

declare(strict_types=1);

namespace App\Actions\Payments;

use App\Actions\Contracts\Action;
use App\DataTransferObjects\Payments\PaymentData;
use App\Exceptions\BusinessRuleException;
use App\Models\Payment;
use App\Repositories\Contracts\PaymentRepositoryInterface;

final readonly class UpdatePaymentAction implements Action
{
    use Concerns\StampsPayment;

    public function __construct(private PaymentRepositoryInterface $payments) {}

    public function handle(Payment $payment, PaymentData $data): Payment
    {
        $attributes = $data->toArray();

        if (array_key_exists('name', $attributes)) {
            $name = trim((string) $attributes['name']);

            if ($name === '') {
                throw BusinessRuleException::make(__('messages.payments.name_required'), ['name' => null]);
            }

            $attributes['name'] = $name;
        }

        if ($attributes === []) {
            return $payment;
        }

        $status = $attributes['status'] ?? $payment->status;
        $paidAt = array_key_exists('paid_at', $attributes)
            ? $attributes['paid_at']
            : $payment->paid_at?->format('Y-m-d');

        $attributes = [...$attributes, ...$this->stamp($status, $paidAt)];

        return $this->payments->update($payment, $attributes);
    }
}
