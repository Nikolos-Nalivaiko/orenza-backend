<?php

declare(strict_types=1);

namespace Tests\Feature\Models;

use App\Enums\MembershipRole;
use App\Enums\MembershipStatus;
use App\Models\Membership;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class MembershipTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_persists_the_expected_columns(): void
    {
        $workspace = Workspace::factory()->create();
        $user = User::factory()->create();
        $inviter = User::factory()->create();

        $membership = Membership::create([
            'workspace_id' => $workspace->id,
            'user_id' => $user->id,
            'role' => MembershipRole::Admin,
            'status' => MembershipStatus::Active,
            'invited_by_id' => $inviter->id,
        ]);

        $this->assertDatabaseHas('memberships', [
            'workspace_id' => $workspace->id,
            'user_id' => $user->id,
            'role' => 'admin',
            'status' => 'active',
            'invited_by_id' => $inviter->id,
        ]);

        $membership = $membership->fresh();

        $this->assertSame(MembershipRole::Admin, $membership->role);
        $this->assertSame(MembershipStatus::Active, $membership->status);
    }

    public function test_defaults_are_member_and_active(): void
    {
        $membership = Membership::create([
            'workspace_id' => Workspace::factory()->create()->id,
            'user_id' => User::factory()->create()->id,
        ])->fresh();

        $this->assertSame(MembershipRole::Member, $membership->role);
        $this->assertSame(MembershipStatus::Active, $membership->status);
        $this->assertNull($membership->invited_by_id);
    }

    public function test_a_user_cannot_join_the_same_workspace_twice(): void
    {
        $workspace = Workspace::factory()->create();
        $user = User::factory()->create();

        Membership::factory()->forWorkspace($workspace)->forUser($user)->create();

        $this->expectException(QueryException::class);

        Membership::factory()->forWorkspace($workspace)->forUser($user)->create();
    }

    public function test_it_belongs_to_a_workspace_a_user_and_an_inviter(): void
    {
        $workspace = Workspace::factory()->create();
        $user = User::factory()->create();
        $inviter = User::factory()->create();

        $membership = Membership::factory()
            ->forWorkspace($workspace)
            ->forUser($user)
            ->invitedBy($inviter)
            ->create();

        $this->assertTrue($membership->workspace->is($workspace));
        $this->assertTrue($membership->user->is($user));
        $this->assertTrue($membership->invitedBy->is($inviter));
    }

    public function test_a_user_can_belong_to_several_workspaces_with_different_roles(): void
    {
        $user = User::factory()->create();
        $first = Workspace::factory()->create();
        $second = Workspace::factory()->create();

        Membership::factory()->owner()->forWorkspace($first)->forUser($user)->create();
        Membership::factory()->admin()->forWorkspace($second)->forUser($user)->create();

        $this->assertCount(2, $user->workspaces);
        $this->assertSame(MembershipRole::Owner, $user->membershipFor($first)->role);
        $this->assertSame(MembershipRole::Admin, $user->membershipFor($second)->role);
        $this->assertTrue($user->belongsToWorkspace($first));
        $this->assertFalse($user->belongsToWorkspace(Workspace::factory()->create()));
    }

    public function test_the_pivot_is_a_membership_with_casts(): void
    {
        $user = User::factory()->create();
        $workspace = Workspace::factory()->create();

        Membership::factory()->admin()->forWorkspace($workspace)->forUser($user)->create();

        $pivot = $user->workspaces()->first()->membership;

        $this->assertInstanceOf(Membership::class, $pivot);
        $this->assertSame(MembershipRole::Admin, $pivot->role);
        $this->assertTrue($pivot->canManageMembers());
    }

    public function test_a_workspace_exposes_its_members(): void
    {
        $workspace = Workspace::factory()->create();
        $user = User::factory()->create();

        Membership::factory()->forWorkspace($workspace)->forUser($user)->create();
        Membership::factory()->forWorkspace($workspace)->create();
        Membership::factory()->create();

        $this->assertCount(2, $workspace->members);
        $this->assertTrue($workspace->hasMember($user));
        $this->assertSame(MembershipRole::Member, $workspace->membershipFor($user)->role);
    }

    public function test_suspended_members_are_filtered_by_scope(): void
    {
        $workspace = Workspace::factory()->create();

        Membership::factory()->forWorkspace($workspace)->create();
        Membership::factory()->suspended()->forWorkspace($workspace)->create();
        Membership::factory()->owner()->forWorkspace($workspace)->create();

        $this->assertCount(2, $workspace->memberships()->active()->get());
        $this->assertCount(1, $workspace->memberships()->role(MembershipRole::Owner)->get());
    }

    public function test_a_suspended_member_cannot_manage_anything(): void
    {
        $membership = Membership::factory()->admin()->suspended()->create();

        $this->assertFalse($membership->isActive());
        $this->assertFalse($membership->canManageMembers());
    }

    public function test_memberships_are_removed_with_the_workspace_or_the_user(): void
    {
        $workspace = Workspace::factory()->create();
        $user = User::factory()->create();
        Membership::factory()->forWorkspace($workspace)->forUser($user)->create();

        $user->delete();
        $this->assertDatabaseCount('memberships', 0);

        $other = Membership::factory()->forWorkspace($workspace)->create();

        $workspace->forceDelete();
        $this->assertDatabaseMissing('memberships', ['id' => $other->id]);
    }

    public function test_a_user_tracks_the_current_workspace(): void
    {
        $workspace = Workspace::factory()->create();
        $user = User::factory()->create(['current_workspace_id' => $workspace->id]);

        $this->assertTrue($user->currentWorkspace->is($workspace));

        $workspace->forceDelete();

        $this->assertNull($user->fresh()->current_workspace_id);
    }
}
