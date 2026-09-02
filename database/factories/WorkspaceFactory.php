<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\WorkspaceType;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Workspace>
 */
class WorkspaceFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->company();

        return [
            'type' => WorkspaceType::Company,
            'name' => $name,
            'slug' => Str::slug($name).'-'.Str::lower(Str::random(6)),
            'owner_id' => User::factory(),
        ];
    }

    public function personal(): static
    {
        return $this->state(function (array $attributes): array {
            $name = fake()->name();

            return [
                'type' => WorkspaceType::Personal,
                'name' => $name,
                'slug' => Str::slug($name).'-'.Str::lower(Str::random(6)),
            ];
        });
    }

    public function company(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => WorkspaceType::Company,
        ]);
    }

    public function ownedBy(User $user): static
    {
        return $this->state(fn (array $attributes): array => [
            'owner_id' => $user->getKey(),
        ]);
    }
}
