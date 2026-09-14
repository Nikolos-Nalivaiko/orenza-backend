<?php

declare(strict_types=1);

namespace App\Actions\Objects;

use App\Actions\Contracts\Action;
use App\Exceptions\BusinessRuleException;
use App\Models\ConstructionObject;
use App\Repositories\Contracts\ObjectRepositoryInterface;

final readonly class UpdateObjectCoverFocusAction implements Action
{
    public function __construct(private ObjectRepositoryInterface $objects) {}

    public function handle(ConstructionObject $object, float $x, float $y): ConstructionObject
    {
        $cover = $object->cover;

        if ($cover === null) {
            throw BusinessRuleException::make(__('messages.objects.cover_missing'), ['cover' => null]);
        }

        return $this->objects
            ->update($object, ['cover' => $cover->withFocus($x, $y)])
            ->load(['client', 'materials', 'services.workers', 'payments']);
    }
}
