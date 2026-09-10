<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\Services\CreateServiceAction;
use App\Actions\Services\DeleteServiceAction;
use App\Actions\Services\SetServicesStatusAction;
use App\Actions\Services\UpdateServiceAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Services\StatusServicesRequest;
use App\Http\Requests\Services\StoreServiceRequest;
use App\Http\Requests\Services\UpdateServiceRequest;
use App\Http\Resources\ServiceResource;
use App\Models\ConstructionObject;
use App\Models\Service;
use App\Models\Workspace;
use App\Repositories\Contracts\ServiceRepositoryInterface;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

final class ServiceController extends Controller
{
    public function __construct(
        private readonly ServiceRepositoryInterface $services,
        private readonly CreateServiceAction $createService,
        private readonly UpdateServiceAction $updateService,
        private readonly DeleteServiceAction $deleteService,
        private readonly SetServicesStatusAction $setStatus,
    ) {}

    public function index(Workspace $workspace, ConstructionObject $object): JsonResponse
    {
        $this->authorize('view', $workspace);

        $services = $this->services->listForObject($object);

        return ApiResponse::success(
            ServiceResource::collection($services)->resolve(),
            meta: ['total' => $services->count()],
        );
    }

    public function store(
        StoreServiceRequest $request,
        Workspace $workspace,
        ConstructionObject $object,
    ): JsonResponse {
        $this->authorize('view', $workspace);

        $service = $this->createService->handle($object, $request->toData());

        return ApiResponse::created(new ServiceResource($service), __('messages.services.created'));
    }

    public function update(
        UpdateServiceRequest $request,
        Workspace $workspace,
        ConstructionObject $object,
        Service $service,
    ): JsonResponse {
        $this->authorize('view', $workspace);

        $service = $this->updateService->handle($service, $request->toData());

        return ApiResponse::success(new ServiceResource($service), __('messages.services.updated'));
    }

    public function destroy(
        Workspace $workspace,
        ConstructionObject $object,
        Service $service,
    ): JsonResponse {
        $this->authorize('view', $workspace);

        $this->deleteService->handle($service);

        return ApiResponse::success(null, __('messages.services.deleted'));
    }

    public function status(
        StatusServicesRequest $request,
        Workspace $workspace,
        ConstructionObject $object,
    ): JsonResponse {
        $this->authorize('view', $workspace);

        $services = $this->setStatus->handle($object, $request->ids(), $request->status());

        return ApiResponse::success(
            ServiceResource::collection($services)->resolve(),
            __('messages.services.updated'),
        );
    }
}
