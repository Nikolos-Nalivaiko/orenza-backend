<?php

declare(strict_types=1);

namespace App\Actions\Objects;

use App\Actions\Contracts\Action;
use App\Models\ObjectPhoto;
use App\Repositories\Contracts\ObjectPhotoRepositoryInterface;
use App\Support\Media\PhotoStorage;

final readonly class DeleteObjectPhotoAction implements Action
{
    public function __construct(
        private ObjectPhotoRepositoryInterface $photos,
        private PhotoStorage $storage,
    ) {}

    public function handle(ObjectPhoto $photo): bool
    {
        $deleted = $this->photos->delete($photo);

        if ($deleted) {
            $this->storage->purgeLater([$photo]);
        }

        return $deleted;
    }
}
