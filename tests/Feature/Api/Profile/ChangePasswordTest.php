<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Profile;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

final class ChangePasswordTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_user_can_change_their_password(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->putJson('/api/v1/profile/password', [
                'current_password' => 'password',
                'password' => 'new-secret-123',
                'password_confirmation' => 'new-secret-123',
            ])
            ->assertOk()
            ->assertJsonPath('message', __('messages.profile.password_changed'));

        $this->assertTrue(Hash::check('new-secret-123', $user->refresh()->password));
    }

    public function test_other_sessions_are_signed_out_but_the_current_one_stays(): void
    {
        $user = User::factory()->create();
        $current = $user->createToken('laptop')->plainTextToken;
        $user->createToken('phone');
        $user->createToken('tablet');

        $this->withHeader('Authorization', "Bearer {$current}")
            ->putJson('/api/v1/profile/password', [
                'current_password' => 'password',
                'password' => 'new-secret-123',
                'password_confirmation' => 'new-secret-123',
            ])
            ->assertOk()
            ->assertJsonPath('data.revoked_tokens', 2);

        $this->assertSame(['laptop'], $user->tokens()->pluck('name')->all());
    }

    public function test_a_wrong_current_password_is_rejected(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->putJson('/api/v1/profile/password', [
                'current_password' => 'not-my-password',
                'password' => 'new-secret-123',
                'password_confirmation' => 'new-secret-123',
            ])
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['current_password']]);

        $this->assertTrue(Hash::check('password', $user->refresh()->password));
    }

    public function test_the_new_password_must_differ_and_be_confirmed(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->putJson('/api/v1/profile/password', [
                'current_password' => 'password',
                'password' => 'password',
                'password_confirmation' => 'something-else',
            ])
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['password']]);
    }

    public function test_a_short_password_is_rejected(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->putJson('/api/v1/profile/password', [
                'current_password' => 'password',
                'password' => 'short',
                'password_confirmation' => 'short',
            ])
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['password']]);
    }

    public function test_guessing_the_current_password_is_throttled(): void
    {
        $user = User::factory()->create();

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->actingAs($user, 'sanctum')
                ->putJson('/api/v1/profile/password', ['current_password' => "guess-{$attempt}", 'password' => 'new-secret-123', 'password_confirmation' => 'new-secret-123'])
                ->assertStatus(422);
        }

        $this->actingAs($user, 'sanctum')
            ->putJson('/api/v1/profile/password', ['current_password' => 'guess-6', 'password' => 'new-secret-123', 'password_confirmation' => 'new-secret-123'])
            ->assertStatus(429);
    }

    public function test_a_guest_cannot_change_a_password(): void
    {
        $this->putJson('/api/v1/profile/password', [])->assertUnauthorized();
    }
}
