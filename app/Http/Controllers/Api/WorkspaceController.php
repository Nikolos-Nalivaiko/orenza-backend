<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\Workspaces\CreateWorkspaceAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Workspaces\StoreWorkspaceRequest;
use App\Http\Resources\WorkspaceResource;
use App\Models\User;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

final class WorkspaceController extends Controller
{
    public function __construct(private readonly CreateWorkspaceAction $createWorkspace) {}

    public function store(StoreWorkspaceRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $workspace = $this->createWorkspace->handle($user, $request->toData());

        return ApiResponse::created(new WorkspaceResource($workspace), 'Workspace created.');
    }
}
