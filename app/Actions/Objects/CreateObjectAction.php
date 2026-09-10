<?php

declare(strict_types=1);

namespace App\Actions\Objects;

use App\Actions\Contracts\Action;
use App\Actions\Materials\CreateMaterialAction;
use App\Actions\Objects\Concerns\LinksClient;
use App\DataTransferObjects\Materials\MaterialData;
use App\DataTransferObjects\Objects\ObjectData;
use App\Enums\ObjectStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\ConstructionObject;
use App\Models\Workspace;
use App\Repositories\Contracts\ClientRepositoryInterface;
use App\Repositories\Contracts\ObjectRepositoryInterface;
use Illuminate\Support\Facades\DB;

final readonly class CreateObjectAction implements Action
{
    use LinksClient;

    public function __construct(
        private ObjectRepositoryInterface $objects,
        private ClientRepositoryInterface $clients,
        private CreateMaterialAction $createMaterial,
    ) {}

    /**
     * @param  array<int, MaterialData>  $materials
     */
    public function handle(Workspace $workspace, ObjectData $data, array $materials = []): ConstructionObject
    {
        $attributes = $data->toArray();

        $name = trim((string) ($attributes['name'] ?? ''));
        $address = trim((string) ($attributes['address'] ?? ''));

        if ($name === '') {
            throw BusinessRuleException::make(__('messages.objects.name_required'), ['name' => null]);
        }

        if ($address === '') {
            throw BusinessRuleException::make(__('messages.objects.address_required'), ['address' => null]);
        }

        unset($attributes['archived']);

        $attributes = [
            ...$attributes,
            'workspace_id' => $workspace->getKey(),
            'name' => $name,
            'address' => $address,
            'status' => $attributes['status'] ?? ObjectStatus::default(),
            'client_id' => $this->clientFor($workspace->getKey(), $attributes['client_id'] ?? null),
        ];

        $object = DB::transaction(function () use ($attributes, $materials): ConstructionObject {
            $object = $this->objects->create([
                ...$attributes,
                'public_token' => $this->freeToken(),
            ]);

            foreach ($materials as $material) {
                $this->createMaterial->handle($object, $material);
            }

            return $object;
        });

        return $object->load(['client', 'materials']);
    }

    private function freeToken(): string
    {
        do {
            $token = ConstructionObject::newPublicToken();
        } while ($this->objects->tokenTaken($token));

        return $token;
    }
}
