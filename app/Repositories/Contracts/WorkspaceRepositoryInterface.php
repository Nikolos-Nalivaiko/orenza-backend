<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\User;
use App\Models\Workspace;

/**
 * @extends RepositoryInterface<Workspace>
 */
interface WorkspaceRepositoryInterface extends RepositoryInterface
{
    public function findBySlug(string $slug): ?Workspace;

    public function slugExists(string $slug): bool;

    public function personalExistsFor(User $user): bool;
}
