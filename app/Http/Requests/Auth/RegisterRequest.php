<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use App\DataTransferObjects\Users\UserData;
use App\Http\Requests\ApiFormRequest;
use Illuminate\Validation\Rules\Password;

final class RegisterRequest extends ApiFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:32', 'unique:users,phone'],
            'password' => ['required', 'string', 'confirmed', Password::defaults()],
            'device_name' => ['sometimes', 'string', 'max:255'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('phone'))) {
            $this->merge(['phone' => UserData::normalisePhone($this->string('phone')->value())]);
        }

        if (is_string($this->input('email'))) {
            $this->merge(['email' => mb_strtolower(trim($this->string('email')->value()))]);
        }
    }

    public function toData(): UserData
    {
        return UserData::fromArray($this->safe()->except('device_name'));
    }

    public function deviceName(): ?string
    {
        $device = $this->validated('device_name');

        return is_string($device) ? $device : null;
    }
}
