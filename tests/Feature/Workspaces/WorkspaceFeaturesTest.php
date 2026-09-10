<?php

declare(strict_types=1);

namespace Tests\Feature\Workspaces;

use App\Enums\WorkspaceType;
use App\Models\Membership;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

final class WorkspaceFeaturesTest extends TestCase
{
    use RefreshDatabase;

    private function memberOf(Workspace $workspace): User
    {
        $user = User::factory()->create();

        Membership::factory()->forWorkspace($workspace)->forUser($user)->owner()->create();

        return $user;
    }

    public function test_a_company_has_a_team_and_a_personal_workspace_does_not(): void
    {
        $this->assertTrue(WorkspaceType::Company->hasTeam());
        $this->assertFalse(WorkspaceType::Personal->hasTeam());

        $this->assertSame(['team' => true], WorkspaceType::Company->features());
        $this->assertSame(['team' => false], WorkspaceType::Personal->features());
    }

    public function test_the_api_reports_the_features_of_each_workspace(): void
    {
        $company = Workspace::factory()->company()->create();
        $user = $this->memberOf($company);

        $this->actingAs($user, 'sanctum')
            ->getJson("/api/v1/workspaces/{$company->slug}")
            ->assertOk()
            ->assertJsonPath('data.features.team', true);

        $personal = Workspace::factory()->personal()->ownedBy($user)->create();

        Membership::factory()->forWorkspace($personal)->forUser($user)->owner()->create();

        $this->actingAs($user, 'sanctum')
            ->getJson("/api/v1/workspaces/{$personal->slug}")
            ->assertOk()
            ->assertJsonPath('data.features.team', false);
    }

    public function test_the_team_cannot_be_managed_in_a_personal_workspace(): void
    {
        $personal = Workspace::factory()->personal()->create();
        $company = Workspace::factory()->company()->create();

        $inPersonal = $this->memberOf($personal);
        $inCompany = $this->memberOf($company);

        $this->assertFalse(Gate::forUser($inPersonal)->allows('manageTeam', $personal));
        $this->assertTrue(Gate::forUser($inCompany)->allows('manageTeam', $company));
        $this->assertFalse(Gate::forUser($inCompany)->allows('manageTeam', $personal));
    }
}
