<?php

declare(strict_types=1);

namespace App\Actions\Workspaces;

use App\Actions\Contracts\Action;
use App\Exceptions\BusinessRuleException;
use App\Models\Workspace;
use App\Repositories\Contracts\WorkspaceRepositoryInterface;

final readonly class UpdateWorkspaceAction implements Action
{
    public function __construct(private WorkspaceRepositoryInterface $workspaces) {}

    public function handle(Workspace $workspace, string $name): Workspace
    {
        $name = trim($name);

        if ($name === '') {
            throw BusinessRuleException::make(__('messages.workspaces.name_empty'), ['name' => null]);
        }

        if ($name === $workspace->name) {
            return $workspace;
        }

        return $this->workspaces->update($workspace, ['name' => $name]);
    }
}
