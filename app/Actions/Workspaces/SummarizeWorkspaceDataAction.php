<?php

declare(strict_types=1);

namespace App\Actions\Workspaces;

use App\Actions\Contracts\Action;
use App\Models\Workspace;
use App\Repositories\Contracts\ClientRepositoryInterface;
use App\Repositories\Contracts\EmployeeRepositoryInterface;
use App\Repositories\Contracts\MaterialRepositoryInterface;
use App\Repositories\Contracts\ObjectRepositoryInterface;
use App\Repositories\Contracts\PaymentRepositoryInterface;
use App\Repositories\Contracts\ServiceRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;

final readonly class SummarizeWorkspaceDataAction implements Action
{
    public function __construct(
        private ObjectRepositoryInterface $objects,
        private ClientRepositoryInterface $clients,
        private EmployeeRepositoryInterface $employees,
        private MaterialRepositoryInterface $materials,
        private ServiceRepositoryInterface $services,
        private PaymentRepositoryInterface $payments,
    ) {}

    /**
     * @return array{objects: int, archived: int, clients: int, employees: int|null, materials: int, services: int, payments: int}
     */
    public function handle(Workspace $workspace): array
    {
        $ofWorkspace = static fn (Builder $query): Builder => $query->where('workspace_id', $workspace->getKey());

        return [
            'objects' => $this->objects->query()->ofWorkspace($workspace)->count(),
            'archived' => $this->objects->query()->ofWorkspace($workspace)->archived()->count(),
            'clients' => $this->clients->countForWorkspace($workspace),
            'employees' => $workspace->type->hasTeam() ? $this->employees->countForWorkspace($workspace) : null,
            'materials' => $this->materials->query()->whereHas('object', $ofWorkspace)->count(),
            'services' => $this->services->query()->whereHas('object', $ofWorkspace)->count(),
            'payments' => $this->payments->query()->whereHas('object', $ofWorkspace)->count(),
        ];
    }
}
