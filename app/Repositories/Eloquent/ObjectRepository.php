<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Enums\ObjectStatus;
use App\Models\ConstructionObject;
use App\Models\Workspace;
use App\Repositories\Contracts\ObjectRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

/**
 * @extends BaseRepository<ConstructionObject>
 */
final class ObjectRepository extends BaseRepository implements ObjectRepositoryInterface
{
    /**
     * @return class-string<ConstructionObject>
     */
    protected function model(): string
    {
        return ConstructionObject::class;
    }

    /**
     * @return Collection<int, ConstructionObject>
     */
    public function listForWorkspace(
        Workspace $workspace,
        ?string $search = null,
        ?ObjectStatus $status = null,
        bool $withArchived = false,
    ): Collection {
        $query = $this->query()->with(['client', 'materials', 'services.workers', 'payments'])->ofWorkspace($workspace);

        if (! $withArchived) {
            $query->notArchived();
        }

        if ($status instanceof ObjectStatus) {
            $query->ofStatus($status);
        }

        if ($search !== null && trim($search) !== '') {
            $query->search($search);
        }

        return $query->orderByDesc('created_at')->orderByDesc('id')->get();
    }

    public function countForWorkspace(Workspace $workspace, bool $withArchived = false): int
    {
        $query = $this->query()->ofWorkspace($workspace);

        if (! $withArchived) {
            $query->notArchived();
        }

        return $query->count();
    }

    public function tokenTaken(string $token): bool
    {
        return $this->query()->withTrashed()->where('public_token', $token)->exists();
    }
}
