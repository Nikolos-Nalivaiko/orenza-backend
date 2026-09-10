<?php

declare(strict_types=1);

namespace App\Http\Requests\Objects;

final class UpdateObjectRequest extends ObjectRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            ...$this->sharedRules(),
            'name' => ['sometimes', 'string', 'min:3', 'max:255'],
            'address' => ['sometimes', 'string', 'min:5', 'max:255'],
        ];
    }
}
