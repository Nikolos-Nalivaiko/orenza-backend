<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Models\Workspace;

final class WorkspacePolicy
{
    public function view(User $user, Workspace $workspace): bool
    {
        return $workspace->isOwnedBy($user);
    }

    public function update(User $user, Workspace $workspace): bool
    {
        return $this->view($user, $workspace);
    }

    public function delete(User $user, Workspace $workspace): bool
    {
        return $this->view($user, $workspace);
    }

    public function manageTeam(User $user, Workspace $workspace): bool
    {
        return $this->view($user, $workspace) && $workspace->type->hasTeam();
    }
}
