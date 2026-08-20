<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

final class RegisterTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'first_name' => 'Ада',
            'last_name' => 'Лавлейс',
            'email' => 'ada@example.com',
            'phone' => '+380 (50) 123-45-67',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ], $overrides);
    }

    public function test_a_guest_can_register_and_receives_a_token(): void
    {
        Event::fake([Registered::class]);

        $response = $this->postJson('/api/v1/auth/register', $this->payload());

        $response->assertCreated()
            ->assertJsonPath('data.user.email', 'ada@example.com')
            ->assertJsonPath('data.user.full_name', 'Ада Лавлейс')
            ->assertJsonPath('data.token.type', 'Bearer')
            ->assertJsonStructure(['data' => ['user' => ['id', 'first_name', 'last_name', 'phone'], 'token' => ['token', 'expires_at']]]);

        $user = User::firstWhere('email', 'ada@example.com');

        $this->assertNotNull($user);
        $this->assertTrue(Hash::check('password123', $user->password));
        $this->assertSame(1, $user->tokens()->count());

        Event::assertDispatched(Registered::class);
    }

    public function test_registration_does_not_create_a_workspace(): void
    {
        $this->postJson('/api/v1/auth/register', $this->payload())->assertCreated();

        $this->assertDatabaseCount('workspaces', 0);
        $this->assertDatabaseCount('memberships', 0);
        $this->assertNull(User::firstWhere('email', 'ada@example.com')->current_workspace_id);
    }

    public function test_the_phone_is_optional(): void
    {
        $this->postJson('/api/v1/auth/register', $this->payload(['phone' => null]))
            ->assertCreated()
            ->assertJsonPath('data.user.phone', null);
    }

    public function test_the_phone_is_normalised_before_storing(): void
    {
        $this->postJson('/api/v1/auth/register', $this->payload())->assertCreated();

        $this->assertDatabaseHas('users', ['phone' => '+380501234567']);
    }

    public function test_the_email_is_stored_in_lower_case(): void
    {
        $this->postJson('/api/v1/auth/register', $this->payload(['email' => 'ADA@Example.COM']))
            ->assertCreated();

        $this->assertDatabaseHas('users', ['email' => 'ada@example.com']);
    }

    public function test_it_rejects_a_duplicate_email(): void
    {
        User::factory()->create(['email' => 'ada@example.com']);

        $this->postJson('/api/v1/auth/register', $this->payload())
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'validation_failed')
            ->assertJsonStructure(['errors' => ['email']]);
    }

    public function test_it_rejects_a_duplicate_phone(): void
    {
        User::factory()->create(['phone' => '+380501234567']);

        $this->postJson('/api/v1/auth/register', $this->payload())
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['phone']]);
    }

    public function test_it_requires_a_confirmed_password(): void
    {
        $this->postJson('/api/v1/auth/register', $this->payload(['password_confirmation' => 'other-password']))
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['password']]);
    }

    public function test_it_requires_the_name_fields(): void
    {
        $this->postJson('/api/v1/auth/register', ['email' => 'ada@example.com'])
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['first_name', 'last_name', 'password']]);
    }
}
