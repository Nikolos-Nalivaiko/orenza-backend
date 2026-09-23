<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Profile;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class UpdateProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_user_can_update_their_profile(): void
    {
        $user = User::factory()->create(['first_name' => 'Ада', 'last_name' => 'Лавлейс']);

        $this->actingAs($user, 'sanctum')
            ->patchJson('/api/v1/profile', [
                'first_name' => '  Нікола ',
                'last_name' => 'Наливайко',
                'email' => ' Nikola@Orenza.UA ',
                'phone' => '+380 (67) 123-45-67',
            ])
            ->assertOk()
            ->assertJsonPath('data.first_name', 'Нікола')
            ->assertJsonPath('data.full_name', 'Нікола Наливайко')
            ->assertJsonPath('data.email', 'nikola@orenza.ua')
            ->assertJsonPath('data.phone', '+380671234567');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'first_name' => 'Нікола',
            'email' => 'nikola@orenza.ua',
            'phone' => '+380671234567',
        ]);
    }

    public function test_fields_can_be_updated_one_by_one(): void
    {
        $user = User::factory()->create(['first_name' => 'Ада', 'email' => 'ada@example.com']);

        $this->actingAs($user, 'sanctum')
            ->patchJson('/api/v1/profile', ['last_name' => 'Байрон'])
            ->assertOk()
            ->assertJsonPath('data.first_name', 'Ада')
            ->assertJsonPath('data.last_name', 'Байрон')
            ->assertJsonPath('data.email', 'ada@example.com');
    }

    public function test_keeping_the_same_email_and_phone_is_allowed(): void
    {
        $user = User::factory()->create(['email' => 'ada@example.com', 'phone' => '+380671234567']);

        $this->actingAs($user, 'sanctum')
            ->patchJson('/api/v1/profile', ['email' => 'ada@example.com', 'phone' => '+380671234567'])
            ->assertOk();
    }

    public function test_the_phone_can_be_cleared(): void
    {
        $user = User::factory()->create(['phone' => '+380671234567']);

        $this->actingAs($user, 'sanctum')
            ->patchJson('/api/v1/profile', ['phone' => null])
            ->assertOk()
            ->assertJsonPath('data.phone', null);

        $this->actingAs($user, 'sanctum')
            ->patchJson('/api/v1/profile', ['phone' => ''])
            ->assertOk()
            ->assertJsonPath('data.phone', null);
    }

    public function test_an_email_of_another_user_is_rejected(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->patchJson('/api/v1/profile', ['email' => 'Taken@Example.com'])
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['email']]);
    }

    public function test_a_phone_of_another_user_is_rejected(): void
    {
        User::factory()->create(['phone' => '+380671234567']);
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->patchJson('/api/v1/profile', ['phone' => '+380 67 123 45 67'])
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['phone']]);
    }

    public function test_names_and_email_cannot_be_blank(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->patchJson('/api/v1/profile', ['first_name' => '  ', 'last_name' => '', 'email' => 'not-an-email'])
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['first_name', 'last_name', 'email']]);
    }

    public function test_changing_the_email_drops_its_verification(): void
    {
        $user = User::factory()->create(['email' => 'ada@example.com', 'email_verified_at' => now()]);

        $this->actingAs($user, 'sanctum')
            ->patchJson('/api/v1/profile', ['email' => 'ada@example.com', 'first_name' => 'Ада'])
            ->assertOk();

        $this->assertNotNull($user->refresh()->email_verified_at);

        $this->actingAs($user, 'sanctum')
            ->patchJson('/api/v1/profile', ['email' => 'lovelace@example.com'])
            ->assertOk()
            ->assertJsonPath('data.email_verified_at', null);
    }

    public function test_the_password_cannot_be_changed_here(): void
    {
        $user = User::factory()->create();
        $hash = $user->password;

        $this->actingAs($user, 'sanctum')
            ->patchJson('/api/v1/profile', ['password' => 'new-secret-123'])
            ->assertOk();

        $this->assertSame($hash, $user->refresh()->password);
    }

    public function test_a_guest_cannot_update_a_profile(): void
    {
        $this->patchJson('/api/v1/profile', ['first_name' => 'Ада'])
            ->assertUnauthorized();
    }
}
