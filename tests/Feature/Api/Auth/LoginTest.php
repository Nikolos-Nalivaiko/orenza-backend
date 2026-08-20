<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_user_can_sign_in(): void
    {
        $user = User::factory()->create(['email' => 'ada@example.com']);

        $this->postJson('/api/v1/auth/login', [
            'email' => 'ADA@example.com',
            'password' => 'password',
            'device_name' => 'iPhone',
        ])
            ->assertOk()
            ->assertJsonPath('data.user.id', $user->id)
            ->assertJsonPath('data.token.type', 'Bearer')
            ->assertJsonStructure(['data' => ['token' => ['token', 'expires_at']]]);

        $this->assertSame('iPhone', $user->tokens()->sole()->name);
    }

    public function test_it_rejects_a_wrong_password(): void
    {
        User::factory()->create(['email' => 'ada@example.com']);

        $this->postJson('/api/v1/auth/login', [
            'email' => 'ada@example.com',
            'password' => 'wrong-password',
        ])
            ->assertUnauthorized()
            ->assertJsonPath('error_code', 'invalid_credentials');
    }

    public function test_it_rejects_an_unknown_email(): void
    {
        $this->postJson('/api/v1/auth/login', [
            'email' => 'nobody@example.com',
            'password' => 'password',
        ])
            ->assertUnauthorized()
            ->assertJsonPath('error_code', 'invalid_credentials');
    }

    public function test_repeated_failures_are_throttled(): void
    {
        User::factory()->create(['email' => 'ada@example.com']);

        $payload = ['email' => 'ada@example.com', 'password' => 'wrong-password'];

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->postJson('/api/v1/auth/login', $payload)->assertUnauthorized();
        }

        $this->postJson('/api/v1/auth/login', $payload)
            ->assertStatus(429)
            ->assertJsonPath('error_code', 'too_many_requests');
    }
}
