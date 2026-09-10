<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Clients;

use App\Models\Client;
use App\Models\Membership;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class CreateClientTest extends TestCase
{
    use RefreshDatabase;

    private function workspaceFor(User $user): Workspace
    {
        $workspace = Workspace::factory()->ownedBy($user)->create();

        Membership::factory()->forWorkspace($workspace)->forUser($user)->owner()->create();

        return $workspace;
    }

    public function test_a_client_is_created_with_a_phone(): void
    {
        $user = User::factory()->create();
        $workspace = $this->workspaceFor($user);

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/workspaces/{$workspace->slug}/clients", [
                'type' => 'person',
                'name' => 'Олександр Романюк',
                'phone' => '+380 67 123 45 67',
            ])
            ->assertCreated()
            ->assertJsonPath('data.name', 'Олександр Романюк')
            ->assertJsonPath('data.phone', '+380671234567');

        $this->assertSame('+380671234567', Client::sole()->phone);
    }

    public function test_a_client_cannot_be_created_without_a_phone(): void
    {
        $user = User::factory()->create();
        $workspace = $this->workspaceFor($user);

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/workspaces/{$workspace->slug}/clients", [
                'type' => 'person',
                'name' => 'Олександр Романюк',
            ])
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'validation_failed')
            ->assertJsonStructure(['errors' => ['phone']]);

        $this->assertDatabaseCount('clients', 0);
    }

    public function test_an_empty_phone_counts_as_no_phone(): void
    {
        $user = User::factory()->create();
        $workspace = $this->workspaceFor($user);

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/workspaces/{$workspace->slug}/clients", [
                'type' => 'person',
                'name' => 'Олександр Романюк',
                'phone' => '   ',
            ])
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['phone']]);
    }

    public function test_an_existing_client_is_updated_without_touching_the_phone(): void
    {
        $user = User::factory()->create();
        $workspace = $this->workspaceFor($user);

        $client = Client::factory()->ofWorkspace($workspace)->create(['phone' => null]);

        $this->actingAs($user, 'sanctum')
            ->patchJson("/api/v1/workspaces/{$workspace->slug}/clients/{$client->id}", [
                'name' => 'Нове імʼя',
            ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Нове імʼя');
    }
}
