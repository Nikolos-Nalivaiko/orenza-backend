<?php

declare(strict_types=1);

namespace App\Actions\Materials;

use App\Actions\Contracts\Action;
use App\DataTransferObjects\Materials\MaterialData;
use App\Exceptions\BusinessRuleException;
use App\Models\Material;
use App\Repositories\Contracts\MaterialRepositoryInterface;

final readonly class UpdateMaterialAction implements Action
{
    use Concerns\PricesTheBuyer;

    public function __construct(private MaterialRepositoryInterface $materials) {}

    public function handle(Material $material, MaterialData $data): Material
    {
        $attributes = $data->toArray();

        if (array_key_exists('name', $attributes)) {
            $name = trim((string) $attributes['name']);

            if ($name === '') {
                throw BusinessRuleException::make(__('messages.materials.name_required'), ['name' => null]);
            }

            $attributes['name'] = $name;
        }

        $buyer = $attributes['buyer'] ?? $material->buyer;

        $attributes = [...$attributes, ...$this->prices($buyer, $attributes)];

        if ($attributes === []) {
            return $material;
        }

        return $this->materials->update($material, $attributes);
    }
}
