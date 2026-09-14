<?php

declare(strict_types=1);

namespace App\Actions\Objects;

use App\Actions\Contracts\Action;
use App\Models\ConstructionObject;
use App\Repositories\Contracts\ObjectRepositoryInterface;
use App\Support\Media\CoverStorage;

final readonly class RemoveObjectCoverAction implements Action
{
    public function __construct(
        private ObjectRepositoryInterface $objects,
        private CoverStorage $storage,
    ) {}

    public function handle(ConstructionObject $object): ConstructionObject
    {
        $cover = $object->cover;

        if ($cover !== null) {
            $object = $this->objects->update($object, ['cover' => null]);

            $this->storage->purgeLater($object->id, $cover);
        }

        return $object->load(['client', 'materials', 'services.workers', 'payments']);
    }
}
