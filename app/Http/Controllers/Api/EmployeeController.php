<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\Employees\CreateEmployeeAction;
use App\Actions\Employees\DeleteEmployeeAction;
use App\Actions\Employees\UpdateEmployeeAction;
use App\Enums\EmployeeStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Employees\StoreEmployeeRequest;
use App\Http\Requests\Employees\UpdateEmployeeRequest;
use App\Http\Resources\EmployeeResource;
use App\Models\Employee;
use App\Models\Workspace;
use App\Repositories\Contracts\EmployeeRepositoryInterface;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class EmployeeController extends Controller
{
    public function __construct(
        private readonly EmployeeRepositoryInterface $employees,
        private readonly CreateEmployeeAction $createEmployee,
        private readonly UpdateEmployeeAction $updateEmployee,
        private readonly DeleteEmployeeAction $deleteEmployee,
    ) {}

    public function index(Request $request, Workspace $workspace): JsonResponse
    {
        $this->authorize('view', $workspace);

        $status = $request->query('status');
        $search = $request->query('search');

        $employees = $this->employees->listForWorkspace(
            $workspace,
            $search === null ? null : (string) $search,
            is_string($status) ? EmployeeStatus::tryFrom($status) : null,
        );

        return ApiResponse::success(
            EmployeeResource::collection($employees)->resolve(),
            meta: [
                'total' => $employees->count(),
                'all' => $this->employees->countForWorkspace($workspace),
            ],
        );
    }

    public function store(StoreEmployeeRequest $request, Workspace $workspace): JsonResponse
    {
        $this->authorize('view', $workspace);

        $employee = $this->createEmployee->handle($workspace, $request->toData());

        return ApiResponse::created(new EmployeeResource($employee), __('messages.employees.created'));
    }

    public function show(Workspace $workspace, Employee $employee): JsonResponse
    {
        $this->authorize('view', $workspace);

        return ApiResponse::success(new EmployeeResource($employee));
    }

    public function update(
        UpdateEmployeeRequest $request,
        Workspace $workspace,
        Employee $employee,
    ): JsonResponse {
        $this->authorize('view', $workspace);

        $employee = $this->updateEmployee->handle($employee, $request->toData());

        return ApiResponse::success(new EmployeeResource($employee), __('messages.employees.updated'));
    }

    public function destroy(Workspace $workspace, Employee $employee): JsonResponse
    {
        $this->authorize('view', $workspace);

        $this->deleteEmployee->handle($employee);

        return ApiResponse::success(null, __('messages.employees.deleted'));
    }
}
