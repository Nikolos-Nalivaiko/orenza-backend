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
use Tests\TestCase;

final class ListObjectsTest extends TestCase
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

    /**
     * @param  array<string, mixed>  $query
     */
    private function list(array $query = []): \Illuminate\Testing\TestResponse
    {
        $url = "/api/v1/workspaces/{$this->workspace->slug}/objects";

        return $this->actingAs($this->user, 'sanctum')
            ->getJson($query === [] ? $url : $url.'?'.http_build_query($query));
    }

    public function test_a_guest_sees_nothing(): void
    {
        $this->getJson("/api/v1/workspaces/{$this->workspace->slug}/objects")->assertUnauthorized();
    }

    public function test_only_the_objects_of_this_workspace_are_listed(): void
    {
        ConstructionObject::factory()->ofWorkspace($this->workspace)->create(['name' => 'Наш']);
        ConstructionObject::factory()->create(['name' => 'Чужий']);

        $this->list()
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Наш')
            ->assertJsonPath('meta.total', 1);
    }

    public function test_the_newest_object_comes_first(): void
    {
        ConstructionObject::factory()->ofWorkspace($this->workspace)->create([
            'name' => 'Старий',
            'created_at' => now()->subMonth(),
        ]);
        ConstructionObject::factory()->ofWorkspace($this->workspace)->create([
            'name' => 'Свіжий',
            'created_at' => now(),
        ]);

        $this->list()
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Свіжий')
            ->assertJsonPath('data.1.name', 'Старий');
    }

    public function test_archived_objects_are_hidden_until_asked_for(): void
    {
        ConstructionObject::factory()->ofWorkspace($this->workspace)->create(['name' => 'В роботі']);
        ConstructionObject::factory()->ofWorkspace($this->workspace)->archived()->create(['name' => 'В архіві']);

        $this->list()
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'В роботі')
            ->assertJsonPath('meta.all', 2);

        $this->list(['archived' => 1])
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_objects_are_filtered_by_status(): void
    {
        ConstructionObject::factory()->ofWorkspace($this->workspace)->create(['name' => 'Планується']);
        ConstructionObject::factory()->ofWorkspace($this->workspace)->inProgress()->create(['name' => 'В роботі']);

        $this->list(['status' => ObjectStatus::InProgress->value])
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'В роботі');
    }

    public function test_objects_are_searched_by_name_and_address(): void
    {
        ConstructionObject::factory()->ofWorkspace($this->workspace)->create([
            'name' => 'ЖК «Пасаж»',
            'address' => 'вул. Стеценка, 12 · Київ',
        ]);
        ConstructionObject::factory()->ofWorkspace($this->workspace)->create([
            'name' => 'Котеджі «Липки»',
            'address' => 'вул. Лісова, 3 · Буча',
        ]);

        $this->list(['search' => 'Пасаж'])
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'ЖК «Пасаж»');

        $this->list(['search' => 'Буча'])
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Котеджі «Липки»');
    }

    public function test_the_client_travels_with_the_object(): void
    {
        $client = Client::factory()->ofWorkspace($this->workspace)->create(['name' => 'ТОВ «Мегабуд»']);

        ConstructionObject::factory()->forClient($client)->create();

        $this->list()
            ->assertOk()
            ->assertJsonPath('data.0.client.name', 'ТОВ «Мегабуд»')
            ->assertJsonPath('data.0.client.phone', $client->phone);
    }

    public function test_a_deleted_object_is_gone_from_the_list(): void
    {
        $object = ConstructionObject::factory()->ofWorkspace($this->workspace)->create();

        $object->delete();

        $this->list()->assertOk()->assertJsonCount(0, 'data')->assertJsonPath('meta.all', 0);
    }
}
