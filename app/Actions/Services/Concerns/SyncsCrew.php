<?php

declare(strict_types=1);

namespace App\Actions\Services\Concerns;

use App\DataTransferObjects\Services\ServiceWorkerData;
use App\Exceptions\BusinessRuleException;
use App\Models\ConstructionObject;
use App\Models\Service;

trait SyncsCrew
{
    /**
     * @param  array<int, ServiceWorkerData>  $crew
     */
    private function syncCrew(Service $service, ConstructionObject $object, array $crew): void
    {
        $this->guardTeam($object, $crew);

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
    private function guardTeam(ConstructionObject $object, array $crew): void
    {
        if ($crew === []) {
            return;
        }

        $workspace = $object->workspace;

        if ($workspace === null || ! $workspace->type->hasTeam()) {
            throw BusinessRuleException::make(
                __('messages.services.no_team'),
                ['workers' => null],
            );
        }
    }
}
