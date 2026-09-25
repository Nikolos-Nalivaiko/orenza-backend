<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\Objects\DeleteObjectPhotoAction;
use App\Actions\Objects\UploadObjectPhotoAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Objects\ListPhotosRequest;
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

    public function index(ListPhotosRequest $request, Workspace $workspace, ConstructionObject $object): JsonResponse
    {
        $this->authorize('view', $workspace);

        $perPage = $request->perPage();
        $found = $this->photos->pageForObject($object, $perPage + 1, $request->cursor());
        $photos = $found->take($perPage);
        $last = $photos->last();

        return ApiResponse::success(
            ObjectPhotoResource::collection($photos)->resolve(),
            meta: [
                'per_page' => $perPage,
                'next_cursor' => $found->count() > $perPage && $last instanceof ObjectPhoto
                    ? $last->position()->encode()
                    : null,
                'total' => $this->photos->countForObject($object),
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
