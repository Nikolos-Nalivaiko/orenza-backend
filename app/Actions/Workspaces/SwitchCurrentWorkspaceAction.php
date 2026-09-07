<?php

declare(strict_types=1);

namespace App\Actions\Workspaces;

use App\Actions\Contracts\Action;
use App\Exceptions\BusinessRuleException;
use App\Models\User;
use App\Models\Workspace;

final readonly class SwitchCurrentWorkspaceAction implements Action
{
    public function handle(User $user, Workspace $workspace): User
    {
        $membership = $workspace->membershipFor($user);

        if ($membership === null || ! $membership->isActive()) {
            throw BusinessRuleException::make(
                __('messages.workspaces.not_a_member'),
                ['workspace_id' => $workspace->getKey()],
            );
        }

        $user->forceFill(['current_workspace_id' => $workspace->getKey()])->save();

        $membership->forceFill(['last_active_at' => now()])->save();

        return $user->refresh();
    }
}
