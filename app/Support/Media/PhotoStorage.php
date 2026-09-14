<?php

declare(strict_types=1);

namespace App\Support\Media;

use App\Enums\PhotoVariant;
use App\Models\ObjectPhoto;

final readonly class PhotoStorage
{
    public function __construct(private MediaStorage $media) {}

    public function path(int $objectId, string $key, PhotoVariant $variant): string
    {
        return "objects/{$objectId}/photos/{$key}-{$variant->value}.webp";
    }

    public function url(ObjectPhoto $photo, PhotoVariant $variant): string
    {
        return $this->media->url($this->path($photo->construction_object_id, $photo->key, $variant));
    }

    /**
     * @return list<string>
     */
    public function paths(int $objectId, string $key): array
    {
        return array_map(
            fn (PhotoVariant $variant): string => $this->path($objectId, $key, $variant),
            PhotoVariant::cases(),
        );
    }

    public function store(int $objectId, string $key, ProcessedImage $processed): void
    {
        $files = [];

        foreach (PhotoVariant::cases() as $variant) {
            $files[$this->path($objectId, $key, $variant)] = $processed->variant($variant->value);
        }

        $this->media->store($files);
    }

    public function purgeNow(int $objectId, string $key): void
    {
        $this->media->deleteNow($this->paths($objectId, $key));
    }

    /**
     * @param  iterable<ObjectPhoto>  $photos
     */
    public function purgeLater(iterable $photos): void
    {
        $paths = [];

        foreach ($photos as $photo) {
            array_push($paths, ...$this->paths($photo->construction_object_id, $photo->key));
        }

        $this->media->deleteLater($paths);
    }
}
