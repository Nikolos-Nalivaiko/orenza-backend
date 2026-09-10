<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Objects;

use App\Models\Client;
use App\Models\ConstructionObject;
use App\Models\Membership;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class CreateObjectTest extends TestCase
{
    use RefreshDatabase;

    private function workspaceFor(User $user): Workspace
    {
        $workspace = Workspace::factory()->company()->ownedBy($user)->create();

        Membership::factory()->forWorkspace($workspace)->forUser($user)->owner()->create();

        return $workspace;
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return [
            'name' => 'ЖК «Пасаж»',
            'address' => 'вул. Стеценка, 12 · Київ',
            'started_at' => '2026-06-01',
            'finished_at' => '2026-10-14',
            ...$overrides,
        ];
    }

    public function test_a_guest_cannot_create_an_object(): void
    {
        $workspace = Workspace::factory()->create();

        $this->postJson("/api/v1/workspaces/{$workspace->slug}/objects", $this->payload())
            ->assertUnauthorized();
    }

    public function test_a_stranger_cannot_create_an_object_in_someone_elses_workspace(): void
    {
        $workspace = Workspace::factory()->create();

        $this->actingAs(User::factory()->create(), 'sanctum')
            ->postJson("/api/v1/workspaces/{$workspace->slug}/objects", $this->payload())
            ->assertForbidden();

        $this->assertDatabaseCount('construction_objects', 0);
    }

    public function test_an_object_is_created_with_the_planned_status_and_a_public_token(): void
    {
        $user = User::factory()->create();
        $workspace = $this->workspaceFor($user);

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/workspaces/{$workspace->slug}/objects", $this->payload())
            ->assertCreated()
            ->assertJsonPath('data.name', 'ЖК «Пасаж»')
            ->assertJsonPath('data.address', 'вул. Стеценка, 12 · Київ')
            ->assertJsonPath('data.status.value', 'planned')
            ->assertJsonPath('data.status.label', 'Планується')
            ->assertJsonPath('data.started_at', '2026-06-01')
            ->assertJsonPath('data.finished_at', '2026-10-14')
            ->assertJsonPath('data.client', null)
            ->assertJsonPath('data.archived_at', null);

        $object = ConstructionObject::sole();

        $this->assertSame($workspace->id, $object->workspace_id);
        $this->assertSame(32, mb_strlen((string) $object->public_token));
    }

    public function test_every_object_gets_its_own_public_token(): void
    {
        $user = User::factory()->create();
        $workspace = $this->workspaceFor($user);

        foreach (['ЖК «Пасаж»', 'Котеджі «Липки»'] as $name) {
            $this->actingAs($user, 'sanctum')
                ->postJson("/api/v1/workspaces/{$workspace->slug}/objects", $this->payload(['name' => $name]))
                ->assertCreated();
        }

        $this->assertSame(2, ConstructionObject::query()->distinct()->count('public_token'));
    }

    public function test_a_name_and_an_address_are_required(): void
    {
        $user = User::factory()->create();
        $workspace = $this->workspaceFor($user);

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/workspaces/{$workspace->slug}/objects", ['name' => 'ЖК'])
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'validation_failed')
            ->assertJsonStructure(['errors' => ['name', 'address']]);
    }

    public function test_a_client_of_the_same_workspace_is_linked(): void
    {
        $user = User::factory()->create();
        $workspace = $this->workspaceFor($user);
        $client = Client::factory()->ofWorkspace($workspace)->create(['name' => 'ТОВ «Мегабуд»']);

        $this->actingAs($user, 'sanctum')
            ->postJson(
                "/api/v1/workspaces/{$workspace->slug}/objects",
                $this->payload(['client_id' => $client->id]),
            )
            ->assertCreated()
            ->assertJsonPath('data.client.id', $client->id)
            ->assertJsonPath('data.client.name', 'ТОВ «Мегабуд»');
    }

    public function test_a_client_of_another_workspace_is_rejected(): void
    {
        $user = User::factory()->create();
        $workspace = $this->workspaceFor($user);
        $stranger = Client::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->postJson(
                "/api/v1/workspaces/{$workspace->slug}/objects",
                $this->payload(['client_id' => $stranger->id]),
            )
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['client_id']]);
    }

    public function test_empty_dates_are_accepted_as_unknown(): void
    {
        $user = User::factory()->create();
        $workspace = $this->workspaceFor($user);

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/workspaces/{$workspace->slug}/objects", $this->payload([
                'started_at' => '',
                'finished_at' => '',
                'actual_started_at' => '',
                'actual_finished_at' => '',
            ]))
            ->assertCreated()
            ->assertJsonPath('data.started_at', null)
            ->assertJsonPath('data.actual_finished_at', null);
    }

    public function test_a_finish_date_cannot_precede_the_start(): void
    {
        $user = User::factory()->create();
        $workspace = $this->workspaceFor($user);

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/workspaces/{$workspace->slug}/objects", $this->payload([
                'started_at' => '2026-10-14',
                'finished_at' => '2026-06-01',
            ]))
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['finished_at']]);
    }

    public function test_an_object_in_progress_needs_an_actual_start(): void
    {
        $user = User::factory()->create();
        $workspace = $this->workspaceFor($user);

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/workspaces/{$workspace->slug}/objects", $this->payload([
                'status' => 'in_progress',
            ]))
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['actual_started_at']]);

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/workspaces/{$workspace->slug}/objects", $this->payload([
                'status' => 'in_progress',
                'actual_started_at' => '2026-06-08',
            ]))
            ->assertCreated()
            ->assertJsonPath('data.status.value', 'in_progress')
            ->assertJsonPath('data.actual_started_at', '2026-06-08');
    }

    public function test_a_finished_object_needs_an_actual_finish(): void
    {
        $user = User::factory()->create();
        $workspace = $this->workspaceFor($user);

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/workspaces/{$workspace->slug}/objects", $this->payload([
                'status' => 'done',
                'actual_started_at' => '2026-06-08',
            ]))
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['actual_finished_at']]);
    }

    public function test_an_actual_finish_without_a_start_is_rejected(): void
    {
        $user = User::factory()->create();
        $workspace = $this->workspaceFor($user);

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/workspaces/{$workspace->slug}/objects", $this->payload([
                'actual_finished_at' => '2026-10-02',
            ]))
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['actual_started_at']]);
    }

    public function test_an_object_is_created_together_with_its_positions(): void
    {
        $user = User::factory()->create();
        $workspace = $this->workspaceFor($user);

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/workspaces/{$workspace->slug}/objects", $this->payload([
                'materials' => [
                    [
                        'name' => 'Бетон М300',
                        'unit' => 'м³',
                        'quantity' => 12.5,
                        'buyer' => 'contractor',
                        'cost_price' => 3000,
                        'client_price' => 3600,
                        'status' => 'needed',
                        'approved_by_client' => false,
                    ],
                ],
            ]))
            ->assertCreated()
            ->assertJsonCount(1, 'data.materials')
            ->assertJsonPath('data.materials.0.name', 'Бетон М300')
            ->assertJsonPath('data.materials.0.client_price', 3600);

        $this->assertDatabaseCount('materials', 1);
    }

    public function test_a_broken_position_takes_the_whole_object_with_it(): void
    {
        $user = User::factory()->create();
        $workspace = $this->workspaceFor($user);

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/workspaces/{$workspace->slug}/objects", $this->payload([
                'materials' => [['name' => 'Бетон', 'unit' => 'м³', 'quantity' => -5]],
            ]))
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['materials.0.quantity']]);

        $this->assertDatabaseCount('construction_objects', 0);
        $this->assertDatabaseCount('materials', 0);
    }

    public function test_an_object_without_positions_reports_an_empty_list(): void
    {
        $user = User::factory()->create();
        $workspace = $this->workspaceFor($user);

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/workspaces/{$workspace->slug}/objects", $this->payload())
            ->assertCreated()
            ->assertJsonPath('data.materials', []);
    }

    public function test_an_object_is_created_together_with_its_works(): void
    {
        $user = User::factory()->create();
        $workspace = $this->workspaceFor($user);

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/workspaces/{$workspace->slug}/objects", $this->payload([
                'services' => [
                    [
                        'name' => 'Монолітні роботи',
                        'unit' => 'м³',
                        'planned_volume' => 120,
                        'client_price' => 1000,
                        'status' => 'planned',
                        'workers' => [['employee_id' => 7, 'volume' => 120, 'rate' => 400]],
                    ],
                ],
            ]))
            ->assertCreated()
            ->assertJsonCount(1, 'data.services')
            ->assertJsonPath('data.services.0.name', 'Монолітні роботи')
            ->assertJsonPath('data.services.0.workers.0.employee_id', 7);

        $this->assertDatabaseCount('services', 1);
        $this->assertDatabaseCount('service_workers', 1);
    }

    public function test_an_object_without_works_reports_an_empty_list(): void
    {
        $user = User::factory()->create();
        $workspace = $this->workspaceFor($user);

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/workspaces/{$workspace->slug}/objects", $this->payload())
            ->assertCreated()
            ->assertJsonPath('data.services', []);
    }

    public function test_an_object_is_created_together_with_its_payments(): void
    {
        $user = User::factory()->create();
        $workspace = $this->workspaceFor($user);

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/workspaces/{$workspace->slug}/objects", $this->payload([
                'discount_percent' => 5,
                'payments' => [
                    ['name' => 'Аванс', 'amount' => 100000, 'status' => 'paid', 'paid_at' => '2026-06-12'],
                    ['name' => 'Доплата', 'amount' => 250000, 'status' => 'pending'],
                ],
            ]))
            ->assertCreated()
            ->assertJsonCount(2, 'data.payments')
            ->assertJsonPath('data.discount_percent', 5)
            ->assertJsonPath('data.payments.0.name', 'Аванс')
            ->assertJsonPath('data.payments.0.paid_at', '2026-06-12')
            ->assertJsonPath('data.payments.1.status.value', 'pending');

        $this->assertDatabaseCount('payments', 2);
    }

    public function test_an_unknown_status_is_rejected(): void
    {
        $user = User::factory()->create();
        $workspace = $this->workspaceFor($user);

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/workspaces/{$workspace->slug}/objects", $this->payload(['status' => 'frozen']))
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['status']]);
    }
}
