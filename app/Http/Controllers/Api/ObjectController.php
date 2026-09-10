<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\Objects\CreateObjectAction;
use App\Actions\Objects\DeleteObjectAction;
use App\Actions\Objects\UpdateObjectAction;
use App\Enums\ObjectStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Objects\StoreObjectRequest;
use App\Http\Requests\Objects\UpdateObjectRequest;
use App\Http\Resources\ObjectResource;
use App\Models\ConstructionObject;
use App\Models\Workspace;
use App\Repositories\Contracts\ObjectRepositoryInterface;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ObjectController extends Controller
{
    public function __construct(
        private readonly ObjectRepositoryInterface $objects,
        private readonly CreateObjectAction $createObject,
        private readonly UpdateObjectAction $updateObject,
        private readonly DeleteObjectAction $deleteObject,
    ) {}

    public function index(Request $request, Workspace $workspace): JsonResponse
    {
        $this->authorize('view', $workspace);

        $status = $request->query('status');
        $search = $request->query('search');
        $withArchived = $request->boolean('archived');

        $objects = $this->objects->listForWorkspace(
            $workspace,
            $search === null ? null : (string) $search,
            is_string($status) ? ObjectStatus::tryFrom($status) : null,
            $withArchived,
        );

        return ApiResponse::success(
            ObjectResource::collection($objects)->resolve(),
            meta: [
                'total' => $objects->count(),
                'all' => $this->objects->countForWorkspace($workspace, true),
            ],
        );
    }

    public function store(StoreObjectRequest $request, Workspace $workspace): JsonResponse
    {
        $this->authorize('view', $workspace);

        $object = $this->createObject->handle($workspace, $request->toData(), $request->materials());

        return ApiResponse::created(new ObjectResource($object), __('messages.objects.created'));
    }

    public function show(Workspace $workspace, ConstructionObject $object): JsonResponse
    {
        $this->authorize('view', $workspace);

        return ApiResponse::success(new ObjectResource($object->load(['client', 'materials'])));
    }

    public function update(
        UpdateObjectRequest $request,
        Workspace $workspace,
        ConstructionObject $object,
    ): JsonResponse {
        $this->authorize('view', $workspace);

        $object = $this->updateObject->handle($object, $request->toData());

        return ApiResponse::success(new ObjectResource($object), __('messages.objects.updated'));
    }

    public function destroy(Workspace $workspace, ConstructionObject $object): JsonResponse
    {
        $this->authorize('view', $workspace);

        $this->deleteObject->handle($object);

        return ApiResponse::success(null, __('messages.objects.deleted'));
    }
}
