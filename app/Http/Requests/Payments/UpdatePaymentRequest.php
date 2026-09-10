<?php

declare(strict_types=1);

namespace App\Http\Requests\Payments;

use App\DataTransferObjects\Payments\PaymentData;
use App\Http\Requests\ApiFormRequest;

final class UpdatePaymentRequest extends ApiFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return PaymentRules::all(required: false);
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('paid_at')) && trim($this->string('paid_at')->value()) === '') {
            $this->merge(['paid_at' => null]);
        }
    }

    public function toData(): PaymentData
    {
        return PaymentData::fromArray($this->validated());
    }
}
