<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Profile;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class SessionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_other_sessions_are_signed_out(): void
    {
        $user = User::factory()->create();
        $current = $user->createToken('laptop')->plainTextToken;
        $user->createToken('phone');

        $this->withHeader('Authorization', "Bearer {$current}")
            ->deleteJson('/api/v1/profile/sessions')
            ->assertOk()
            ->assertJsonPath('data.revoked_tokens', 1)
            ->assertJsonPath('message', __('messages.profile.sessions_revoked'));

        $this->assertSame(['laptop'], $user->tokens()->pluck('name')->all());

        $this->withHeader('Authorization', "Bearer {$current}")
            ->getJson('/api/v1/auth/me')
            ->assertOk();
    }

    public function test_sessions_of_other_users_are_untouched(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $current = $user->createToken('laptop')->plainTextToken;
        $other->createToken('phone');

        $this->withHeader('Authorization', "Bearer {$current}")
            ->deleteJson('/api/v1/profile/sessions')
            ->assertOk()
            ->assertJsonPath('data.revoked_tokens', 0);

        $this->assertSame(1, $other->tokens()->count());
    }

    public function test_a_guest_cannot_sign_out_sessions(): void
    {
        $this->deleteJson('/api/v1/profile/sessions')->assertUnauthorized();
    }
}
