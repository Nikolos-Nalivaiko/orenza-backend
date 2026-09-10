<?php

declare(strict_types=1);

namespace App\Actions\Objects;

use App\Actions\Contracts\Action;
use App\Actions\Materials\CreateMaterialAction;
use App\Actions\Objects\Concerns\LinksClient;
use App\Actions\Payments\CreatePaymentAction;
use App\Actions\Services\CreateServiceAction;
use App\DataTransferObjects\Materials\MaterialData;
use App\DataTransferObjects\Objects\ObjectData;
use App\DataTransferObjects\Payments\PaymentData;
use App\DataTransferObjects\Services\ServiceData;
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
        private CreateServiceAction $createService,
        private CreatePaymentAction $createPayment,
    ) {}

    /**
     * @param  array<int, MaterialData>  $materials
     * @param  array<int, ServiceData>  $services
     * @param  array<int, PaymentData>  $payments
     */
    public function handle(
        Workspace $workspace,
        ObjectData $data,
        array $materials = [],
        array $services = [],
        array $payments = [],
    ): ConstructionObject {
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

        $object = DB::transaction(function () use ($attributes, $materials, $services, $payments): ConstructionObject {
            $object = $this->objects->create([
                ...$attributes,
                'public_token' => $this->freeToken(),
            ]);

            foreach ($materials as $material) {
                $this->createMaterial->handle($object, $material);
            }

            foreach ($services as $service) {
                $this->createService->handle($object, $service);
            }

            foreach ($payments as $payment) {
                $this->createPayment->handle($object, $payment);
            }

            return $object;
        });

        return $object->load(['client', 'materials', 'services.workers', 'payments']);
    }

    private function freeToken(): string
    {
        do {
            $token = ConstructionObject::newPublicToken();
        } while ($this->objects->tokenTaken($token));

        return $token;
    }
}
