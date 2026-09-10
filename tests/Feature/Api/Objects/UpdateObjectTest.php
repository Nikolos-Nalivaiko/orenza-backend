<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Objects;

use App\Enums\ObjectStatus;
use App\Models\Client;
use App\Models\ConstructionObject;
use App\Models\Membership;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

final class UpdateObjectTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Workspace $workspace;

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
    }

    private function object(): ConstructionObject
    {
        return ConstructionObject::factory()->ofWorkspace($this->workspace)->create([
            'name' => 'ЖК «Пасаж»',
            'address' => 'вул. Стеценка, 12 · Київ',
            'started_at' => '2026-06-01',
            'finished_at' => '2026-10-14',
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function edit(ConstructionObject $object, array $payload): TestResponse
    {
        return $this->actingAs($this->user, 'sanctum')
            ->patchJson("/api/v1/workspaces/{$this->workspace->slug}/objects/{$object->id}", $payload);
    }

    public function test_a_single_field_is_updated_without_touching_the_rest(): void
    {
        $object = $this->object();

        $this->edit($object, ['name' => 'ЖК «Пасаж», черга 2'])
            ->assertOk()
            ->assertJsonPath('data.name', 'ЖК «Пасаж», черга 2')
            ->assertJsonPath('data.address', 'вул. Стеценка, 12 · Київ')
            ->assertJsonPath('data.started_at', '2026-06-01');
    }

    public function test_a_status_change_is_checked_against_the_dates_already_stored(): void
    {
        $object = $this->object();

        $this->edit($object, ['status' => 'in_progress'])
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['actual_started_at']]);

        $this->edit($object, ['status' => 'in_progress', 'actual_started_at' => '2026-06-08'])
            ->assertOk()
            ->assertJsonPath('data.status.value', 'in_progress');

        $this->edit($object->refresh(), ['status' => 'paused'])
            ->assertOk()
            ->assertJsonPath('data.status.value', 'paused');
    }

    public function test_a_finished_object_needs_the_finish_date_in_the_same_request(): void
    {
        $object = $this->object();

        $this->edit($object, ['status' => 'done', 'actual_started_at' => '2026-06-08'])
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['actual_finished_at']]);

        $this->edit($object, [
            'status' => 'done',
            'actual_started_at' => '2026-06-08',
            'actual_finished_at' => '2026-10-02',
        ])
            ->assertOk()
            ->assertJsonPath('data.status.value', 'done')
            ->assertJsonPath('data.actual_finished_at', '2026-10-02');
    }

    public function test_a_new_date_is_checked_against_the_stored_one(): void
    {
        $object = $this->object();

        $this->edit($object, ['finished_at' => '2026-05-01'])
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['finished_at']]);
    }

    public function test_a_client_is_linked_and_unlinked(): void
    {
        $object = $this->object();
        $client = Client::factory()->ofWorkspace($this->workspace)->create();

        $this->edit($object, ['client_id' => $client->id])
            ->assertOk()
            ->assertJsonPath('data.client.id', $client->id);

        $this->edit($object->refresh(), ['client_id' => null])
            ->assertOk()
            ->assertJsonPath('data.client', null);
    }

    public function test_a_client_of_another_workspace_is_rejected(): void
    {
        $object = $this->object();

        $this->edit($object, ['client_id' => Client::factory()->create()->id])
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['client_id']]);
    }

    public function test_an_object_survives_the_deletion_of_its_client(): void
    {
        $client = Client::factory()->ofWorkspace($this->workspace)->create();
        $object = ConstructionObject::factory()->forClient($client)->create();

        $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/v1/workspaces/{$this->workspace->slug}/clients/{$client->id}")
            ->assertOk();

        $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/v1/workspaces/{$this->workspace->slug}/objects/{$object->id}")
            ->assertOk()
            ->assertJsonPath('data.client', null);
    }

    public function test_an_object_is_archived_and_restored_by_a_flag(): void
    {
        $object = $this->object();

        $this->edit($object, ['archived' => true])->assertOk();

        $this->assertNotNull($object->refresh()->archived_at);

        $this->edit($object, ['archived' => false])
            ->assertOk()
            ->assertJsonPath('data.archived_at', null);

        $this->assertNull($object->refresh()->archived_at);
    }

    public function test_a_discount_is_kept_the_way_it_was_entered(): void
    {
        $object = $this->object();

        $this->edit($object, ['discount_percent' => 5])
            ->assertOk()
            ->assertJsonPath('data.discount_percent', 5)
            ->assertJsonPath('data.discount_amount', null);

        $this->edit($object->refresh(), ['discount_percent' => null, 'discount_amount' => 120000])
            ->assertOk()
            ->assertJsonPath('data.discount_percent', null)
            ->assertJsonPath('data.discount_amount', 120000);
    }

    public function test_a_discount_cannot_be_a_percentage_and_an_amount_at_once(): void
    {
        $object = $this->object();

        $this->edit($object, ['discount_percent' => 5, 'discount_amount' => 120000])
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['discount_amount']]);

        $this->edit($object, ['discount_percent' => 5])->assertOk();

        $this->edit($object->refresh(), ['discount_amount' => 120000])
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['discount_amount']]);
    }

    public function test_a_percentage_above_a_hundred_is_rejected(): void
    {
        $this->edit($this->object(), ['discount_percent' => 120])
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['discount_percent']]);
    }

    public function test_an_object_of_another_workspace_is_not_found(): void
    {
        $stranger = ConstructionObject::factory()->create();

        $this->edit($stranger, ['name' => 'Чужий обʼєкт'])->assertNotFound();
    }

    public function test_an_object_is_soft_deleted(): void
    {
        $object = $this->object();

        $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/v1/workspaces/{$this->workspace->slug}/objects/{$object->id}")
            ->assertOk();

        $this->assertSoftDeleted($object);

        $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/v1/workspaces/{$this->workspace->slug}/objects/{$object->id}")
            ->assertNotFound();
    }

    public function test_the_public_token_is_not_writable(): void
    {
        $object = $this->object();
        $token = $object->public_token;

        $this->edit($object, ['public_token' => str_repeat('a', 32), 'status' => ObjectStatus::Paused->value])
            ->assertOk();

        $this->assertSame($token, $object->refresh()->public_token);
    }
}
