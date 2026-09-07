<?php

declare(strict_types=1);

namespace App\Actions\Clients;

use App\Actions\Contracts\Action;
use App\Models\Client;
use App\Repositories\Contracts\ClientRepositoryInterface;

final readonly class DeleteClientAction implements Action
{
    public function __construct(private ClientRepositoryInterface $clients) {}

    public function handle(Client $client): bool
    {
        return $this->clients->delete($client);
    }
}
