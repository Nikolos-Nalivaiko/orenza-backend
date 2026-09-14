<?php

declare(strict_types=1);

namespace App\Actions\Employees;

use App\Actions\Contracts\Action;
use App\Exceptions\BusinessRuleException;
use App\Models\Employee;
use App\Repositories\Contracts\EmployeeRepositoryInterface;

final readonly class DeleteEmployeeAction implements Action
{
    public function __construct(private EmployeeRepositoryInterface $employees) {}

    public function handle(Employee $employee): bool
    {
        if ($employee->charges()->exists()) {
            throw BusinessRuleException::make(__('messages.employees.has_charges'));
        }

        return $this->employees->delete($employee);
    }
}
