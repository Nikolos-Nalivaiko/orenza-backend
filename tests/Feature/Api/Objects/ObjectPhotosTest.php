<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Objects;

use App\Actions\Objects\UploadObjectPhotoAction;
use App\Models\ConstructionObject;
use App\Models\ObjectPhoto;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Tests\Fixtures\BuildsJpegs;
use Tests\TestCase;

final class ObjectPhotosTest extends TestCase
{
    use BuildsJpegs, RefreshDatabase;

    private User $user;

    private Workspace $workspace;

    private FilesystemAdapter $disk;

    protected function setUp(): void
    {
        parent::setUp();

        $this->disk = Storage::fake('media');

        $this->user = User::factory()->create();
        $this->workspace = Workspace::factory()->ownedBy($this->user)->create();

    }

    private function url(ConstructionObject $object, ?ObjectPhoto $photo = null): string
    {
        $base = "/api/v1/workspaces/{$this->workspace->slug}/objects/{$object->getKey()}/photos";

        return $photo === null ? $base : "{$base}/{$photo->getKey()}";
    }

    /**
     * @param  array<string, mixed>  $fields
     */
    private function upload(ConstructionObject $object, UploadedFile $file, array $fields = []): TestResponse
    {
        return $this->actingAs($this->user, 'sanctum')
            ->post($this->url($object), ['photo' => $file, ...$fields], ['Accept' => 'application/json']);
    }

    private function object(): ConstructionObject
    {
        return ConstructionObject::factory()->ofWorkspace($this->workspace)->create();
    }

    /**
     * @return list<string>
     */
    private function storedFiles(ConstructionObject $object): array
    {
        return $this->disk->allFiles("objects/{$object->getKey()}/photos");
    }

    public function test_a_guest_sees_no_photos(): void
    {
        $object = $this->object();

        $this->getJson($this->url($object))->assertUnauthorized();
    }

    public function test_a_stranger_cannot_add_photos(): void
    {
        $object = $this->object();

        $this->actingAs(User::factory()->create(), 'sanctum')
            ->post($this->url($object), ['photo' => UploadedFile::fake()->image('site.jpg', 1600, 1200)], ['Accept' => 'application/json'])
            ->assertForbidden();

        $this->assertDatabaseCount('object_photos', 0);
    }

    public function test_a_photo_is_stored_in_two_webp_sizes(): void
    {
        $object = $this->object();

        $response = $this->upload($object, UploadedFile::fake()->image('IMG_2041.jpg', 4000, 3000))
            ->assertCreated()
            ->assertJsonPath('message', 'Фото додано.')
            ->assertJsonPath('data.width', 4000)
            ->assertJsonPath('data.height', 3000)
            ->assertJsonPath('data.name', 'IMG_2041.jpg')
            ->assertJsonPath('data.taken_at', null);

        $photo = ObjectPhoto::sole();

        $this->assertSame($this->user->id, $photo->uploaded_by);
        $this->assertCount(2, $this->storedFiles($object));

        foreach (['full' => 2048, 'thumb' => 480] as $variant => $side) {
            $path = "objects/{$object->getKey()}/photos/{$photo->key}-{$variant}.webp";

            $this->disk->assertExists($path);
            $this->assertStringEndsWith($path, (string) $response->json("data.{$variant}"));

            $size = getimagesizefromstring((string) $this->disk->get($path));

            $this->assertIsArray($size);
            $this->assertSame('image/webp', $size['mime']);
            $this->assertSame($side, $size[0]);
        }
    }

    public function test_the_shooting_date_comes_from_the_camera(): void
    {
        $object = $this->object();
        $file = UploadedFile::fake()->createWithContent(
            'phone.jpg',
            $this->jpegWithExif(1600, 1200, description: 'GPS-SECRET-MARKER', takenAt: '2026:08:15 10:30:00'),
        );

        $this->upload($object, $file, ['taken_at' => '2026-09-01T12:00:00+03:00'])
            ->assertCreated()
            ->assertJsonPath('data.taken_at', '2026-08-15T10:30:00+00:00');

        foreach ($this->storedFiles($object) as $path) {
            $this->assertStringNotContainsString('GPS-SECRET-MARKER', (string) $this->disk->get($path));
        }
    }

