<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Workspaces;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ListWorkspacesTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_own_workspaces_are_listed(): void
    {
        $user = User::factory()->create();
        $first = Workspace::factory()->company()->ownedBy($user)->create();
        $second = Workspace::factory()->personal()->ownedBy($user)->create();
        Workspace::factory()->company()->create();
        Workspace::factory()->company()->ownedBy($user)->create()->delete();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/workspaces')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.id', $first->id)
            ->assertJsonPath('data.1.id', $second->id);
    }

    public function test_a_foreign_workspace_cannot_be_opened_or_made_current(): void
    {
        $user = User::factory()->create();
        $foreign = Workspace::factory()->company()->create();

        $this->actingAs($user, 'sanctum')
            ->getJson("/api/v1/workspaces/{$foreign->slug}")
            ->assertForbidden();

        $this->actingAs($user, 'sanctum')
            ->putJson("/api/v1/workspaces/{$foreign->slug}/current")
            ->assertForbidden();

        $this->assertNull($user->refresh()->current_workspace_id);
    }

    public function test_an_own_workspace_can_be_made_current(): void
    {
        $user = User::factory()->create();
        $workspace = Workspace::factory()->company()->ownedBy($user)->create();

        $this->actingAs($user, 'sanctum')
            ->putJson("/api/v1/workspaces/{$workspace->slug}/current")
            ->assertOk()
            ->assertJsonPath('data.user.current_workspace_id', $workspace->id);
    }
}
