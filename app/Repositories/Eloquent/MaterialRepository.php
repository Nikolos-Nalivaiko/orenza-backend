<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Enums\MaterialStatus;
use App\Models\ConstructionObject;
use App\Models\Material;
use App\Repositories\Contracts\MaterialRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

/**
 * @extends BaseRepository<Material>
 */
final class MaterialRepository extends BaseRepository implements MaterialRepositoryInterface
{
    /**
     * @return class-string<Material>
     */
    protected function model(): string
    {
        return Material::class;
    }

    /**
     * @return Collection<int, Material>
     */
    public function listForObject(ConstructionObject $object): Collection
    {
        return $this->query()->ofObject($object)->orderBy('id')->get();
    }

    /**
     * @param  array<int, int>  $ids
     * @return Collection<int, Material>
     */
    public function ofObjectByIds(ConstructionObject $object, array $ids): Collection
    {
        return $this->query()->ofObject($object)->whereIn('id', $ids)->orderBy('id')->get();
    }

    /**
     * @param  array<int, int>  $ids
     */
    public function markStatus(ConstructionObject $object, array $ids, MaterialStatus $status): int
    {
        return $this->query()
            ->ofObject($object)
            ->whereIn('id', $ids)
            ->where('status', '!=', $status)
            ->update(['status' => $status, 'updated_at' => now()]);
    }
}
