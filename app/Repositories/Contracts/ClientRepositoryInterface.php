<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Enums\ClientType;
use App\Models\Client;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Collection;

/**
 * @extends RepositoryInterface<Client>
 */
interface ClientRepositoryInterface extends RepositoryInterface
{
    /**
     * @return Collection<int, Client>
     */
    public function listForWorkspace(
        Workspace $workspace,
        ?string $search = null,
        ?ClientType $type = null,
    ): Collection;

    public function countForWorkspace(Workspace $workspace): int;
}
