<?php

declare(strict_types=1);

namespace App\Actions\Employees;

use App\Actions\Contracts\Action;
use App\DataTransferObjects\Employees\EmployeeData;
use App\Enums\EmployeeStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\Employee;
use App\Models\Workspace;
use App\Repositories\Contracts\EmployeeRepositoryInterface;

final readonly class CreateEmployeeAction implements Action
{
    use Concerns\NeedsTeam;

    public function __construct(private EmployeeRepositoryInterface $employees) {}

    public function handle(Workspace $workspace, EmployeeData $data): Employee
    {
        $this->guardTeam($workspace);

        $attributes = $data->toArray();
        $name = trim((string) ($attributes['name'] ?? ''));

        if ($name === '') {
            throw BusinessRuleException::make(__('messages.employees.name_required'), ['name' => null]);
        }

        return $this->employees->create([
            ...$attributes,
            'workspace_id' => $workspace->getKey(),
            'name' => $name,
            'status' => $attributes['status'] ?? EmployeeStatus::default(),
        ]);
    }
}
