<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Membership;
use App\Models\User;
use App\Models\Workspace;

final class WorkspacePolicy
{
    public function view(User $user, Workspace $workspace): bool
    {
        return $this->activeMembership($user, $workspace) instanceof Membership;
    }

    private function activeMembership(User $user, Workspace $workspace): ?Membership
    {
        $membership = $workspace->membershipFor($user);

        return $membership instanceof Membership && $membership->isActive() ? $membership : null;
    }
}
