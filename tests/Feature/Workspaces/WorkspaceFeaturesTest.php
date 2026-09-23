<?php

declare(strict_types=1);

namespace Tests\Feature\Workspaces;

use App\Enums\WorkspaceType;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

final class WorkspaceFeaturesTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_company_has_a_team_and_a_personal_workspace_does_not(): void
    {
        $this->assertTrue(WorkspaceType::Company->hasTeam());
        $this->assertFalse(WorkspaceType::Personal->hasTeam());

        $this->assertSame(['team' => true], WorkspaceType::Company->features());
        $this->assertSame(['team' => false], WorkspaceType::Personal->features());
    }

    public function test_the_api_reports_the_features_of_each_workspace(): void
    {
        $user = User::factory()->create();
        $company = Workspace::factory()->company()->ownedBy($user)->create();

        $this->actingAs($user, 'sanctum')
            ->getJson("/api/v1/workspaces/{$company->slug}")
            ->assertOk()
            ->assertJsonPath('data.features.team', true);

        $personal = Workspace::factory()->personal()->ownedBy($user)->create();

        $this->actingAs($user, 'sanctum')
            ->getJson("/api/v1/workspaces/{$personal->slug}")
            ->assertOk()
            ->assertJsonPath('data.features.team', false);
    }

    public function test_the_team_cannot_be_managed_in_a_personal_workspace(): void
    {
        $owner = User::factory()->create();
        $personal = Workspace::factory()->personal()->ownedBy($owner)->create();
        $company = Workspace::factory()->company()->ownedBy($owner)->create();
        $foreign = Workspace::factory()->company()->create();

        $this->assertFalse(Gate::forUser($owner)->allows('manageTeam', $personal));
        $this->assertTrue(Gate::forUser($owner)->allows('manageTeam', $company));
        $this->assertFalse(Gate::forUser($owner)->allows('manageTeam', $foreign));
    }
}
