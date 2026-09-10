<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\MaterialBuyer;
use App\Enums\MaterialStatus;
use App\Models\ConstructionObject;
use App\Models\Material;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Material>
 */
class MaterialFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $cost = fake()->randomFloat(2, 100, 5000);

        return [
            'construction_object_id' => ConstructionObject::factory(),
            'name' => fake()->randomElement(['Бетон М300', 'Цегла рядова', 'Арматура 12', 'Пісок']),
            'unit' => fake()->randomElement(['шт', 'м³', 'кг', 'т']),
            'quantity' => fake()->randomFloat(2, 1, 500),
            'buyer' => MaterialBuyer::Contractor,
            'cost_price' => $cost,
            'client_price' => round($cost * 1.2, 2),
            'status' => MaterialStatus::Needed,
            'approved_by_client' => false,
        ];
    }

    public function ofObject(ConstructionObject $object): static
    {
        return $this->state(fn (array $attributes): array => [
            'construction_object_id' => $object->getKey(),
        ]);
    }

    public function status(MaterialStatus $status): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => $status,
        ]);
    }

    public function boughtByClient(): static
    {
        return $this->state(fn (array $attributes): array => [
            'buyer' => MaterialBuyer::Client,
            'cost_price' => null,
            'client_price' => null,
        ]);
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes): array => [
            'approved_by_client' => true,
        ]);
    }
}
