<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\Materials\CreateMaterialAction;
use App\Actions\Materials\DeleteMaterialAction;
use App\Actions\Materials\SetMaterialsStatusAction;
use App\Actions\Materials\UpdateMaterialAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Materials\StatusMaterialsRequest;
use App\Http\Requests\Materials\StoreMaterialRequest;
use App\Http\Requests\Materials\UpdateMaterialRequest;
use App\Http\Resources\MaterialResource;
use App\Models\ConstructionObject;
use App\Models\Material;
use App\Models\Workspace;
use App\Repositories\Contracts\MaterialRepositoryInterface;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

final class MaterialController extends Controller
{
    public function __construct(
        private readonly MaterialRepositoryInterface $materials,
        private readonly CreateMaterialAction $createMaterial,
        private readonly UpdateMaterialAction $updateMaterial,
        private readonly DeleteMaterialAction $deleteMaterial,
        private readonly SetMaterialsStatusAction $setStatus,
    ) {}

    public function index(Workspace $workspace, ConstructionObject $object): JsonResponse
    {
        $this->authorize('view', $workspace);

        $materials = $this->materials->listForObject($object);

        return ApiResponse::success(
            MaterialResource::collection($materials)->resolve(),
            meta: ['total' => $materials->count()],
        );
    }

    public function store(
        StoreMaterialRequest $request,
        Workspace $workspace,
        ConstructionObject $object,
    ): JsonResponse {
        $this->authorize('view', $workspace);

        $material = $this->createMaterial->handle($object, $request->toData());

        return ApiResponse::created(new MaterialResource($material), __('messages.materials.created'));
    }

    public function update(
        UpdateMaterialRequest $request,
        Workspace $workspace,
        ConstructionObject $object,
        Material $material,
    ): JsonResponse {
        $this->authorize('view', $workspace);

        $material = $this->updateMaterial->handle($material, $request->toData());

        return ApiResponse::success(new MaterialResource($material), __('messages.materials.updated'));
    }

    public function destroy(
        Workspace $workspace,
        ConstructionObject $object,
        Material $material,
    ): JsonResponse {
        $this->authorize('view', $workspace);

        $this->deleteMaterial->handle($material);

        return ApiResponse::success(null, __('messages.materials.deleted'));
    }

    public function status(
        StatusMaterialsRequest $request,
        Workspace $workspace,
        ConstructionObject $object,
    ): JsonResponse {
        $this->authorize('view', $workspace);

        $materials = $this->setStatus->handle($object, $request->ids(), $request->status());

        return ApiResponse::success(
            MaterialResource::collection($materials)->resolve(),
            __('messages.materials.updated'),
        );
    }
}
