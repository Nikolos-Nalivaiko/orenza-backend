<?php

declare(strict_types=1);

namespace App\Actions\Clients;

use App\Actions\Contracts\Action;
use App\DataTransferObjects\Clients\ClientData;
use App\Enums\ClientType;
use App\Exceptions\BusinessRuleException;
use App\Models\Client;
use App\Repositories\Contracts\ClientRepositoryInterface;

final readonly class UpdateClientAction implements Action
{
    public function __construct(private ClientRepositoryInterface $clients) {}

    public function handle(Client $client, ClientData $data): Client
    {
        $attributes = $data->toArray();

        if (array_key_exists('name', $attributes)) {
            $name = trim((string) $attributes['name']);

            if ($name === '') {
                throw BusinessRuleException::make(__('messages.clients.name_required'), ['name' => null]);
            }

            $attributes['name'] = $name;
        }

        $type = $attributes['type'] ?? $client->type;

        if ($type === ClientType::Person) {
            $attributes['contact'] = null;
        }

        if ($attributes === []) {
            return $client;
        }

        return $this->clients->update($client, $attributes);
    }
}
