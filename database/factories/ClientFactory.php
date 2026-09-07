<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ClientType;
use App\Models\Client;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Client>
 */
class ClientFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'workspace_id' => Workspace::factory(),
            'type' => ClientType::Person,
            'name' => fake()->name(),
            'contact' => null,
            'phone' => '+3806'.fake()->numerify('########'),
            'email' => fake()->unique()->safeEmail(),
            'notes' => null,
            'discount' => 0,
        ];
    }

    public function person(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => ClientType::Person,
            'name' => fake()->name(),
            'contact' => null,
        ]);
    }

    public function company(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => ClientType::Company,
            'name' => fake()->company(),
            'contact' => fake()->name(),
        ]);
    }

    public function ofWorkspace(Workspace $workspace): static
    {
        return $this->state(fn (array $attributes): array => [
            'workspace_id' => $workspace->getKey(),
        ]);
    }
}
