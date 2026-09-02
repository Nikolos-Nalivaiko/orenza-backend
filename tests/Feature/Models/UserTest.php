<?php

declare(strict_types=1);

namespace Tests\Feature\Models;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

final class UserTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_persists_the_expected_columns(): void
    {
        $user = User::create([
            'first_name' => 'Ада',
            'last_name' => 'Лавлейс',
            'email' => 'ada@example.com',
            'phone' => '+380501234567',
            'password' => 'password123',
        ]);

        $this->assertDatabaseHas('users', [
            'first_name' => 'Ада',
            'last_name' => 'Лавлейс',
            'email' => 'ada@example.com',
            'phone' => '+380501234567',
        ]);

        $this->assertTrue(Hash::check('password123', $user->password));
    }

    public function test_the_phone_is_optional(): void
    {
        $user = User::factory()->withoutPhone()->create();

        $this->assertNull($user->phone);
    }

    public function test_it_exposes_the_full_name(): void
    {
        $user = User::factory()->make(['first_name' => 'Ада', 'last_name' => 'Лавлейс']);

        $this->assertSame('Ада Лавлейс', $user->full_name);
    }

    public function test_the_password_and_remember_token_stay_hidden(): void
    {
        $user = User::factory()->create();

        $this->assertArrayNotHasKey('password', $user->toArray());
        $this->assertArrayNotHasKey('remember_token', $user->toArray());
    }

    public function test_it_issues_sanctum_tokens(): void
    {
        $user = User::factory()->create();
        $user->createToken('test');

        $this->assertSame(1, $user->tokens()->count());
    }
}
