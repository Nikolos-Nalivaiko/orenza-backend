<?php

declare(strict_types=1);

namespace App\Actions\Workspaces;

use App\Actions\Contracts\Action;
use App\DataTransferObjects\Workspaces\WorkspaceData;
use App\Enums\MembershipRole;
use App\Enums\MembershipStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\User;
use App\Models\Workspace;
use App\Repositories\Contracts\WorkspaceRepositoryInterface;
use Illuminate\Support\Facades\DB;

final readonly class CreateWorkspaceAction implements Action
{
    public function __construct(
        private WorkspaceRepositoryInterface $workspaces,
        private GenerateWorkspaceSlugAction $generateSlug,
    ) {}

    public function handle(User $owner, WorkspaceData $data): Workspace
    {
        if ($data->type->isPersonal() && $this->workspaces->personalExistsFor($owner)) {
            throw BusinessRuleException::make(
                __('messages.workspaces.personal_exists'),
                ['type' => $data->type->value],
            );
        }

        $name = $data->name ?? ($data->type->isPersonal() ? $owner->full_name : null);

        if ($name === null || $name === '') {
            throw BusinessRuleException::make(__('messages.workspaces.name_required'), ['name' => null]);
        }

        $slug = $this->generateSlug->handle($name, $data->slug);

        return DB::transaction(function () use ($owner, $data, $name, $slug): Workspace {
            $workspace = $this->workspaces->create([
                'type' => $data->type,
                'name' => $name,
                'slug' => $slug,
                'owner_id' => $owner->getKey(),
            ]);

            $workspace->memberships()->create([
                'user_id' => $owner->getKey(),
                'role' => MembershipRole::Owner,
                'status' => MembershipStatus::Active,
            ]);

            if ($owner->current_workspace_id === null) {
                $owner->forceFill(['current_workspace_id' => $workspace->getKey()])->save();
            }

            return $workspace;
        });
    }
}
