<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ObjectStatus;
use App\Models\Client;
use App\Models\ConstructionObject;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ConstructionObject>
 */
class ConstructionObjectFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $started = fake()->dateTimeBetween('-6 months', '-1 month');

        return [
            'workspace_id' => Workspace::factory(),
            'client_id' => null,
            'name' => 'ЖК «'.fake()->lastName().'»',
            'description' => null,
            'address' => fake()->streetAddress(),
            'status' => ObjectStatus::Planned,
            'started_at' => $started->format('Y-m-d'),
            'finished_at' => fake()->dateTimeBetween($started, '+6 months')->format('Y-m-d'),
            'actual_started_at' => null,
            'actual_finished_at' => null,
            'cover_path' => null,
            'public_token' => ConstructionObject::newPublicToken(),
            'archived_at' => null,
        ];
    }

    public function ofWorkspace(Workspace $workspace): static
    {
        return $this->state(fn (array $attributes): array => [
            'workspace_id' => $workspace->getKey(),
        ]);
    }

    public function forClient(Client $client): static
    {
        return $this->state(fn (array $attributes): array => [
            'workspace_id' => $client->workspace_id,
            'client_id' => $client->getKey(),
        ]);
    }

    public function status(ObjectStatus $status): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => $status,
        ]);
    }

    public function inProgress(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => ObjectStatus::InProgress,
            'actual_started_at' => $attributes['started_at'] ?? now()->format('Y-m-d'),
        ]);
    }

    public function done(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => ObjectStatus::Done,
            'actual_started_at' => $attributes['started_at'] ?? now()->format('Y-m-d'),
            'actual_finished_at' => $attributes['finished_at'] ?? now()->format('Y-m-d'),
        ]);
    }

    public function archived(): static
    {
        return $this->state(fn (array $attributes): array => [
            'archived_at' => now(),
        ]);
    }
}
