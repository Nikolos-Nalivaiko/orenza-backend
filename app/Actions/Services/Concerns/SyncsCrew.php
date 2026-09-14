<?php

declare(strict_types=1);

namespace App\Actions\Services\Concerns;

use App\DataTransferObjects\Services\ServiceWorkerData;
use App\Exceptions\BusinessRuleException;
use App\Models\ConstructionObject;
use App\Models\Service;
use App\Models\Workspace;
use App\Repositories\Contracts\EmployeeRepositoryInterface;

/**
 * @property-read EmployeeRepositoryInterface $employees
 */
trait SyncsCrew
{
    /**
     * @param  array<int, ServiceWorkerData>  $crew
     */
    private function syncCrew(Service $service, ConstructionObject $object, array $crew): void
    {
        $this->guardCrew($object, $crew);

        $rows = [];

        foreach ($crew as $worker) {
            $rows[$worker->employeeId] = $worker->toArray();
        }

        $service->workers()->whereNotIn('employee_id', array_keys($rows) ?: [0])->delete();

        foreach ($rows as $employeeId => $row) {
            $service->workers()->updateOrCreate(['employee_id' => $employeeId], $row);
        }

        $service->load('workers');
    }

    /**
     * @param  array<int, ServiceWorkerData>  $crew
     */
    private function guardCrew(ConstructionObject $object, array $crew): void
    {
        if ($crew === []) {
            return;
        }

        $workspace = $object->workspace;

        if (! $workspace instanceof Workspace || ! $workspace->type->hasTeam()) {
            throw BusinessRuleException::make(
                __('messages.services.no_team'),
                ['workers' => null],
            );
        }

        $ids = array_map(static fn (ServiceWorkerData $worker): int => $worker->employeeId, $crew);
        $known = $this->employees->idsOfWorkspace($workspace, $ids);

        if (array_diff(array_unique($ids), $known) !== []) {
            throw BusinessRuleException::make(
                __('messages.employees.not_in_team'),
                ['workers' => null],
            );
        }
    }
}
