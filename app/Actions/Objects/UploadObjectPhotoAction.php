<?php

declare(strict_types=1);

namespace App\Actions\Objects;

use App\Actions\Contracts\Action;
use App\Enums\PhotoVariant;
use App\Exceptions\BusinessRuleException;
use App\Models\ConstructionObject;
use App\Models\ObjectPhoto;
use App\Models\User;
use App\Repositories\Contracts\ObjectPhotoRepositoryInterface;
use App\Support\Media\ImageProcessor;
use App\Support\Media\MediaStorage;
use App\Support\Media\PhotoStorage;
use App\Support\Media\UnreadableImageException;
use Carbon\CarbonImmutable;
use Throwable;

final readonly class UploadObjectPhotoAction implements Action
{
    public const int MAX_PER_OBJECT = 500;

    public function __construct(
        private ObjectPhotoRepositoryInterface $photos,
        private ImageProcessor $processor,
        private PhotoStorage $storage,
    ) {}

    public function handle(
        ConstructionObject $object,
        string $path,
        ?string $originalName = null,
        ?User $uploader = null,
        ?CarbonImmutable $takenAt = null,
    ): ObjectPhoto {
        if ($this->photos->countForObject($object) >= self::MAX_PER_OBJECT) {
            throw BusinessRuleException::make(
                __('messages.photos.limit', ['max' => self::MAX_PER_OBJECT]),
                ['photo' => null],
            );
        }

        try {
            $processed = $this->processor->process($path, PhotoVariant::cases());
        } catch (UnreadableImageException) {
            throw BusinessRuleException::make(__('messages.photos.unreadable'), ['photo' => null]);
        }

        $key = MediaStorage::newKey();

        $this->storage->store($object->id, $key, $processed);

        try {
            /** @var ObjectPhoto */
            return $this->photos->create([
                'construction_object_id' => $object->id,
                'uploaded_by' => $uploader?->id,
                'key' => $key,
                'width' => $processed->width,
                'height' => $processed->height,
                'color' => $processed->color,
                'original_name' => $originalName === null ? null : mb_substr($originalName, 0, 255),
                'taken_at' => $processed->takenAt ?? $takenAt,
            ]);
        } catch (Throwable $exception) {
            $this->storage->purgeNow($object->id, $key);

            throw $exception;
        }
    }
}
