<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\Objects\DeleteObjectPhotoAction;
use App\Actions\Objects\UploadObjectPhotoAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Objects\StorePhotoRequest;
use App\Http\Resources\ObjectPhotoResource;
use App\Models\ConstructionObject;
use App\Models\ObjectPhoto;
use App\Models\User;
use App\Models\Workspace;
use App\Repositories\Contracts\ObjectPhotoRepositoryInterface;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

final class ObjectPhotoController extends Controller
{
    public function __construct(
        private readonly ObjectPhotoRepositoryInterface $photos,
        private readonly UploadObjectPhotoAction $uploadPhoto,
        private readonly DeleteObjectPhotoAction $deletePhoto,
    ) {}

    public function index(Workspace $workspace, ConstructionObject $object): JsonResponse
    {
        $this->authorize('view', $workspace);

        $photos = $this->photos->listForObject($object);

        return ApiResponse::success(
            ObjectPhotoResource::collection($photos)->resolve(),
            meta: [
                'total' => $photos->count(),
                'limit' => UploadObjectPhotoAction::MAX_PER_OBJECT,
            ],
        );
    }

    public function store(StorePhotoRequest $request, Workspace $workspace, ConstructionObject $object): JsonResponse
    {
        $this->authorize('view', $workspace);

        $user = $request->user();

        $photo = $this->uploadPhoto->handle(
            $object,
            $request->photo()->getRealPath(),
            $request->photo()->getClientOriginalName(),
            $user instanceof User ? $user : null,
            $request->takenAt(),
        );

        return ApiResponse::created(new ObjectPhotoResource($photo), __('messages.photos.created'));
    }

    public function destroy(Workspace $workspace, ConstructionObject $object, ObjectPhoto $photo): JsonResponse
    {
        $this->authorize('view', $workspace);

        $this->deletePhoto->handle($photo);

        return ApiResponse::success(null, __('messages.photos.deleted'));
    }
}
