<?php

declare(strict_types=1);

namespace App\Actions\Objects;

use App\Actions\Contracts\Action;
use App\Models\ConstructionObject;
use App\Repositories\Contracts\ObjectPhotoRepositoryInterface;
use App\Repositories\Contracts\ObjectRepositoryInterface;
use App\Support\Media\PhotoStorage;

final readonly class DeleteObjectAction implements Action
{
    public function __construct(
        private ObjectRepositoryInterface $objects,
        private ObjectPhotoRepositoryInterface $photos,
        private PhotoStorage $photoFiles,
    ) {}

    public function handle(ConstructionObject $object): bool
    {
        $photos = $this->photos->listForObject($object);

        $deleted = $this->objects->delete($object);

        if (! $deleted) {
            return false;
        }

        if ($photos->isNotEmpty()) {
            $this->photos->deleteForObject($object);
            $this->photoFiles->purgeLater($photos);
        }

        return true;
    }
}
