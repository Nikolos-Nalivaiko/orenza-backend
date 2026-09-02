<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class SessionTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_authenticated_user_can_read_their_profile(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('data.email', $user->email)
            ->assertJsonMissingPath('data.password');
    }

    public function test_a_guest_cannot_read_a_profile(): void
    {
        $this->getJson('/api/v1/auth/me')
            ->assertUnauthorized()
            ->assertJsonPath('error_code', 'unauthenticated');
    }

    public function test_a_token_from_login_grants_access(): void
    {
        User::factory()->create(['email' => 'ada@example.com']);

        $token = $this->postJson('/api/v1/auth/login', [
            'email' => 'ada@example.com',
            'password' => 'password',
        ])->json('data.token.token');

        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('data.email', 'ada@example.com');
    }

    public function test_logging_out_revokes_the_current_token_only(): void
    {
        $user = User::factory()->create(['email' => 'ada@example.com']);
        $user->createToken('other-device');

        $token = $this->postJson('/api/v1/auth/login', [
            'email' => 'ada@example.com',
            'password' => 'password',
        ])->json('data.token.token');

        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/auth/logout')
            ->assertOk()
            ->assertJsonPath('data.revoked_tokens', 1);

        $this->assertSame(1, $user->tokens()->count());

        $this->app['auth']->forgetGuards();

        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/auth/me')
            ->assertUnauthorized();
    }

    public function test_logging_out_everywhere_revokes_every_token(): void
    {
        $user = User::factory()->create();
        $user->createToken('first');
        $user->createToken('second');

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/auth/logout', ['everywhere' => true])
            ->assertOk()
            ->assertJsonPath('data.revoked_tokens', 2);

        $this->assertSame(0, $user->tokens()->count());
    }
}
