<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Models\ConstructionObject;
use App\Models\ObjectPhoto;
use App\Repositories\Contracts\ObjectPhotoRepositoryInterface;
use App\Support\MomentCursor;
use Illuminate\Database\Eloquent\Collection;

/**
 * @extends BaseRepository<ObjectPhoto>
 */
final class ObjectPhotoRepository extends BaseRepository implements ObjectPhotoRepositoryInterface
{
    /**
     * @return class-string<ObjectPhoto>
     */
    protected function model(): string
    {
        return ObjectPhoto::class;
    }

    /**
     * @return Collection<int, ObjectPhoto>
     */
    public function listForObject(ConstructionObject $object): Collection
    {
        return $this->query()->ofObject($object)->newestFirst()->get();
    }

    /**
     * @return Collection<int, ObjectPhoto>
     */
    public function pageForObject(ConstructionObject $object, int $limit, ?MomentCursor $after = null): Collection
    {
        $query = $this->query()->ofObject($object);

        if ($after instanceof MomentCursor) {
            $query->afterPosition($after);
        }

        return $query->newestFirst()->limit($limit)->get();
    }

    public function countForObject(ConstructionObject $object): int
    {
        return $this->query()->ofObject($object)->count();
    }

    public function deleteForObject(ConstructionObject $object): void
    {
        $this->query()->ofObject($object)->delete();
    }
}
