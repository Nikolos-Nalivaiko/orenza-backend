<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Enums\ClientType;
use App\Models\Client;
use App\Models\Workspace;
use App\Repositories\Contracts\ClientRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

/**
 * @extends BaseRepository<Client>
 */
final class ClientRepository extends BaseRepository implements ClientRepositoryInterface
{
    /**
     * @return class-string<Client>
     */
    protected function model(): string
    {
        return Client::class;
    }

    /**
     * @return Collection<int, Client>
     */
    public function listForWorkspace(
        Workspace $workspace,
        ?string $search = null,
        ?ClientType $type = null,
    ): Collection {
        $query = $this->query()->ofWorkspace($workspace);

        if ($type instanceof ClientType) {
            $query->ofType($type);
        }

        if ($search !== null && trim($search) !== '') {
            $query->search($search);
        }

        return $query->orderByRaw(Client::collated('name', $query))->get();
    }

    public function countForWorkspace(Workspace $workspace): int
    {
        return $this->query()->ofWorkspace($workspace)->count();
    }
}
