<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\PaymentStatus;
use App\Models\ConstructionObject;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'construction_object_id' => ConstructionObject::factory(),
            'name' => fake()->randomElement(['Аванс', 'Другий транш', 'Доплата']),
            'description' => null,
            'amount' => fake()->randomFloat(2, 5000, 300000),
            'status' => PaymentStatus::Pending,
            'paid_at' => null,
            'client_visible' => false,
        ];
    }

    public function ofObject(ConstructionObject $object): static
    {
        return $this->state(fn (array $attributes): array => [
            'construction_object_id' => $object->getKey(),
        ]);
    }

    public function status(PaymentStatus $status): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => $status,
        ]);
    }

    public function paid(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => PaymentStatus::Paid,
            'paid_at' => now()->toDateString(),
        ]);
    }
}
