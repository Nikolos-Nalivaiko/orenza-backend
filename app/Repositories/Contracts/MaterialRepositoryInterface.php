<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Enums\MaterialStatus;
use App\Models\ConstructionObject;
use App\Models\Material;
use Illuminate\Database\Eloquent\Collection;

/**
 * @extends RepositoryInterface<Material>
 */
interface MaterialRepositoryInterface extends RepositoryInterface
{
    /**
     * @return Collection<int, Material>
     */
    public function listForObject(ConstructionObject $object): Collection;

    /**
     * @param  array<int, int>  $ids
     * @return Collection<int, Material>
     */
    public function ofObjectByIds(ConstructionObject $object, array $ids): Collection;

    /**
     * @param  array<int, int>  $ids
     */
    public function markStatus(ConstructionObject $object, array $ids, MaterialStatus $status): int;
}
