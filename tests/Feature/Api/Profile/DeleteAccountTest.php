<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Profile;

use App\Enums\CoverVariant;
use App\Enums\PhotoVariant;
use App\Models\ConstructionObject;
use App\Models\ObjectPhoto;
use App\Models\User;
use App\Models\Workspace;
use App\Support\Media\CoverStorage;
use App\Support\Media\PhotoStorage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class DeleteAccountTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_user_can_delete_their_account(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('laptop')->plainTextToken;
        $user->createToken('phone');

        $this->withHeader('Authorization', "Bearer {$token}")
            ->deleteJson('/api/v1/profile', ['password' => 'password'])
            ->assertOk()
            ->assertJsonPath('message', __('messages.profile.deleted'));

        $this->assertModelMissing($user);
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_owned_workspaces_are_removed_with_all_their_data(): void
    {
        $user = User::factory()->create();
        $workspace = $this->workspaceOf($user);
        $archived = $this->workspaceOf($user);
        $archived->delete();

        $object = ConstructionObject::factory()->ofWorkspace($workspace)->create();
        $trashed = ConstructionObject::factory()->ofWorkspace($workspace)->create();
        $trashed->delete();

        $this->actingAs($user, 'sanctum')
            ->deleteJson('/api/v1/profile', ['password' => 'password'])
            ->assertOk();

        $this->assertDatabaseMissing('workspaces', ['id' => $workspace->id]);
        $this->assertDatabaseMissing('workspaces', ['id' => $archived->id]);
        $this->assertDatabaseMissing('construction_objects', ['id' => $object->id]);
        $this->assertDatabaseMissing('construction_objects', ['id' => $trashed->id]);
    }

    public function test_media_of_owned_workspaces_is_purged(): void
    {
        $disk = Storage::fake('media');
        $user = User::factory()->create();
        $object = ConstructionObject::factory()->ofWorkspace($this->workspaceOf($user))->withCover()->create();
        $photo = ObjectPhoto::factory()->ofObject($object)->create();

        $coverPath = app(CoverStorage::class)->path($object->id, $object->cover, CoverVariant::cases()[0]);
        $photoPath = app(PhotoStorage::class)->path($object->id, $photo->key, PhotoVariant::cases()[0]);
        $disk->put($coverPath, 'cover');
        $disk->put($photoPath, 'photo');

        $this->actingAs($user, 'sanctum')
            ->deleteJson('/api/v1/profile', ['password' => 'password'])
            ->assertOk();

        $disk->assertMissing([$coverPath, $photoPath]);
    }

    public function test_workspaces_of_other_users_stay(): void
    {
        $user = User::factory()->create();
        $owner = User::factory()->create();
        $foreign = $this->workspaceOf($owner);

        $this->actingAs($user, 'sanctum')
            ->deleteJson('/api/v1/profile', ['password' => 'password'])
            ->assertOk();

        $this->assertModelExists($foreign);
        $this->assertModelExists($owner);
    }

    public function test_a_wrong_password_is_rejected(): void
    {
        $user = User::factory()->create();
        $workspace = $this->workspaceOf($user);

        $this->actingAs($user, 'sanctum')
            ->deleteJson('/api/v1/profile', ['password' => 'not-my-password'])
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['password']]);

        $this->assertModelExists($user);
        $this->assertModelExists($workspace);
    }

    public function test_the_password_is_required(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->deleteJson('/api/v1/profile')
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['password']]);
    }

    public function test_guessing_the_password_is_throttled(): void
    {
        $user = User::factory()->create();

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->actingAs($user, 'sanctum')
                ->deleteJson('/api/v1/profile', ['password' => "guess-{$attempt}"])
                ->assertStatus(422);
        }

        $this->actingAs($user, 'sanctum')
            ->deleteJson('/api/v1/profile', ['password' => 'password'])
            ->assertStatus(429);

        $this->assertModelExists($user);
    }

    public function test_a_guest_cannot_delete_an_account(): void
    {
        $this->deleteJson('/api/v1/profile', ['password' => 'password'])->assertUnauthorized();
    }

    private function workspaceOf(User $user): Workspace
    {
        return Workspace::factory()->ownedBy($user)->create();
    }
}
