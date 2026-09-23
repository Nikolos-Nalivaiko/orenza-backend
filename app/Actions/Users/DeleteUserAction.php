<?php

declare(strict_types=1);

namespace App\Actions\Users;

use App\Actions\Contracts\Action;
use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Repositories\Contracts\WorkspaceRepositoryInterface;
use App\Support\Media\MediaStorage;
use App\Support\Media\WorkspaceMedia;
use Illuminate\Support\Facades\DB;

final readonly class DeleteUserAction implements Action
{
    public function __construct(
        private UserRepositoryInterface $users,
        private WorkspaceRepositoryInterface $workspaces,
        private WorkspaceMedia $workspaceMedia,
        private MediaStorage $media,
    ) {}

    public function handle(User $user): void
    {
        $owned = $this->workspaces->listOwnedBy($user);
        $paths = $this->workspaceMedia->paths($owned);

        DB::transaction(function () use ($user, $owned): void {
            $user->tokens()->delete();

            foreach ($owned as $workspace) {
                $this->workspaces->forceDelete($workspace);
            }

            $this->users->delete($user);
        });

        $this->media->deleteLater($paths);
    }
}
