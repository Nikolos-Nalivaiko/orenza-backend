<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Enums\CoverVariant;
use App\Models\ConstructionObject;
use App\Support\Media\CoverStorage;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property ConstructionObject $resource
 */
final class CoverResource extends JsonResource
{
    /**
     * @return array<string, mixed>|null
     */
    public static function for(ConstructionObject $object): ?array
    {
        return $object->cover === null ? null : (new self($object))->resolve();
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $cover = $this->resource->cover;
        $storage = app(CoverStorage::class);

        if ($cover === null) {
            return [];
        }

        $urls = [];

        foreach (CoverVariant::cases() as $variant) {
            $urls[$variant->value] = $storage->url($this->resource->id, $cover, $variant);
        }

        return [
            ...$urls,
            'width' => $cover->width,
            'height' => $cover->height,
            'color' => $cover->color,
            'focus' => [
                'x' => $cover->focusX,
                'y' => $cover->focusY,
            ],
        ];
    }
}
