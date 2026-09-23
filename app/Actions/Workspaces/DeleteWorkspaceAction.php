<?php

declare(strict_types=1);

namespace App\Actions\Workspaces;

use App\Actions\Contracts\Action;
use App\Models\Workspace;
use App\Repositories\Contracts\WorkspaceRepositoryInterface;
use App\Support\Media\MediaStorage;
use App\Support\Media\WorkspaceMedia;
use Illuminate\Support\Facades\DB;

final readonly class DeleteWorkspaceAction implements Action
{
    public function __construct(
        private WorkspaceRepositoryInterface $workspaces,
        private WorkspaceMedia $workspaceMedia,
        private MediaStorage $media,
    ) {}

    public function handle(Workspace $workspace): bool
    {
        $paths = $this->workspaceMedia->paths([$workspace]);

        $deleted = DB::transaction(fn (): bool => $this->workspaces->forceDelete($workspace));

        if ($deleted) {
            $this->media->deleteLater($paths);
        }

        return $deleted;
    }
}
