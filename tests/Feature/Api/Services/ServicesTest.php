<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Services;

use App\Enums\ServiceStatus;
use App\Models\ConstructionObject;
use App\Models\Membership;
use App\Models\Service;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

final class ServicesTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Workspace $workspace;

    private ConstructionObject $object;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->workspace = Workspace::factory()->company()->ownedBy($this->user)->create();

        Membership::factory()
            ->forWorkspace($this->workspace)
            ->forUser($this->user)
            ->owner()
            ->create();

        $this->object = ConstructionObject::factory()->ofWorkspace($this->workspace)->create();
    }

    private function path(string $tail = '', ?Workspace $workspace = null, ?ConstructionObject $object = null): string
    {
        $workspace ??= $this->workspace;
        $object ??= $this->object;

        return "/api/v1/workspaces/{$workspace->slug}/objects/{$object->id}/services{$tail}";
    }

    private function personalObject(): ConstructionObject
    {
        $personal = Workspace::factory()->personal()->ownedBy($this->user)->create();

        Membership::factory()->forWorkspace($personal)->forUser($this->user)->owner()->create();

        return ConstructionObject::factory()->ofWorkspace($personal)->create();
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function add(array $payload = [], ?string $path = null): TestResponse
    {
        return $this->actingAs($this->user, 'sanctum')->postJson($path ?? $this->path(), [
            'name' => 'Монолітні роботи',
            'unit' => 'м³',
            'planned_volume' => 120,
            'client_price' => 1000,
            'status' => 'planned',
            ...$payload,
        ]);
    }

    public function test_a_guest_sees_nothing(): void
    {
        $this->getJson($this->path())->assertUnauthorized();
    }

    public function test_a_work_is_added(): void
    {
        $this->add()
            ->assertCreated()
            ->assertJsonPath('data.name', 'Монолітні роботи')
            ->assertJsonPath('data.unit', 'м³')
            ->assertJsonPath('data.planned_volume', 120)
            ->assertJsonPath('data.actual_volume', null)
            ->assertJsonPath('data.client_price', 1000)
            ->assertJsonPath('data.status.value', 'planned')
            ->assertJsonPath('data.status.label', 'Заплановано')
            ->assertJsonPath('data.workers', []);
    }

    public function test_a_work_is_added_with_its_crew(): void
    {
        $this->add(['workers' => [
            ['employee_id' => 7, 'volume' => 80, 'rate' => 400],
            ['employee_id' => 9, 'volume' => 40, 'rate' => 450],
        ]])
            ->assertCreated()
            ->assertJsonCount(2, 'data.workers')
            ->assertJsonPath('data.workers.0.employee_id', 7)
            ->assertJsonPath('data.workers.0.volume', 80)
            ->assertJsonPath('data.workers.1.rate', 450);

        $this->assertDatabaseCount('service_workers', 2);
    }

    public function test_a_personal_workspace_refuses_performers(): void
    {
        $object = $this->personalObject();

        $this->add(
            ['workers' => [['employee_id' => 7, 'volume' => 10, 'rate' => 100]]],
            $this->path('', $object->workspace, $object),
        )
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'business_rule_violation');

        $this->assertDatabaseCount('service_workers', 0);
        $this->assertDatabaseCount('services', 0);
    }

    public function test_a_personal_workspace_still_keeps_works_without_a_crew(): void
    {
        $object = $this->personalObject();

        $this->add([], $this->path('', $object->workspace, $object))
            ->assertCreated()
            ->assertJsonPath('data.workers', []);
    }

    public function test_the_crew_is_replaced_as_a_whole(): void
    {
        $service = Service::factory()->ofObject($this->object)->create();

        $this->actingAs($this->user, 'sanctum')
            ->patchJson($this->path("/{$service->id}"), [
                'workers' => [['employee_id' => 7, 'volume' => 50, 'rate' => 400]],
            ])
            ->assertOk()
            ->assertJsonCount(1, 'data.workers');

        $this->actingAs($this->user, 'sanctum')
            ->patchJson($this->path("/{$service->id}"), [
                'workers' => [['employee_id' => 9, 'volume' => 60, 'rate' => 500]],
            ])
            ->assertOk()
            ->assertJsonCount(1, 'data.workers')
            ->assertJsonPath('data.workers.0.employee_id', 9);

        $this->assertDatabaseCount('service_workers', 1);
    }

    public function test_an_empty_crew_clears_the_performers(): void
    {
        $service = Service::factory()->ofObject($this->object)->create();

        $this->actingAs($this->user, 'sanctum')
            ->patchJson($this->path("/{$service->id}"), [
                'workers' => [['employee_id' => 7, 'volume' => 50, 'rate' => 400]],
            ])
            ->assertOk();

        $this->actingAs($this->user, 'sanctum')
            ->patchJson($this->path("/{$service->id}"), ['workers' => []])
            ->assertOk()
            ->assertJsonPath('data.workers', []);

        $this->assertDatabaseCount('service_workers', 0);
    }

    public function test_the_actual_volume_is_recorded(): void
    {
        $service = Service::factory()->ofObject($this->object)->create();

        $this->actingAs($this->user, 'sanctum')
            ->patchJson($this->path("/{$service->id}"), ['actual_volume' => 96.5])
            ->assertOk()
            ->assertJsonPath('data.actual_volume', 96.5);

        $this->actingAs($this->user, 'sanctum')
            ->patchJson($this->path("/{$service->id}"), ['actual_volume' => null])
            ->assertOk()
            ->assertJsonPath('data.actual_volume', null);
    }

    public function test_a_name_a_unit_and_a_volume_are_required(): void
    {
        $this->actingAs($this->user, 'sanctum')
            ->postJson($this->path(), ['client_price' => 100])
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['name', 'unit', 'planned_volume']]);
    }

    public function test_a_worker_without_an_employee_is_rejected(): void
    {
        $this->add(['workers' => [['volume' => 10, 'rate' => 100]]])
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['workers.0.employee_id']]);
    }

    public function test_the_status_of_several_works_changes_at_once(): void
    {
        $first = Service::factory()->ofObject($this->object)->create();
        $second = Service::factory()->ofObject($this->object)->create();
        $untouched = Service::factory()->ofObject($this->object)->create();

        $this->actingAs($this->user, 'sanctum')
            ->patchJson($this->path('/status'), [
                'ids' => [$first->id, $second->id],
                'status' => 'done',
            ])
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.status.value', 'done');

        $this->assertSame(ServiceStatus::Planned, $untouched->refresh()->status);
    }

    public function test_a_work_is_removed_with_its_crew(): void
    {
        $service = Service::factory()->ofObject($this->object)->create();

        $this->actingAs($this->user, 'sanctum')
            ->patchJson($this->path("/{$service->id}"), [
                'workers' => [['employee_id' => 7, 'volume' => 50, 'rate' => 400]],
            ])
            ->assertOk();

        $this->actingAs($this->user, 'sanctum')
            ->deleteJson($this->path("/{$service->id}"))
            ->assertOk();

        $this->assertDatabaseCount('services', 0);
        $this->assertDatabaseCount('service_workers', 0);
    }

    public function test_a_work_of_another_object_is_not_found(): void
    {
        $stranger = Service::factory()->create();

        $this->actingAs($this->user, 'sanctum')
            ->patchJson($this->path("/{$stranger->id}"), ['actual_volume' => 1])
            ->assertNotFound();
    }

    public function test_works_travel_with_the_object(): void
    {
        $service = Service::factory()->ofObject($this->object)->create(['name' => 'Мурування']);

        $this->actingAs($this->user, 'sanctum')
            ->patchJson($this->path("/{$service->id}"), [
                'workers' => [['employee_id' => 7, 'volume' => 50, 'rate' => 400]],
            ])
            ->assertOk();

        $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/v1/workspaces/{$this->workspace->slug}/objects/{$this->object->id}")
            ->assertOk()
            ->assertJsonPath('data.services.0.name', 'Мурування')
            ->assertJsonPath('data.services.0.workers.0.employee_id', 7);
    }
}
