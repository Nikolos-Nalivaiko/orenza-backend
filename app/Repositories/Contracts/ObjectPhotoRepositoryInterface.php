<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\ConstructionObject;
use App\Models\ObjectPhoto;
use Illuminate\Database\Eloquent\Collection;

/**
 * @extends RepositoryInterface<ObjectPhoto>
 */
interface ObjectPhotoRepositoryInterface extends RepositoryInterface
{
    /**
     * @return Collection<int, ObjectPhoto>
     */
    public function listForObject(ConstructionObject $object): Collection;

    public function countForObject(ConstructionObject $object): int;

    public function deleteForObject(ConstructionObject $object): void;
}
