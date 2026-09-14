<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Enums\EmployeeStatus;
use App\Models\Employee;
use App\Models\Workspace;
use App\Repositories\Contracts\EmployeeRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

/**
 * @extends BaseRepository<Employee>
 */
final class EmployeeRepository extends BaseRepository implements EmployeeRepositoryInterface
{
    /**
     * @return class-string<Employee>
     */
    protected function model(): string
    {
        return Employee::class;
    }

    /**
     * @return Collection<int, Employee>
     */
    public function listForWorkspace(
        Workspace $workspace,
        ?string $search = null,
        ?EmployeeStatus $status = null,
    ): Collection {
        $query = $this->query()->ofWorkspace($workspace);

        if ($status instanceof EmployeeStatus) {
            $query->ofStatus($status);
        }

        if ($search !== null && trim($search) !== '') {
            $query->search($search);
        }

        return $query->orderByRaw(Employee::collated('name', $query))->get();
    }

    public function countForWorkspace(Workspace $workspace): int
    {
        return $this->query()->ofWorkspace($workspace)->count();
    }

    /**
     * @param  array<int, int>  $ids
     * @return array<int, int>
     */
    public function idsOfWorkspace(Workspace $workspace, array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        /** @var array<int, int> $found */
        $found = $this->query()
            ->ofWorkspace($workspace)
            ->whereIn('id', $ids)
            ->pluck('id')
            ->map(static fn (mixed $id): int => (int) $id)
            ->all();

        return $found;
    }
}
