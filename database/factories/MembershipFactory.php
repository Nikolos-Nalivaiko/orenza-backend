<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\MembershipRole;
use App\Enums\MembershipStatus;
use App\Models\Membership;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Membership>
 */
class MembershipFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'workspace_id' => Workspace::factory(),
            'user_id' => User::factory(),
            'role' => MembershipRole::Member,
            'status' => MembershipStatus::Active,
            'invited_by_id' => null,
            'last_active_at' => null,
        ];
    }

    public function role(MembershipRole $role): static
    {
        return $this->state(fn (array $attributes): array => [
            'role' => $role,
        ]);
    }

    public function owner(): static
    {
        return $this->role(MembershipRole::Owner);
    }

    public function admin(): static
    {
        return $this->role(MembershipRole::Admin);
    }

    public function suspended(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => MembershipStatus::Suspended,
        ]);
    }

    public function forWorkspace(Workspace $workspace): static
    {
        return $this->state(fn (array $attributes): array => [
            'workspace_id' => $workspace->getKey(),
        ]);
    }

    public function forUser(User $user): static
    {
        return $this->state(fn (array $attributes): array => [
            'user_id' => $user->getKey(),
        ]);
    }

    public function invitedBy(User $user): static
    {
        return $this->state(fn (array $attributes): array => [
            'invited_by_id' => $user->getKey(),
        ]);
    }
}
