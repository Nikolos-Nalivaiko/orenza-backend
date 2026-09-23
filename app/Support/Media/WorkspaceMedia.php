<?php

declare(strict_types=1);

namespace App\Support\Media;

use App\Models\Workspace;
use App\Repositories\Contracts\ObjectRepositoryInterface;

final readonly class WorkspaceMedia
{
    public function __construct(
        private ObjectRepositoryInterface $objects,
        private CoverStorage $covers,
        private PhotoStorage $photos,
    ) {}

    /**
     * @param  iterable<Workspace>  $workspaces
     * @return list<string>
     */
    public function paths(iterable $workspaces): array
    {
        $ids = [];

        foreach ($workspaces as $workspace) {
            $ids[] = (int) $workspace->getKey();
        }

        if ($ids === []) {
            return [];
        }

        $paths = [];

        foreach ($this->objects->listWithMediaForWorkspaces($ids) as $object) {
            if ($object->cover !== null) {
                array_push($paths, ...$this->covers->paths($object->id, $object->cover));
            }

            foreach ($object->photos as $photo) {
                array_push($paths, ...$this->photos->paths($object->id, $photo->key));
            }
        }

        return $paths;
    }
}
