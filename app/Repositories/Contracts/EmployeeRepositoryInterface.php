<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Enums\EmployeeStatus;
use App\Models\Employee;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Collection;

/**
 * @extends RepositoryInterface<Employee>
 */
interface EmployeeRepositoryInterface extends RepositoryInterface
{
    /**
     * @return Collection<int, Employee>
     */
    public function listForWorkspace(
        Workspace $workspace,
        ?string $search = null,
        ?EmployeeStatus $status = null,
    ): Collection;

    public function countForWorkspace(Workspace $workspace): int;

    /**
     * @param  array<int, int>  $ids
     * @return array<int, int>
     */
    public function idsOfWorkspace(Workspace $workspace, array $ids): array;
}
