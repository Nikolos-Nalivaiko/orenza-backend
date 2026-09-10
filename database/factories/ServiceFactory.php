<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ServiceStatus;
use App\Models\ConstructionObject;
use App\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Service>
 */
class ServiceFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'construction_object_id' => ConstructionObject::factory(),
            'name' => fake()->randomElement(['Монолітні роботи', 'Мурування', 'Штукатурка', 'Покрівля']),
            'description' => null,
            'unit' => fake()->randomElement(['м²', 'м³', 'т', 'пог. м']),
            'planned_volume' => fake()->randomFloat(2, 10, 500),
            'actual_volume' => null,
            'client_price' => fake()->randomFloat(2, 200, 2000),
            'status' => ServiceStatus::Planned,
        ];
    }

    public function ofObject(ConstructionObject $object): static
    {
        return $this->state(fn (array $attributes): array => [
            'construction_object_id' => $object->getKey(),
        ]);
    }

    public function status(ServiceStatus $status): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => $status,
        ]);
    }

    public function done(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => ServiceStatus::Done,
            'actual_volume' => $attributes['planned_volume'],
        ]);
    }
}
