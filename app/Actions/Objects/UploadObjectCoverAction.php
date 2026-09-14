<?php

declare(strict_types=1);

namespace App\Actions\Objects;

use App\Actions\Contracts\Action;
use App\Enums\CoverVariant;
use App\Exceptions\BusinessRuleException;
use App\Models\ConstructionObject;
use App\Repositories\Contracts\ObjectRepositoryInterface;
use App\Support\Media\Cover;
use App\Support\Media\CoverStorage;
use App\Support\Media\ImageProcessor;
use App\Support\Media\MediaStorage;
use App\Support\Media\UnreadableImageException;
use Throwable;

final readonly class UploadObjectCoverAction implements Action
{
    public function __construct(
        private ObjectRepositoryInterface $objects,
        private ImageProcessor $processor,
        private CoverStorage $storage,
    ) {}

    public function handle(
        ConstructionObject $object,
        string $path,
        float $focusX = Cover::FOCUS_CENTER,
        float $focusY = Cover::FOCUS_CENTER,
    ): ConstructionObject {
        try {
            $processed = $this->processor->process($path, CoverVariant::cases());
        } catch (UnreadableImageException) {
            throw BusinessRuleException::make(__('messages.objects.cover_unreadable'), ['cover' => null]);
        }

        $cover = (new Cover(
            key: MediaStorage::newKey(),
            width: $processed->width,
            height: $processed->height,
            color: $processed->color,
        ))->withFocus($focusX, $focusY);

        $previous = $object->cover;

        $this->storage->store($object->id, $cover, $processed);

        try {
            $object = $this->objects->update($object, ['cover' => $cover]);
        } catch (Throwable $exception) {
            $this->storage->purgeNow($object->id, $cover);

            throw $exception;
        }

        if ($previous !== null) {
            $this->storage->purgeLater($object->id, $previous);
        }

        return $object->load(['client', 'materials', 'services.workers', 'payments']);
    }
}
