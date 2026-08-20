<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Enums\WorkspaceType;
use App\Models\User;
use App\Models\Workspace;
use App\Repositories\Contracts\WorkspaceRepositoryInterface;

/**
 * @extends BaseRepository<Workspace>
 */
final class WorkspaceRepository extends BaseRepository implements WorkspaceRepositoryInterface
{
    /**
     * @return class-string<Workspace>
     */
    protected function model(): string
    {
        return Workspace::class;
    }

    public function findBySlug(string $slug): ?Workspace
    {
        return $this->query()->where('slug', $slug)->first();
    }

    public function slugExists(string $slug): bool
    {
        return $this->query()->withTrashed()->where('slug', $slug)->exists();
    }

    public function personalExistsFor(User $user): bool
    {
        return $this->query()
            ->where('owner_id', $user->getKey())
            ->where('type', WorkspaceType::Personal)
            ->exists();
    }
}
