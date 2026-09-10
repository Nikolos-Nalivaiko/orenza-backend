<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Materials;

use App\Enums\MaterialStatus;
use App\Models\ConstructionObject;
use App\Models\Material;
use App\Models\Membership;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

final class MaterialsTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Workspace $workspace;

    private ConstructionObject $object;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->workspace = Workspace::factory()->ownedBy($this->user)->create();

        Membership::factory()
            ->forWorkspace($this->workspace)
            ->forUser($this->user)
            ->owner()
            ->create();

        $this->object = ConstructionObject::factory()->ofWorkspace($this->workspace)->create();
    }

    private function path(string $tail = ''): string
    {
        return "/api/v1/workspaces/{$this->workspace->slug}/objects/{$this->object->id}/materials{$tail}";
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function add(array $payload = []): TestResponse
    {
        return $this->actingAs($this->user, 'sanctum')->postJson($this->path(), [
            'name' => 'Бетон М300',
            'unit' => 'м³',
            'quantity' => 12.5,
            'buyer' => 'contractor',
            'cost_price' => 3000,
            'client_price' => 3600,
            'status' => 'needed',
            'approved_by_client' => false,
            ...$payload,
        ]);
    }

    public function test_a_guest_sees_nothing(): void
    {
        $this->getJson($this->path())->assertUnauthorized();
    }

    public function test_a_stranger_cannot_read_the_positions(): void
    {
        $this->actingAs(User::factory()->create(), 'sanctum')->getJson($this->path())->assertForbidden();
    }

    public function test_a_position_is_added(): void
    {
        $this->add()
            ->assertCreated()
            ->assertJsonPath('data.name', 'Бетон М300')
            ->assertJsonPath('data.unit', 'м³')
            ->assertJsonPath('data.quantity', 12.5)
            ->assertJsonPath('data.buyer.value', 'contractor')
            ->assertJsonPath('data.buyer.label', 'Підрядник')
            ->assertJsonPath('data.cost_price', 3000)
            ->assertJsonPath('data.client_price', 3600)
            ->assertJsonPath('data.status.value', 'needed')
            ->assertJsonPath('data.status.label', 'Потрібно')
            ->assertJsonPath('data.approved_by_client', false);

        $this->assertSame($this->object->id, Material::sole()->construction_object_id);
    }

    public function test_a_position_bought_by_the_client_carries_no_prices(): void
    {
        $this->add(['buyer' => 'client'])
            ->assertCreated()
            ->assertJsonPath('data.cost_price', null)
            ->assertJsonPath('data.client_price', null);
    }

    public function test_switching_the_buyer_to_the_client_clears_the_prices(): void
    {
        $material = Material::factory()->ofObject($this->object)->create();

        $this->actingAs($this->user, 'sanctum')
            ->patchJson($this->path("/{$material->id}"), ['buyer' => 'client'])
            ->assertOk()
            ->assertJsonPath('data.cost_price', null)
            ->assertJsonPath('data.client_price', null);
    }

    public function test_a_name_and_a_quantity_are_required(): void
    {
        $this->actingAs($this->user, 'sanctum')
            ->postJson($this->path(), ['unit' => 'шт'])
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['name', 'quantity']]);
    }

    public function test_a_negative_quantity_is_rejected(): void
    {
        $this->add(['quantity' => -1])
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['quantity']]);
    }

    public function test_positions_are_listed_in_the_order_they_were_added(): void
    {
        $this->add(['name' => 'Перший'])->assertCreated();
        $this->add(['name' => 'Другий'])->assertCreated();

        $this->actingAs($this->user, 'sanctum')
            ->getJson($this->path())
            ->assertOk()
            ->assertJsonPath('meta.total', 2)
            ->assertJsonPath('data.0.name', 'Перший')
            ->assertJsonPath('data.1.name', 'Другий');
    }

    public function test_the_status_of_several_positions_changes_at_once(): void
    {
        $first = Material::factory()->ofObject($this->object)->create();
        $second = Material::factory()->ofObject($this->object)->create();
        $untouched = Material::factory()->ofObject($this->object)->create();

        $this->actingAs($this->user, 'sanctum')
            ->patchJson($this->path('/status'), [
                'ids' => [$first->id, $second->id],
                'status' => 'delivered',
            ])
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.status.value', 'delivered')
            ->assertJsonPath('data.1.status.value', 'delivered');

        $this->assertSame(MaterialStatus::Needed, $untouched->refresh()->status);
    }

    public function test_the_status_endpoint_ignores_positions_of_another_object(): void
    {
        $stranger = Material::factory()->create();

        $this->actingAs($this->user, 'sanctum')
            ->patchJson($this->path('/status'), ['ids' => [$stranger->id], 'status' => 'used'])
            ->assertOk()
            ->assertJsonCount(0, 'data');

        $this->assertSame(MaterialStatus::Needed, $stranger->refresh()->status);
    }

    public function test_approval_is_toggled(): void
    {
        $material = Material::factory()->ofObject($this->object)->create();

        $this->actingAs($this->user, 'sanctum')
            ->patchJson($this->path("/{$material->id}"), ['approved_by_client' => true])
            ->assertOk()
            ->assertJsonPath('data.approved_by_client', true);

        $this->assertTrue($material->refresh()->approved_by_client);
    }

    public function test_a_position_is_removed(): void
    {
        $material = Material::factory()->ofObject($this->object)->create();

        $this->actingAs($this->user, 'sanctum')
            ->deleteJson($this->path("/{$material->id}"))
            ->assertOk();

        $this->assertDatabaseCount('materials', 0);
    }

    public function test_a_position_of_another_object_is_not_found(): void
    {
        $stranger = Material::factory()->create();

        $this->actingAs($this->user, 'sanctum')
            ->patchJson($this->path("/{$stranger->id}"), ['approved_by_client' => true])
            ->assertNotFound();
    }

    public function test_positions_travel_with_the_object(): void
    {
        Material::factory()->ofObject($this->object)->create(['name' => 'Цегла']);

        $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/v1/workspaces/{$this->workspace->slug}/objects/{$this->object->id}")
            ->assertOk()
            ->assertJsonPath('data.materials.0.name', 'Цегла');
    }

    public function test_positions_die_with_the_object(): void
    {
        Material::factory()->ofObject($this->object)->create();

        $this->object->forceDelete();

        $this->assertDatabaseCount('materials', 0);
    }
}
