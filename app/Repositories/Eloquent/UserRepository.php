<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;

/**
 * @extends BaseRepository<User>
 */
final class UserRepository extends BaseRepository implements UserRepositoryInterface
{
    /**
     * @return class-string<User>
     */
    protected function model(): string
    {
        return User::class;
    }

    public function findByEmail(string $email): ?User
    {
        return $this->query()->where('email', mb_strtolower($email))->first();
    }

    public function emailExists(string $email, ?int $exceptId = null): bool
    {
        return $this->query()
            ->where('email', mb_strtolower($email))
            ->when($exceptId, static fn (Builder $query, int $id) => $query->whereKeyNot($id))
            ->exists();
    }

    public function phoneExists(string $phone, ?int $exceptId = null): bool
    {
        return $this->query()
            ->where('phone', $phone)
            ->when($exceptId, static fn (Builder $query, int $id) => $query->whereKeyNot($id))
            ->exists();
    }
}
