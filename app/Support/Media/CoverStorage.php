<?php

declare(strict_types=1);

namespace App\Support\Media;

use App\Enums\CoverVariant;

final readonly class CoverStorage
{
    public function __construct(private MediaStorage $media) {}

    public function path(int $objectId, Cover $cover, CoverVariant $variant): string
    {
        return "objects/{$objectId}/cover/{$cover->key}-{$variant->value}.webp";
    }

    public function url(int $objectId, Cover $cover, CoverVariant $variant): string
    {
        return $this->media->url($this->path($objectId, $cover, $variant));
    }

    /**
     * @return list<string>
     */
    public function paths(int $objectId, Cover $cover): array
    {
        return array_map(
            fn (CoverVariant $variant): string => $this->path($objectId, $cover, $variant),
            CoverVariant::cases(),
        );
    }

    public function store(int $objectId, Cover $cover, ProcessedImage $processed): void
    {
        $files = [];

        foreach (CoverVariant::cases() as $variant) {
            $files[$this->path($objectId, $cover, $variant)] = $processed->variant($variant->value);
        }

        $this->media->store($files);
    }

    public function purgeNow(int $objectId, Cover $cover): void
    {
        $this->media->deleteNow($this->paths($objectId, $cover));
    }

    public function purgeLater(int $objectId, Cover $cover): void
    {
        $this->media->deleteLater($this->paths($objectId, $cover));
    }
}
