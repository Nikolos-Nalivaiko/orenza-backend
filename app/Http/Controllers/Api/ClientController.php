<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\Clients\CreateClientAction;
use App\Actions\Clients\DeleteClientAction;
use App\Actions\Clients\UpdateClientAction;
use App\Enums\ClientType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Clients\StoreClientRequest;
use App\Http\Requests\Clients\UpdateClientRequest;
use App\Http\Resources\ClientResource;
use App\Models\Client;
use App\Models\Workspace;
use App\Repositories\Contracts\ClientRepositoryInterface;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ClientController extends Controller
{
    public function __construct(
        private readonly ClientRepositoryInterface $clients,
        private readonly CreateClientAction $createClient,
        private readonly UpdateClientAction $updateClient,
        private readonly DeleteClientAction $deleteClient,
    ) {}

    public function index(Request $request, Workspace $workspace): JsonResponse
    {
        $this->authorize('view', $workspace);

        $type = $request->query('type');

        $clients = $this->clients->listForWorkspace(
            $workspace,
            $request->query('search') === null ? null : (string) $request->query('search'),
            is_string($type) ? ClientType::tryFrom($type) : null,
        );

        return ApiResponse::success(
            ClientResource::collection($clients)->resolve(),
            meta: ['total' => $clients->count()],
        );
    }

    public function store(StoreClientRequest $request, Workspace $workspace): JsonResponse
    {
        $this->authorize('view', $workspace);

        $client = $this->createClient->handle($workspace, $request->toData());

        return ApiResponse::created(new ClientResource($client), __('messages.clients.created'));
    }

    public function show(Workspace $workspace, Client $client): JsonResponse
    {
        $this->authorize('view', $workspace);

        return ApiResponse::success(new ClientResource($client));
    }

    public function update(UpdateClientRequest $request, Workspace $workspace, Client $client): JsonResponse
    {
        $this->authorize('view', $workspace);

        $client = $this->updateClient->handle($client, $request->toData());

        return ApiResponse::success(new ClientResource($client), __('messages.clients.updated'));
    }

    public function destroy(Workspace $workspace, Client $client): JsonResponse
    {
        $this->authorize('view', $workspace);

        $this->deleteClient->handle($client);

        return ApiResponse::success(null, __('messages.clients.deleted'));
    }
}
