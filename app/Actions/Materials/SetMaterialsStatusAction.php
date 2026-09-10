<?php

declare(strict_types=1);

namespace App\Actions\Materials;

use App\Actions\Contracts\Action;
use App\Enums\MaterialStatus;
use App\Models\ConstructionObject;
use App\Repositories\Contracts\MaterialRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

final readonly class SetMaterialsStatusAction implements Action
{
    public function __construct(private MaterialRepositoryInterface $materials) {}

    /**
     * @param  array<int, int>  $ids
     * @return Collection<int, \App\Models\Material>
     */
    public function handle(ConstructionObject $object, array $ids, MaterialStatus $status): Collection
    {
        DB::transaction(fn () => $this->materials->markStatus($object, $ids, $status));

        return $this->materials->ofObjectByIds($object, $ids);
    }
}
