<?php

declare(strict_types=1);

namespace App\Actions\Objects;

use App\Actions\Contracts\Action;
use App\Models\ConstructionObject;
use App\Repositories\Contracts\ObjectRepositoryInterface;

final readonly class DeleteObjectAction implements Action
{
    public function __construct(private ObjectRepositoryInterface $objects) {}

    public function handle(ConstructionObject $object): bool
    {
        return $this->objects->delete($object);
    }
}
