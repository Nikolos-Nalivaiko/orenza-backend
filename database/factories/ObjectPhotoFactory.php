<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ConstructionObject;
use App\Models\ObjectPhoto;
use App\Support\Media\MediaStorage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ObjectPhoto>
 */
class ObjectPhotoFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'construction_object_id' => ConstructionObject::factory(),
            'uploaded_by' => null,
            'key' => MediaStorage::newKey(),
            'width' => 1600,
            'height' => 1200,
            'color' => '#8a7f6a',
            'original_name' => 'IMG_'.fake()->numerify('####').'.jpg',
            'taken_at' => null,
        ];
    }

    public function ofObject(ConstructionObject $object): static
    {
        return $this->state(fn (array $attributes): array => [
            'construction_object_id' => $object->getKey(),
        ]);
    }

    public function takenAt(string $moment): static
    {
        return $this->state(fn (array $attributes): array => [
            'taken_at' => $moment,
        ]);
    }
}
