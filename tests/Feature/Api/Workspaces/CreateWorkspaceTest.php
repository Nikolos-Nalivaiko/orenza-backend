<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Workspaces;

use App\Enums\MembershipRole;
use App\Enums\MembershipStatus;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class CreateWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_guest_cannot_create_a_workspace(): void
    {
        $this->postJson('/api/v1/workspaces', ['type' => 'company', 'name' => 'Оренза'])
            ->assertUnauthorized();
    }

    public function test_a_company_workspace_is_created_with_an_owner_membership(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/workspaces', ['type' => 'company', 'name' => 'Оренза']);

        $response->assertCreated()
            ->assertJsonPath('data.type.value', 'company')
            ->assertJsonPath('data.type.label', 'Компания')
            ->assertJsonPath('data.name', 'Оренза')
            ->assertJsonPath('data.slug', 'orenza')
            ->assertJsonPath('data.owner_id', $user->id);

        $workspace = Workspace::sole();

        $this->assertTrue($workspace->owner->is($user));

        $membership = $workspace->membershipFor($user);

        $this->assertNotNull($membership);
        $this->assertSame(MembershipRole::Owner, $membership->role);
        $this->assertSame(MembershipStatus::Active, $membership->status);
    }

    public function test_a_company_workspace_requires_a_name(): void
    {
        $this->actingAs(User::factory()->create(), 'sanctum')
            ->postJson('/api/v1/workspaces', ['type' => 'company'])
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'validation_failed')
            ->assertJsonStructure(['errors' => ['name']]);
    }

    public function test_a_personal_workspace_falls_back_to_the_owner_name(): void
    {
        $user = User::factory()->create(['first_name' => 'Ада', 'last_name' => 'Лавлейс']);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/workspaces', ['type' => 'personal'])
            ->assertCreated()
            ->assertJsonPath('data.name', 'Ада Лавлейс')
            ->assertJsonPath('data.slug', 'ada-lavleis');
    }

    public function test_a_personal_workspace_accepts_a_custom_name(): void
    {
        $user = User::factory()->create(['first_name' => 'Ада', 'last_name' => 'Лавлейс']);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/workspaces', ['type' => 'personal', 'name' => 'Мои проекты'])
            ->assertCreated()
            ->assertJsonPath('data.name', 'Мои проекты')
            ->assertJsonPath('data.type.value', 'personal');
    }

    public function test_only_one_personal_workspace_per_user_is_allowed(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/workspaces', ['type' => 'personal'])
            ->assertCreated();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/workspaces', ['type' => 'personal'])
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'business_rule_violation');

        $this->assertDatabaseCount('workspaces', 1);
    }

    public function test_a_user_may_own_several_company_workspaces(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/workspaces', ['type' => 'company', 'name' => 'Оренза'])
            ->assertCreated();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/workspaces', ['type' => 'company', 'name' => 'Оренза'])
            ->assertCreated()
            ->assertJsonPath('data.slug', 'orenza-2');

        $this->assertDatabaseCount('workspaces', 2);
        $this->assertSame(2, $user->memberships()->role(MembershipRole::Owner)->count());
    }

    public function test_a_custom_slug_is_accepted(): void
    {
        $this->actingAs(User::factory()->create(), 'sanctum')
            ->postJson('/api/v1/workspaces', ['type' => 'company', 'name' => 'Оренза', 'slug' => 'My-Studio'])
            ->assertCreated()
            ->assertJsonPath('data.slug', 'my-studio');
    }

    public function test_a_taken_slug_is_rejected(): void
    {
        Workspace::factory()->create(['slug' => 'orenza']);

        $this->actingAs(User::factory()->create(), 'sanctum')
            ->postJson('/api/v1/workspaces', ['type' => 'company', 'name' => 'Оренза', 'slug' => 'orenza'])
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['slug']]);
    }

    public function test_an_invalid_slug_is_rejected(): void
    {
        $this->actingAs(User::factory()->create(), 'sanctum')
            ->postJson('/api/v1/workspaces', ['type' => 'company', 'name' => 'Оренза', 'slug' => 'Моя студия!'])
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['slug']]);
    }

    public function test_the_generated_slug_skips_soft_deleted_workspaces(): void
    {
        Workspace::factory()->create(['slug' => 'orenza'])->delete();

        $this->actingAs(User::factory()->create(), 'sanctum')
            ->postJson('/api/v1/workspaces', ['type' => 'company', 'name' => 'Оренза'])
            ->assertCreated()
            ->assertJsonPath('data.slug', 'orenza-2');
    }

    public function test_a_name_without_latin_or_cyrillic_letters_still_gets_a_slug(): void
    {
        $this->actingAs(User::factory()->create(), 'sanctum')
            ->postJson('/api/v1/workspaces', ['type' => 'company', 'name' => '日本語'])
            ->assertCreated()
            ->assertJsonPath('data.slug', 'workspace');
    }

    public function test_an_invalid_type_is_rejected(): void
    {
        $this->actingAs(User::factory()->create(), 'sanctum')
            ->postJson('/api/v1/workspaces', ['type' => 'team', 'name' => 'Оренза'])
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['type']]);
    }

    public function test_the_first_workspace_becomes_the_current_one(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/workspaces', ['type' => 'personal'])
            ->assertCreated();

        $first = Workspace::sole();

        $this->assertSame($first->id, $user->fresh()->current_workspace_id);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/workspaces', ['type' => 'company', 'name' => 'Оренза'])
            ->assertCreated();

        $this->assertSame($first->id, $user->fresh()->current_workspace_id);
    }
}
