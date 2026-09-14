<?php

declare(strict_types=1);

namespace App\Http\Requests\Employees;

final class StoreEmployeeRequest extends EmployeeRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            ...$this->sharedRules(),
            'name' => ['required', 'string', 'min:'.self::NAME_MIN, 'max:'.self::NAME_MAX],
        ];
    }
}
