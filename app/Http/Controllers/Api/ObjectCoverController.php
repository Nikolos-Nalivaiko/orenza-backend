<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\Objects\RemoveObjectCoverAction;
use App\Actions\Objects\UpdateObjectCoverFocusAction;
use App\Actions\Objects\UploadObjectCoverAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Objects\UpdateCoverFocusRequest;
use App\Http\Requests\Objects\UploadCoverRequest;
use App\Http\Resources\ObjectResource;
use App\Models\ConstructionObject;
use App\Models\Workspace;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

final class ObjectCoverController extends Controller
{
    public function __construct(
        private readonly UploadObjectCoverAction $uploadCover,
        private readonly UpdateObjectCoverFocusAction $updateFocus,
        private readonly RemoveObjectCoverAction $removeCover,
    ) {}

    public function store(UploadCoverRequest $request, Workspace $workspace, ConstructionObject $object): JsonResponse
    {
        $this->authorize('view', $workspace);

        $object = $this->uploadCover->handle(
            $object,
            $request->cover()->getRealPath(),
            $request->focusX(),
            $request->focusY(),
        );

        return ApiResponse::success(new ObjectResource($object), __('messages.objects.cover_updated'));
    }

    public function update(UpdateCoverFocusRequest $request, Workspace $workspace, ConstructionObject $object): JsonResponse
    {
        $this->authorize('view', $workspace);

        $object = $this->updateFocus->handle($object, $request->focusX(), $request->focusY());

        return ApiResponse::success(new ObjectResource($object), __('messages.objects.updated'));
    }

    public function destroy(Workspace $workspace, ConstructionObject $object): JsonResponse
    {
        $this->authorize('view', $workspace);

        $object = $this->removeCover->handle($object);

        return ApiResponse::success(new ObjectResource($object), __('messages.objects.cover_removed'));
    }
}
