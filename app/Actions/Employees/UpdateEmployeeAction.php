<?php

declare(strict_types=1);

namespace App\Actions\Employees;

use App\Actions\Contracts\Action;
use App\DataTransferObjects\Employees\EmployeeData;
use App\Exceptions\BusinessRuleException;
use App\Models\Employee;
use App\Repositories\Contracts\EmployeeRepositoryInterface;

final readonly class UpdateEmployeeAction implements Action
{
    public function __construct(private EmployeeRepositoryInterface $employees) {}

    public function handle(Employee $employee, EmployeeData $data): Employee
    {
        $attributes = $data->toArray();

        if (array_key_exists('name', $attributes)) {
            $name = trim((string) $attributes['name']);

            if ($name === '') {
                throw BusinessRuleException::make(__('messages.employees.name_required'), ['name' => null]);
            }

            $attributes['name'] = $name;
        }

        if ($attributes === []) {
            return $employee;
        }

        return $this->employees->update($employee, $attributes);
    }
}
