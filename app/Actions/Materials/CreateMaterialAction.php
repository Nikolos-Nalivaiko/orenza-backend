<?php

declare(strict_types=1);

namespace App\Actions\Materials;

use App\Actions\Contracts\Action;
use App\DataTransferObjects\Materials\MaterialData;
use App\Enums\MaterialBuyer;
use App\Enums\MaterialStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\ConstructionObject;
use App\Models\Material;
use App\Repositories\Contracts\MaterialRepositoryInterface;

final readonly class CreateMaterialAction implements Action
{
    use Concerns\PricesTheBuyer;

    public function __construct(private MaterialRepositoryInterface $materials) {}

    public function handle(ConstructionObject $object, MaterialData $data): Material
    {
        $attributes = $data->toArray();
        $name = trim((string) ($attributes['name'] ?? ''));

        if ($name === '') {
            throw BusinessRuleException::make(__('messages.materials.name_required'), ['name' => null]);
        }

        $buyer = $attributes['buyer'] ?? MaterialBuyer::default();

        return $this->materials->create([
            ...$attributes,
            ...$this->prices($buyer, $attributes),
            'construction_object_id' => $object->getKey(),
            'name' => $name,
            'buyer' => $buyer,
            'quantity' => $attributes['quantity'] ?? 0,
            'status' => $attributes['status'] ?? MaterialStatus::default(),
            'approved_by_client' => $attributes['approved_by_client'] ?? false,
        ]);
    }
}
