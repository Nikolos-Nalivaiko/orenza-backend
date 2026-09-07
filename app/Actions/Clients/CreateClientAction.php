<?php

declare(strict_types=1);

namespace App\Actions\Clients;

use App\Actions\Contracts\Action;
use App\DataTransferObjects\Clients\ClientData;
use App\Enums\ClientType;
use App\Exceptions\BusinessRuleException;
use App\Models\Client;
use App\Models\Workspace;
use App\Repositories\Contracts\ClientRepositoryInterface;

final readonly class CreateClientAction implements Action
{
    public function __construct(private ClientRepositoryInterface $clients) {}

    public function handle(Workspace $workspace, ClientData $data): Client
    {
        $attributes = $data->toArray();
        $name = isset($attributes['name']) ? trim((string) $attributes['name']) : '';

        if ($name === '') {
            throw BusinessRuleException::make(__('messages.clients.name_required'), ['name' => null]);
        }

        $type = $attributes['type'] ?? ClientType::default();

        return $this->clients->create([
            ...$attributes,
            'workspace_id' => $workspace->getKey(),
            'type' => $type,
            'name' => $name,
            'contact' => $type === ClientType::Company ? ($attributes['contact'] ?? null) : null,
            'discount' => $attributes['discount'] ?? 0,
        ]);
    }
}
