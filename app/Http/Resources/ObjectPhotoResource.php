<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Enums\PhotoVariant;
use App\Models\ObjectPhoto;
use App\Support\Media\PhotoStorage;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ObjectPhoto
 */
final class ObjectPhotoResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $storage = app(PhotoStorage::class);

        return [
            'id' => $this->id,
            'thumb' => $storage->url($this->resource, PhotoVariant::Thumb),
            'full' => $storage->url($this->resource, PhotoVariant::Full),
            'width' => $this->width,
            'height' => $this->height,
            'color' => $this->color,
            'name' => $this->original_name,
            'taken_at' => $this->taken_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
