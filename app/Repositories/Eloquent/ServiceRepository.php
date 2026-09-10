<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Enums\ServiceStatus;
use App\Models\ConstructionObject;
use App\Models\Service;
use App\Repositories\Contracts\ServiceRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

/**
 * @extends BaseRepository<Service>
 */
final class ServiceRepository extends BaseRepository implements ServiceRepositoryInterface
{
    /**
     * @return class-string<Service>
     */
    protected function model(): string
    {
        return Service::class;
    }

    /**
     * @return Collection<int, Service>
     */
    public function listForObject(ConstructionObject $object): Collection
    {
        return $this->query()->with('workers')->ofObject($object)->orderBy('id')->get();
    }

    /**
     * @param  array<int, int>  $ids
     * @return Collection<int, Service>
     */
    public function ofObjectByIds(ConstructionObject $object, array $ids): Collection
    {
        return $this->query()
            ->with('workers')
            ->ofObject($object)
            ->whereIn('id', $ids)
            ->orderBy('id')
            ->get();
    }

    /**
     * @param  array<int, int>  $ids
     */
    public function markStatus(ConstructionObject $object, array $ids, ServiceStatus $status): int
    {
        return $this->query()
            ->ofObject($object)
            ->whereIn('id', $ids)
            ->where('status', '!=', $status)
            ->update(['status' => $status, 'updated_at' => now()]);
    }
}
