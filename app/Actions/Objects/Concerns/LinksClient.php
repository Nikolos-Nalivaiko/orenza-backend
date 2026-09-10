<?php

declare(strict_types=1);

namespace App\Actions\Objects\Concerns;

use App\Exceptions\BusinessRuleException;
use App\Repositories\Contracts\ClientRepositoryInterface;

/**
 * @property-read ClientRepositoryInterface $clients
 */
trait LinksClient
{
    private function clientFor(int $workspaceId, ?int $clientId): ?int
    {
        if ($clientId === null) {
            return null;
        }

        $client = $this->clients->find($clientId);

        if ($client === null || $client->workspace_id !== $workspaceId) {
            throw BusinessRuleException::make(
                __('messages.objects.client_not_found'),
                ['client_id' => null],
            );
        }

        return $client->getKey();
    }
}
