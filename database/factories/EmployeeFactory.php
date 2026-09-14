<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\EmployeeStatus;
use App\Models\Employee;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Employee>
 */
class EmployeeFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'workspace_id' => Workspace::factory()->company(),
            'name' => fake()->name(),
            'role' => fake()->randomElement(['Муляр', 'Штукатур', 'Електрик', 'Маляр', 'Бригадир']),
            'phone' => '+3806'.fake()->numerify('########'),
            'email' => null,
            'status' => EmployeeStatus::Active,
            'notes' => null,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => EmployeeStatus::Inactive,
        ]);
    }

    public function ofWorkspace(Workspace $workspace): static
    {
        return $this->state(fn (array $attributes): array => [
            'workspace_id' => $workspace->getKey(),
        ]);
    }
}
