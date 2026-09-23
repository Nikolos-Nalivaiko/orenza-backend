<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Workspaces;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class UpdateWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_owner_can_rename_the_workspace(): void
    {
        $owner = User::factory()->create();
        $workspace = $this->workspaceOf($owner);

        $this->actingAs($owner, 'sanctum')
            ->patchJson("/api/v1/workspaces/{$workspace->slug}", ['name' => '  Нова назва  '])
            ->assertOk()
            ->assertJsonPath('data.name', 'Нова назва')
            ->assertJsonPath('data.slug', $workspace->slug)
            ->assertJsonPath('message', __('messages.workspaces.updated'));

        $this->assertSame('Нова назва', $workspace->refresh()->name);
    }

    public function test_renaming_keeps_the_slug_and_type(): void
    {
        $owner = User::factory()->create();
        $workspace = $this->workspaceOf($owner);
        $slug = $workspace->slug;
        $type = $workspace->type;

        $this->actingAs($owner, 'sanctum')
            ->patchJson("/api/v1/workspaces/{$slug}", ['name' => 'Інша назва', 'slug' => 'hijack', 'type' => 'personal'])
            ->assertOk();

        $workspace->refresh();

        $this->assertSame($slug, $workspace->slug);
        $this->assertSame($type, $workspace->type);
    }

    public function test_an_outsider_cannot_rename(): void
    {
        $workspace = $this->workspaceOf(User::factory()->create());

        $this->actingAs(User::factory()->create(), 'sanctum')
            ->patchJson("/api/v1/workspaces/{$workspace->slug}", ['name' => 'Захоплено'])
            ->assertForbidden();
    }

    public function test_the_name_is_validated(): void
    {
        $owner = User::factory()->create();
        $workspace = $this->workspaceOf($owner);

        foreach (['', '   ', 'a', str_repeat('я', 256)] as $name) {
            $this->actingAs($owner, 'sanctum')
                ->patchJson("/api/v1/workspaces/{$workspace->slug}", ['name' => $name])
                ->assertStatus(422)
                ->assertJsonStructure(['errors' => ['name']]);
        }
    }

    public function test_a_guest_cannot_rename(): void
    {
        $workspace = $this->workspaceOf(User::factory()->create());

        $this->patchJson("/api/v1/workspaces/{$workspace->slug}", ['name' => 'Нова'])->assertUnauthorized();
    }

    private function workspaceOf(User $user): Workspace
    {
        return Workspace::factory()->company()->ownedBy($user)->create();
    }
}
