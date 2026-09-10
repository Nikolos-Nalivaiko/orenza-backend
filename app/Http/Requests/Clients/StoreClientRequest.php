<?php

declare(strict_types=1);

namespace App\Http\Requests\Clients;

use App\DataTransferObjects\Clients\ClientData;
use App\Enums\ClientType;
use App\Http\Requests\ApiFormRequest;
use App\Support\Phone;
use Illuminate\Validation\Rule;

final class StoreClientRequest extends ApiFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'type' => ['sometimes', Rule::enum(ClientType::class)],
            'name' => ['required', 'string', 'min:2', 'max:255'],
            'contact' => ['sometimes', 'nullable', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:32'],
            'email' => ['sometimes', 'nullable', 'string', 'email', 'max:255'],
            'notes' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'discount' => ['sometimes', 'nullable', 'numeric', 'min:0', 'max:100'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('phone'))) {
            $this->merge(['phone' => Phone::normalise($this->string('phone')->value())]);
        }

        if (is_string($this->input('email'))) {
            $this->merge(['email' => mb_strtolower(trim($this->string('email')->value()))]);
        }
    }

    public function toData(): ClientData
    {
        return ClientData::fromArray($this->validated());
    }
}