    public function test_the_client_can_suggest_a_date_when_the_camera_did_not(): void
    {
        $object = $this->object();

        $this->upload($object, UploadedFile::fake()->image('export.png', 800, 600), ['taken_at' => '2026-07-03T08:15:00+00:00'])
            ->assertCreated()
            ->assertJsonPath('data.taken_at', '2026-07-03T08:15:00+00:00');
    }

    public function test_a_date_from_the_future_is_rejected(): void
    {
        $object = $this->object();

        $this->upload($object, UploadedFile::fake()->image('site.jpg', 800, 600), ['taken_at' => now()->addWeek()->toIso8601String()])
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['taken_at']]);
    }

    public function test_only_pictures_are_accepted(): void
    {
        $object = $this->object();

        $this->upload($object, UploadedFile::fake()->create('act.pdf', 100, 'application/pdf'))
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['photo']]);

        $this->upload($object, UploadedFile::fake()->image('icon.png', 64, 64))
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['photo']]);

        $this->assertDatabaseCount('object_photos', 0);
    }

    public function test_photos_are_listed_newest_shot_first(): void
    {
        $object = $this->object();

        $old = ObjectPhoto::factory()->ofObject($object)->takenAt('2026-06-01 09:00:00')->create();
        $fresh = ObjectPhoto::factory()->ofObject($object)->takenAt('2026-09-01 09:00:00')->create();
        $middle = ObjectPhoto::factory()->ofObject($object)->takenAt('2026-07-15 09:00:00')->create();
        ObjectPhoto::factory()->create();

        $this->actingAs($this->user, 'sanctum')
            ->getJson($this->url($object))
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('data.0.id', $fresh->id)
            ->assertJsonPath('data.1.id', $middle->id)
            ->assertJsonPath('data.2.id', $old->id)
            ->assertJsonPath('meta.total', 3)
            ->assertJsonPath('meta.limit', UploadObjectPhotoAction::MAX_PER_OBJECT);
    }

    public function test_a_photo_can_be_removed(): void
    {
        $object = $this->object();

        $this->upload($object, UploadedFile::fake()->image('site.jpg', 1600, 1200))->assertCreated();

        $photo = ObjectPhoto::sole();

        $this->actingAs($this->user, 'sanctum')
            ->deleteJson($this->url($object, $photo))
            ->assertOk()
            ->assertJsonPath('message', 'Фото прибрано.');

        $this->assertDatabaseCount('object_photos', 0);
        $this->assertSame([], $this->storedFiles($object));
    }

    public function test_a_photo_of_another_object_is_not_found(): void
    {
        $object = $this->object();
        $foreign = ObjectPhoto::factory()->ofObject($this->object())->create();

        $this->actingAs($this->user, 'sanctum')
            ->deleteJson($this->url($object, $foreign))
            ->assertNotFound();

        $this->assertDatabaseCount('object_photos', 1);
    }

    public function test_an_object_cannot_hold_more_photos_than_the_limit(): void
    {
        $object = $this->object();

        ObjectPhoto::factory()->count(UploadObjectPhotoAction::MAX_PER_OBJECT)->ofObject($object)->create();

        $this->upload($object, UploadedFile::fake()->image('site.jpg', 800, 600))
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'business_rule_violation');

        $this->assertSame([], $this->storedFiles($object));
    }

    public function test_deleting_an_object_removes_its_photos(): void
    {
        $object = $this->object();

        $this->upload($object, UploadedFile::fake()->image('one.jpg', 1600, 1200))->assertCreated();
        $this->upload($object, UploadedFile::fake()->image('two.jpg', 1600, 1200))->assertCreated();

        $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/v1/workspaces/{$this->workspace->slug}/objects/{$object->getKey()}")
            ->assertOk();

        $this->assertDatabaseCount('object_photos', 0);
        $this->assertSame([], $this->storedFiles($object));
    }

    public function test_the_public_page_shows_photos_without_file_names(): void
    {
        $object = $this->object();

        ObjectPhoto::factory()->ofObject($object)->takenAt('2026-08-01 10:00:00')->create(['original_name' => 'IMG_SECRET.jpg']);

        $response = $this->getJson("/api/v1/track/{$object->public_token}")
            ->assertOk()
            ->assertJsonCount(1, 'data.photos')
            ->assertJsonPath('data.photos.0.at', '2026-08-01T10:00:00+00:00');

        $this->assertArrayNotHasKey('name', $response->json('data.photos.0'));
        $this->assertStringNotContainsString('IMG_SECRET', (string) $response->getContent());
    }
}
