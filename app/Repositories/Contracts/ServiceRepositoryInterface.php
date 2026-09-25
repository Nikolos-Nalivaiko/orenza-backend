<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Enums\ServiceStatus;
use App\Models\ConstructionObject;
use App\Models\Service;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * @extends RepositoryInterface<Service>
 */
interface ServiceRepositoryInterface extends RepositoryInterface
{
    /**
     * @return LengthAwarePaginator<int, Service>
     */
    public function paginateForObject(ConstructionObject $object, int $perPage): LengthAwarePaginator;

    /**
     * @param  array<int, int>  $ids
     * @return Collection<int, Service>
     */
    public function ofObjectByIds(ConstructionObject $object, array $ids): Collection;

    /**
     * @param  array<int, int>  $ids
     */
    public function markStatus(ConstructionObject $object, array $ids, ServiceStatus $status): int;
}
