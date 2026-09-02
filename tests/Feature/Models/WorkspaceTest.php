<?php

declare(strict_types=1);

namespace Tests\Feature\Models;

use App\Enums\WorkspaceType;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class WorkspaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_persists_the_expected_columns(): void
    {
        $owner = User::factory()->create();

        $workspace = Workspace::create([
            'type' => WorkspaceType::Company,
            'name' => 'Оренза',
            'slug' => 'orenza',
            'owner_id' => $owner->id,
        ]);

        $this->assertDatabaseHas('workspaces', [
            'type' => 'company',
            'name' => 'Оренза',
            'slug' => 'orenza',
            'owner_id' => $owner->id,
        ]);

        $this->assertInstanceOf(WorkspaceType::class, $workspace->fresh()->type);
    }

    public function test_it_belongs_to_an_owner(): void
    {
        $owner = User::factory()->create();
        $workspace = Workspace::factory()->ownedBy($owner)->create();

        $this->assertTrue($workspace->owner->is($owner));
        $this->assertTrue($workspace->isOwnedBy($owner));
        $this->assertFalse($workspace->isOwnedBy(User::factory()->create()));
    }

    public function test_a_user_has_many_owned_workspaces(): void
    {
        $owner = User::factory()->create();
        Workspace::factory()->count(2)->ownedBy($owner)->create();
        Workspace::factory()->create();

        $this->assertCount(2, $owner->ownedWorkspaces);
    }

    public function test_the_slug_is_unique(): void
    {
        Workspace::factory()->create(['slug' => 'orenza']);

        $this->expectException(QueryException::class);

        Workspace::factory()->create(['slug' => 'orenza']);
    }

    public function test_an_owner_with_workspaces_cannot_be_deleted(): void
    {
        $owner = User::factory()->create();
        Workspace::factory()->ownedBy($owner)->create();

        $this->expectException(QueryException::class);

        $owner->delete();
    }

    public function test_it_is_soft_deleted(): void
    {
        $workspace = Workspace::factory()->create();

        $workspace->delete();

        $this->assertSoftDeleted($workspace);
        $this->assertCount(0, Workspace::all());
        $this->assertCount(1, Workspace::withTrashed()->get());
    }

    public function test_it_can_be_filtered_by_type_and_owner(): void
    {
        $owner = User::factory()->create();
        Workspace::factory()->personal()->ownedBy($owner)->create();
        Workspace::factory()->company()->ownedBy($owner)->create();
        Workspace::factory()->personal()->create();

        $this->assertCount(2, Workspace::query()->ofType(WorkspaceType::Personal)->get());
        $this->assertCount(1, Workspace::query()->ofType(WorkspaceType::Personal)->ownedBy($owner)->get());
    }

    public function test_it_is_resolved_by_slug_in_routes(): void
    {
        $workspace = Workspace::factory()->create(['slug' => 'orenza']);

        $this->assertSame('slug', $workspace->getRouteKeyName());
        $this->assertSame('orenza', $workspace->getRouteKey());
    }
}
