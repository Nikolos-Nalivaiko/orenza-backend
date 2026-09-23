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
        if (! $workspace->isOwnedBy($user)) {
            throw BusinessRuleException::make(
                __('messages.workspaces.not_owned'),
                ['workspace_id' => $workspace->getKey()],
            );
        }

        $user->forceFill(['current_workspace_id' => $workspace->getKey()])->save();

        return $user->refresh();
    }
}
