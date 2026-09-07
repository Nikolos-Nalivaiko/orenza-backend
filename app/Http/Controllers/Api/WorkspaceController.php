<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\Workspaces\CreateWorkspaceAction;
use App\Actions\Workspaces\SwitchCurrentWorkspaceAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Workspaces\StoreWorkspaceRequest;
use App\Http\Resources\UserResource;
use App\Http\Resources\WorkspaceResource;
use App\Models\User;
use App\Models\Workspace;
use App\Repositories\Contracts\WorkspaceRepositoryInterface;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class WorkspaceController extends Controller
{
    public function __construct(
        private readonly WorkspaceRepositoryInterface $workspaces,
        private readonly CreateWorkspaceAction $createWorkspace,
        private readonly SwitchCurrentWorkspaceAction $switchCurrent,
    ) {}

    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $workspaces = $this->workspaces->listForUser($user);

        return ApiResponse::success(
            WorkspaceResource::collection($workspaces)->resolve(),
            meta: ['current_workspace_id' => $user->current_workspace_id],
        );
    }

    public function store(StoreWorkspaceRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $workspace = $this->createWorkspace->handle($user, $request->toData());

        return ApiResponse::created(new WorkspaceResource($workspace), __('messages.workspaces.created'));
    }

    public function show(Workspace $workspace): JsonResponse
    {
        $this->authorize('view', $workspace);

        return ApiResponse::success(new WorkspaceResource($workspace));
    }

    public function switch(Request $request, Workspace $workspace): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $this->authorize('view', $workspace);

        $user = $this->switchCurrent->handle($user, $workspace);

        return ApiResponse::success([
            'workspace' => new WorkspaceResource($workspace),
            'user' => new UserResource($user),
        ], __('messages.workspaces.switched'));
    }
}
