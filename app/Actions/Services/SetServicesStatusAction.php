<?php

declare(strict_types=1);

namespace App\Actions\Services;

use App\Actions\Contracts\Action;
use App\Enums\ServiceStatus;
use App\Models\ConstructionObject;
use App\Models\Service;
use App\Repositories\Contracts\ServiceRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

final readonly class SetServicesStatusAction implements Action
{
    public function __construct(private ServiceRepositoryInterface $services) {}

    /**
     * @param  array<int, int>  $ids
     * @return Collection<int, Service>
     */
    public function handle(ConstructionObject $object, array $ids, ServiceStatus $status): Collection
    {
        DB::transaction(fn () => $this->services->markStatus($object, $ids, $status));

        return $this->services->ofObjectByIds($object, $ids);
    }
}
