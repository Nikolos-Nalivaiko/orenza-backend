<?php

declare(strict_types=1);

namespace App\Actions\Objects;

use App\Actions\Contracts\Action;
use App\Actions\Objects\Concerns\LinksClient;
use App\DataTransferObjects\Objects\ObjectData;
use App\Exceptions\BusinessRuleException;
use App\Models\ConstructionObject;
use App\Repositories\Contracts\ClientRepositoryInterface;
use App\Repositories\Contracts\ObjectRepositoryInterface;

final readonly class UpdateObjectAction implements Action
{
    use LinksClient;

    public function __construct(
        private ObjectRepositoryInterface $objects,
        private ClientRepositoryInterface $clients,
    ) {}

    public function handle(ConstructionObject $object, ObjectData $data): ConstructionObject
    {
        $attributes = $data->toArray();

        foreach (['name' => 'name_required', 'address' => 'address_required'] as $field => $message) {
            if (! array_key_exists($field, $attributes)) {
                continue;
            }

            $value = trim((string) $attributes[$field]);

            if ($value === '') {
                throw BusinessRuleException::make(__("messages.objects.{$message}"), [$field => null]);
            }

            $attributes[$field] = $value;
        }

        if (array_key_exists('client_id', $attributes)) {
            $attributes['client_id'] = $this->clientFor($object->workspace_id, $attributes['client_id']);
        }

        if (array_key_exists('archived', $attributes)) {
            $archived = (bool) $attributes['archived'];

            unset($attributes['archived']);

            if ($archived !== $object->isArchived()) {
                $attributes['archived_at'] = $archived ? now() : null;
            }
        }

        if ($attributes === []) {
            return $object->load(['client', 'materials', 'services.workers']);
        }

        return $this->objects->update($object, $attributes)->load(['client', 'materials', 'services.workers']);
    }
}
