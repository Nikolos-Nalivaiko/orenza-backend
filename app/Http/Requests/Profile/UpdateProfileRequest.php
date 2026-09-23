<?php

declare(strict_types=1);

namespace App\Http\Requests\Profile;

use App\DataTransferObjects\Users\UserData;
use App\Http\Requests\ApiFormRequest;
use App\Models\User;
use Illuminate\Validation\Rule;

final class UpdateProfileRequest extends ApiFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var User $user */
        $user = $this->user();

        return [
            'first_name' => ['sometimes', 'required', 'string', 'max:255'],
            'last_name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => ['sometimes', 'required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['sometimes', 'nullable', 'string', 'max:32', Rule::unique('users', 'phone')->ignore($user->id)],
        ];
    }

    protected function prepareForValidation(): void
    {
        foreach (['first_name', 'last_name'] as $field) {
            if (is_string($this->input($field))) {
                $this->merge([$field => trim($this->string($field)->value())]);
            }
        }

        if (is_string($this->input('email'))) {
            $this->merge(['email' => mb_strtolower(trim($this->string('email')->value()))]);
        }

        if (is_string($this->input('phone'))) {
            $phone = trim($this->string('phone')->value());

            $this->merge(['phone' => $phone === '' ? null : UserData::normalisePhone($phone)]);
        }
    }

    public function toData(): UserData
    {
        return UserData::fromArray($this->validated());
    }
}
