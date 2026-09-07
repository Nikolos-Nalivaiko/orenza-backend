<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Enums\MembershipStatus;
use App\Enums\WorkspaceType;
use App\Models\User;
use App\Models\Workspace;
use App\Repositories\Contracts\WorkspaceRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

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

    /**
     * @return Collection<int, Workspace>
     */
    public function listForUser(User $user): Collection
    {
        return $this->query()
            ->whereHas('memberships', function (Builder $query) use ($user): void {
                $query->where('user_id', $user->getKey())
                    ->where('status', MembershipStatus::Active);
            })
            ->orderBy('created_at')
            ->get();
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
