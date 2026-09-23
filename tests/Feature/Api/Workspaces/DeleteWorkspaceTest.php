<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Workspaces;

use App\Enums\CoverVariant;
use App\Enums\PhotoVariant;
use App\Models\Client;
use App\Models\ConstructionObject;
use App\Models\ObjectPhoto;
use App\Models\User;
use App\Models\Workspace;
use App\Support\Media\CoverStorage;
use App\Support\Media\PhotoStorage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class DeleteWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_owner_can_delete_the_workspace_with_all_its_data(): void
    {
        $owner = User::factory()->create();
        $workspace = $this->workspaceOf($owner, 'БудМайстер');
        $client = Client::factory()->ofWorkspace($workspace)->create();
        $object = ConstructionObject::factory()->ofWorkspace($workspace)->forClient($client)->create();
        $trashed = ConstructionObject::factory()->ofWorkspace($workspace)->create();
        $trashed->delete();

        $this->actingAs($owner, 'sanctum')
            ->deleteJson("/api/v1/workspaces/{$workspace->slug}", ['name' => ' БудМайстер '])
            ->assertOk()
            ->assertJsonPath('message', __('messages.workspaces.deleted'));

        $this->assertDatabaseMissing('workspaces', ['id' => $workspace->id]);
        $this->assertDatabaseMissing('clients', ['id' => $client->id]);
        $this->assertDatabaseMissing('construction_objects', ['id' => $object->id]);
        $this->assertDatabaseMissing('construction_objects', ['id' => $trashed->id]);
        $this->assertModelExists($owner);
    }

    public function test_other_workspaces_of_the_owner_stay(): void
    {
        $owner = User::factory()->create();
        $workspace = $this->workspaceOf($owner, 'Перший');
        $other = $this->workspaceOf($owner, 'Другий');
        $otherObject = ConstructionObject::factory()->ofWorkspace($other)->create();

        $this->actingAs($owner, 'sanctum')
            ->deleteJson("/api/v1/workspaces/{$workspace->slug}", ['name' => 'Перший'])
            ->assertOk();

        $this->assertModelExists($other);
        $this->assertModelExists($otherObject);
    }

    public function test_the_current_workspace_is_reset(): void
    {
        $owner = User::factory()->create();
        $workspace = $this->workspaceOf($owner, 'БудМайстер');

        $owner->forceFill(['current_workspace_id' => $workspace->id])->save();

        $this->actingAs($owner, 'sanctum')
            ->deleteJson("/api/v1/workspaces/{$workspace->slug}", ['name' => 'БудМайстер'])
            ->assertOk();

        $this->assertNull($owner->refresh()->current_workspace_id);
    }

    public function test_media_files_are_purged(): void
    {
        $disk = Storage::fake('media');
        $owner = User::factory()->create();
        $workspace = $this->workspaceOf($owner, 'БудМайстер');
        $object = ConstructionObject::factory()->ofWorkspace($workspace)->withCover()->create();
        $photo = ObjectPhoto::factory()->ofObject($object)->create();

        $coverPath = app(CoverStorage::class)->path($object->id, $object->cover, CoverVariant::cases()[0]);
        $photoPath = app(PhotoStorage::class)->path($object->id, $photo->key, PhotoVariant::cases()[0]);
        $disk->put($coverPath, 'cover');
        $disk->put($photoPath, 'photo');

        $this->actingAs($owner, 'sanctum')
            ->deleteJson("/api/v1/workspaces/{$workspace->slug}", ['name' => 'БудМайстер'])
            ->assertOk();

        $disk->assertMissing([$coverPath, $photoPath]);
    }

    public function test_the_tracking_link_stops_working(): void
    {
        $owner = User::factory()->create();
        $workspace = $this->workspaceOf($owner, 'БудМайстер');
        $object = ConstructionObject::factory()->ofWorkspace($workspace)->create();

        $this->actingAs($owner, 'sanctum')
            ->deleteJson("/api/v1/workspaces/{$workspace->slug}", ['name' => 'БудМайстер'])
            ->assertOk();

        $this->getJson("/api/v1/track/{$object->public_token}")->assertNotFound();
    }

    public function test_a_wrong_confirmation_name_is_rejected(): void
    {
        $owner = User::factory()->create();
        $workspace = $this->workspaceOf($owner, 'БудМайстер');

        foreach ([[], ['name' => ''], ['name' => 'Інша назва']] as $payload) {
            $this->actingAs($owner, 'sanctum')
                ->deleteJson("/api/v1/workspaces/{$workspace->slug}", $payload)
                ->assertStatus(422)
                ->assertJsonStructure(['errors' => ['name']]);
        }

        $this->assertModelExists($workspace);
    }

    public function test_an_outsider_cannot_delete(): void
    {
        $workspace = $this->workspaceOf(User::factory()->create(), 'БудМайстер');

        $this->actingAs(User::factory()->create(), 'sanctum')
            ->deleteJson("/api/v1/workspaces/{$workspace->slug}", ['name' => 'БудМайстер'])
            ->assertForbidden();

        $this->assertModelExists($workspace);
    }

    public function test_a_guest_cannot_delete(): void
    {
        $workspace = $this->workspaceOf(User::factory()->create(), 'БудМайстер');

        $this->deleteJson("/api/v1/workspaces/{$workspace->slug}", ['name' => 'БудМайстер'])->assertUnauthorized();
    }

    private function workspaceOf(User $user, string $name): Workspace
    {
        return Workspace::factory()->company()->ownedBy($user)->create(['name' => $name]);
    }
}
