<?php

declare(strict_types=1);

namespace App\Http\Requests\Employees;

use App\DataTransferObjects\Employees\EmployeeData;
use App\Enums\EmployeeStatus;
use App\Http\Requests\ApiFormRequest;
use App\Support\Phone;
use Illuminate\Validation\Rule;

abstract class EmployeeRequest extends ApiFormRequest
{
    public const NAME_MIN = 2;

    public const NAME_MAX = 255;

    public const ROLE_MAX = 120;

    public const NOTES_MAX = 1000;

    /**
     * @return array<string, mixed>
     */
    protected function sharedRules(): array
    {
        return [
            'role' => ['sometimes', 'nullable', 'string', 'max:'.self::ROLE_MAX],
            'phone' => ['sometimes', 'nullable', 'string', 'max:32'],
            'email' => ['sometimes', 'nullable', 'string', 'email', 'max:255'],
            'status' => ['sometimes', Rule::enum(EmployeeStatus::class)],
            'notes' => ['sometimes', 'nullable', 'string', 'max:'.self::NOTES_MAX],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('name'))) {
            $this->merge(['name' => trim($this->string('name')->value())]);
        }

        foreach (['role', 'notes'] as $field) {
            if (is_string($this->input($field))) {
                $this->merge([$field => $this->blankToNull(trim($this->string($field)->value()))]);
            }
        }

        if (is_string($this->input('phone'))) {
            $this->merge(['phone' => $this->blankToNull(Phone::normalise($this->string('phone')->value()))]);
        }

        if (is_string($this->input('email'))) {
            $this->merge(['email' => $this->blankToNull(mb_strtolower(trim($this->string('email')->value())))]);
        }
    }

    private function blankToNull(string $value): ?string
    {
        return $value === '' ? null : $value;
    }

    public function toData(): EmployeeData
    {
        return EmployeeData::fromArray($this->validated());
    }
}
