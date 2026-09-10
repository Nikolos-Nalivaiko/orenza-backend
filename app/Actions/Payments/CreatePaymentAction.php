<?php

declare(strict_types=1);

namespace App\Actions\Payments;

use App\Actions\Contracts\Action;
use App\DataTransferObjects\Payments\PaymentData;
use App\Enums\PaymentStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\ConstructionObject;
use App\Models\Payment;
use App\Repositories\Contracts\PaymentRepositoryInterface;

final readonly class CreatePaymentAction implements Action
{
    use Concerns\StampsPayment;

    public function __construct(private PaymentRepositoryInterface $payments) {}

    public function handle(ConstructionObject $object, PaymentData $data): Payment
    {
        $attributes = $data->toArray();
        $name = trim((string) ($attributes['name'] ?? ''));

        if ($name === '') {
            throw BusinessRuleException::make(__('messages.payments.name_required'), ['name' => null]);
        }

        $status = $attributes['status'] ?? PaymentStatus::default();

        return $this->payments->create([
            ...$attributes,
            ...$this->stamp($status, $attributes['paid_at'] ?? null),
            'construction_object_id' => $object->getKey(),
            'name' => $name,
            'amount' => $attributes['amount'] ?? 0,
            'status' => $status,
            'client_visible' => $attributes['client_visible'] ?? false,
        ]);
    }
}
