<?php

declare(strict_types=1);

namespace App\Actions\Materials;

use App\Actions\Contracts\Action;
use App\Models\Material;
use App\Repositories\Contracts\MaterialRepositoryInterface;

final readonly class DeleteMaterialAction implements Action
{
    public function __construct(private MaterialRepositoryInterface $materials) {}

    public function handle(Material $material): bool
    {
        return $this->materials->delete($material);
    }
}
