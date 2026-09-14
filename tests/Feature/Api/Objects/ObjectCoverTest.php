<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Objects;

use App\Enums\CoverVariant;
use App\Models\ConstructionObject;
use App\Models\Membership;
use App\Models\User;
use App\Models\Workspace;
use App\Support\Media\CoverStorage;
use App\Support\Media\ImageProcessor;
use App\Support\Media\UnreadableImageException;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Tests\Fixtures\BuildsJpegs;
use Tests\TestCase;

final class ObjectCoverTest extends TestCase
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

        Membership::factory()
            ->forWorkspace($this->workspace)
            ->forUser($this->user)
            ->owner()
            ->create();
    }

    private function url(ConstructionObject $object): string
    {
        return "/api/v1/workspaces/{$this->workspace->slug}/objects/{$object->getKey()}/cover";
    }

    /**
     * @param  array<string, mixed>  $fields
     */
    private function upload(ConstructionObject $object, UploadedFile $file, array $fields = []): TestResponse
    {
        return $this->actingAs($this->user, 'sanctum')
            ->post($this->url($object), ['cover' => $file, ...$fields], ['Accept' => 'application/json']);
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
        return $this->disk->allFiles("objects/{$object->getKey()}");
    }

    public function test_a_guest_cannot_upload_a_cover(): void
    {
        $object = $this->object();

        $this->post($this->url($object), ['cover' => UploadedFile::fake()->image('cover.jpg', 1600, 900)], ['Accept' => 'application/json'])
            ->assertUnauthorized();
    }

    public function test_a_stranger_cannot_upload_a_cover(): void
    {
        $object = $this->object();

        $this->actingAs(User::factory()->create(), 'sanctum')
            ->post($this->url($object), ['cover' => UploadedFile::fake()->image('cover.jpg', 1600, 900)], ['Accept' => 'application/json'])
            ->assertForbidden();

        $this->assertSame([], $this->storedFiles($object));
    }

    public function test_an_object_of_another_workspace_is_not_found(): void
    {
        $object = ConstructionObject::factory()->create();

        $this->upload($object, UploadedFile::fake()->image('cover.jpg', 1600, 900))->assertNotFound();
    }

    public function test_an_uploaded_cover_is_stored_in_three_webp_sizes(): void
    {
        $object = $this->object();

        $response = $this->upload($object, UploadedFile::fake()->image('cover.jpg', 3000, 2000))
            ->assertOk()
            ->assertJsonPath('message', 'Обкладинку оновлено.')
            ->assertJsonPath('data.cover.width', 3000)
            ->assertJsonPath('data.cover.height', 2000)
            ->assertJsonPath('data.cover.focus.x', 0.5)
            ->assertJsonPath('data.cover.focus.y', 0.5);

        $this->assertMatchesRegularExpression('/^#[0-9a-f]{6}$/', (string) $response->json('data.cover.color'));

        $cover = $object->refresh()->cover;

        $this->assertNotNull($cover);

        foreach (['hero' => 2048, 'card' => 960, 'thumb' => 320] as $variant => $side) {
            $path = "objects/{$object->getKey()}/cover/{$cover->key}-{$variant}.webp";

            $this->disk->assertExists($path);
            $this->assertStringEndsWith($path, (string) $response->json("data.cover.{$variant}"));

            $size = getimagesizefromstring((string) $this->disk->get($path));

            $this->assertIsArray($size);
            $this->assertSame('image/webp', $size['mime']);
            $this->assertSame($side, $size[0]);
        }
    }

    public function test_a_small_picture_is_never_upscaled(): void
    {
        $object = $this->object();

        $this->upload($object, UploadedFile::fake()->image('cover.png', 640, 360))->assertOk();

        $cover = $object->refresh()->cover;

        $this->assertNotNull($cover);

        $size = getimagesizefromstring((string) $this->disk->get("objects/{$object->getKey()}/cover/{$cover->key}-hero.webp"));

        $this->assertIsArray($size);
        $this->assertSame(640, $size[0]);
    }

    public function test_the_focus_point_can_be_sent_with_the_upload(): void
    {
        $object = $this->object();

        $this->upload($object, UploadedFile::fake()->image('cover.jpg', 1600, 900), ['focus_x' => 0.25, 'focus_y' => 0.8])
            ->assertOk()
            ->assertJsonPath('data.cover.focus.x', 0.25)
            ->assertJsonPath('data.cover.focus.y', 0.8);
    }

    public function test_a_phone_photo_is_turned_upright_and_loses_its_metadata(): void
    {
        $object = $this->object();
        $file = UploadedFile::fake()->createWithContent('phone.jpg', $this->jpegWithExif(1600, 900, 6, 'GPS-SECRET-MARKER'));

        $this->upload($object, $file)
            ->assertOk()
            ->assertJsonPath('data.cover.width', 900)
            ->assertJsonPath('data.cover.height', 1600);

        foreach ($this->storedFiles($object) as $path) {
            $content = (string) $this->disk->get($path);

            $this->assertStringNotContainsString('GPS-SECRET-MARKER', $content);
            $this->assertStringNotContainsString('Exif', $content);

            $size = getimagesizefromstring($content);

            $this->assertIsArray($size);
            $this->assertGreaterThan($size[0], $size[1]);
        }
    }

    public function test_a_new_cover_replaces_the_old_files(): void
    {
        $object = $this->object();

        $this->upload($object, UploadedFile::fake()->image('first.jpg', 1600, 900))->assertOk();

        $first = $this->storedFiles($object);

        $this->upload($object, UploadedFile::fake()->image('second.jpg', 1600, 900))->assertOk();

        $second = $this->storedFiles($object);

        $this->assertCount(3, $second);
        $this->assertSame([], array_intersect($first, $second));
    }

    public function test_only_pictures_are_accepted(): void
    {
        $object = $this->object();

        $this->upload($object, UploadedFile::fake()->create('estimate.pdf', 200, 'application/pdf'))
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'validation_failed')
            ->assertJsonStructure(['errors' => ['cover']]);

        $this->assertNull($object->refresh()->cover);
    }

    public function test_a_tiny_picture_is_rejected(): void
    {
        $object = $this->object();

        $this->upload($object, UploadedFile::fake()->image('icon.png', 120, 120))
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['cover']]);
    }

    public function test_the_file_is_required(): void
    {
        $object = $this->object();

        $this->actingAs($this->user, 'sanctum')
            ->postJson($this->url($object), [])
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['cover']]);
    }

    public function test_a_broken_file_is_reported_as_unreadable(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'cover');

        file_put_contents((string) $path, 'definitely not an image');

        $this->expectException(UnreadableImageException::class);

        try {
            $this->app->make(ImageProcessor::class)->process((string) $path, CoverVariant::cases());
        } finally {
            unlink((string) $path);
        }
    }

    public function test_the_focus_point_can_be_moved_without_a_new_upload(): void
    {
        $object = ConstructionObject::factory()->ofWorkspace($this->workspace)->withCover()->create();
        $key = $object->cover?->key;

        $this->actingAs($this->user, 'sanctum')
            ->patchJson($this->url($object), ['focus_x' => 0.1234, 'focus_y' => 1])
            ->assertOk()
            ->assertJsonPath('data.cover.focus.x', 0.123)
            ->assertJsonPath('data.cover.focus.y', 1);

        $this->assertSame($key, $object->refresh()->cover?->key);
    }

    public function test_the_focus_point_stays_inside_the_picture(): void
    {
        $object = ConstructionObject::factory()->ofWorkspace($this->workspace)->withCover()->create();

        $this->actingAs($this->user, 'sanctum')
            ->patchJson($this->url($object), ['focus_x' => 1.5, 'focus_y' => -0.2])
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['focus_x', 'focus_y']]);
    }

    public function test_there_is_no_focus_without_a_cover(): void
    {
        $object = $this->object();

        $this->actingAs($this->user, 'sanctum')
            ->patchJson($this->url($object), ['focus_x' => 0.3, 'focus_y' => 0.3])
            ->assertStatus(422)
            ->assertJsonPath('message', 'У обʼєкта ще немає обкладинки.');
    }

    public function test_a_cover_can_be_removed(): void
    {
        $object = $this->object();

        $this->upload($object, UploadedFile::fake()->image('cover.jpg', 1600, 900))->assertOk();

        $this->actingAs($this->user, 'sanctum')
            ->deleteJson($this->url($object))
            ->assertOk()
            ->assertJsonPath('message', 'Обкладинку прибрано.')
            ->assertJsonPath('data.cover', null);

        $this->assertNull($object->refresh()->cover);
        $this->assertSame([], $this->storedFiles($object));
    }

    public function test_deleting_an_object_removes_its_cover_files(): void
    {
        $object = $this->object();

        $this->upload($object, UploadedFile::fake()->image('cover.jpg', 1600, 900))->assertOk();

        $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/v1/workspaces/{$this->workspace->slug}/objects/{$object->getKey()}")
            ->assertOk();

        $this->assertSame([], $this->storedFiles($object));
    }

    public function test_the_public_page_shows_the_cover_with_its_focus(): void
    {
        $object = ConstructionObject::factory()->ofWorkspace($this->workspace)->withCover(0.2, 0.7)->create();
        $cover = $object->cover;

        $this->assertNotNull($cover);

        $this->getJson("/api/v1/track/{$object->public_token}")
            ->assertOk()
            ->assertJsonPath('data.cover.hero', $this->app->make(CoverStorage::class)->url(
                $object->getKey(),
                $cover,
                CoverVariant::Hero,
            ))
            ->assertJsonPath('data.cover.focus.x', 0.2)
            ->assertJsonPath('data.cover.focus.y', 0.7)
            ->assertJsonPath('data.cover.color', '#7a8b6c');
    }
}
