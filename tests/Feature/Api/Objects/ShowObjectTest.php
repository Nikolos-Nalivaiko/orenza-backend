<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Objects;

use App\Models\Client;
use App\Models\ConstructionObject;
use App\Models\Material;
use App\Models\Membership;
use App\Models\Payment;
use App\Models\Service;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

final class ShowObjectTest extends TestCase
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

    private function show(ConstructionObject $object): TestResponse
    {
        return $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/v1/workspaces/{$this->workspace->slug}/objects/{$object->getKey()}");
    }

    public function test_a_guest_sees_nothing(): void
    {
        $object = ConstructionObject::factory()->ofWorkspace($this->workspace)->create();

        $this->getJson("/api/v1/workspaces/{$this->workspace->slug}/objects/{$object->getKey()}")
            ->assertUnauthorized();
    }

    public function test_the_card_carries_everything_the_object_is_made_of(): void
    {
        $client = Client::factory()->ofWorkspace($this->workspace)->create(['name' => 'ТОВ «Мегабуд»']);
        $object = ConstructionObject::factory()->forClient($client)->create(['name' => 'ЖК «Пасаж»']);

        Material::factory()->ofObject($object)->create(['name' => 'Цегла']);
        $service = Service::factory()->ofObject($object)->create(['name' => 'Мурування']);
        Payment::factory()->ofObject($object)->create();

        $service->workers()->create(['employee_id' => 7, 'volume' => 120, 'rate' => 400]);

        $this->show($object)
            ->assertOk()
            ->assertJsonPath('data.id', $object->getKey())
            ->assertJsonPath('data.name', 'ЖК «Пасаж»')
            ->assertJsonPath('data.client.name', 'ТОВ «Мегабуд»')
            ->assertJsonCount(1, 'data.materials')
            ->assertJsonPath('data.materials.0.name', 'Цегла')
            ->assertJsonCount(1, 'data.services')
            ->assertJsonPath('data.services.0.name', 'Мурування')
            ->assertJsonPath('data.services.0.workers.0.employee_id', 7)
            ->assertJsonCount(1, 'data.payments')
            ->assertJsonPath('data.public_token', $object->public_token);
    }

    public function test_an_archived_object_still_opens(): void
    {
        $object = ConstructionObject::factory()->ofWorkspace($this->workspace)->archived()->create();

        $this->show($object)
            ->assertOk()
            ->assertJsonPath('data.id', $object->getKey());
    }

    public function test_an_object_of_another_workspace_is_not_found(): void
    {
        $object = ConstructionObject::factory()->create();

        $this->show($object)->assertNotFound();
    }

    public function test_a_deleted_object_is_not_found(): void
    {
        $object = ConstructionObject::factory()->ofWorkspace($this->workspace)->create();

        $object->delete();

        $this->show($object)->assertNotFound();
    }
}
