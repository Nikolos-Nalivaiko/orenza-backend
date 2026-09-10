<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Enums\ObjectStatus;
use App\Models\ConstructionObject;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Collection;

/**
 * @extends RepositoryInterface<ConstructionObject>
 */
interface ObjectRepositoryInterface extends RepositoryInterface
{
    /**
     * @return Collection<int, ConstructionObject>
     */
    public function listForWorkspace(
        Workspace $workspace,
        ?string $search = null,
        ?ObjectStatus $status = null,
        bool $withArchived = false,
    ): Collection;

    public function countForWorkspace(Workspace $workspace, bool $withArchived = false): int;

    public function tokenTaken(string $token): bool;
}
